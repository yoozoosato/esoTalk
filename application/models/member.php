<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * The member model provides functions for retrieving and managing member data. It also provides methods to
 * handle "last action" types.
 *
 * @package esoTalk
 */
class Member extends Eloquent {

	/**
	 * Enable timestamps.
	 */
	public static $timestamps = true;

	/**
	 * Reserved user names which cannot be used.
	 * 
	 * @var array
	 */
	protected static $reservedNames = array('guest', 'member', 'members', 'moderator', 'moderators', 'administrator', 'administrators', 'suspended');

	/**
	 * The post validation messages.
	 *
	 * @var array
	 */
	public $errors;

	/**
	 * Get the member's channel data.
	 *
	 * @return Channel
	 */
	public function channels()
	{
		return $this->has_many('Member_Channel', 'member_id');
	}

	/**
	 * Get the member's conversation data.
	 *
	 * @return Conversation
	 */
	public function conversations()
	{
		return $this->has_and_belongs_to_many('Conversation', 'members_conversations');
	}

	/**
	 * Get the member's group data.
	 *
	 * @return Group
	 */
	public function groups()
	{
		return $this->has_and_belongs_to_many('Group', 'members_groups');
	}

	/**
	 * Determine if the member is valid.
	 *
	 * @return bool
	 */
	public function valid()
	{
		Validator::register('unique_confirmed', function($attribute, $value, $parameters)
		{
			if ( ! isset($parameters[1])) $parameters[1] = $attribute;

			return DB::connection()->table($parameters[0])->where($parameters[1], '=', $value)->where_confirmed_email('1')->count() == 0;
		});

		$rules = array(
			'username' => 'required|alpha_dash|between:3,20|unique_confirmed:members|not_in:'.implode(',', static::$reservedNames),
			'email'    => 'required|email|unique_confirmed:members',
			'password' => 'required|min:6',
		);

		$validator = Validator::make($this->attributes, $rules);

		$this->errors = ( ! $validator->valid()) ? $validator->errors->all() : array();

		return count($this->errors) == 0;
	}

	/**
	 * Save the member to the database.
	 * 
	 * @return bool
	 */
	public function save()
	{
		// Hash the password.
		if (isset($this->dirty['password']))
		{
			$this->dirty['password'] = Hash::make($this->dirty['password']);
		}

		// MD5 the 'reset password' hash for storage (for extra safety).
		if (isset($this->dirty['reset_password']))
		{
			$this->dirty['reset_password'] = md5($this->dirty['reset_password']);
		}

		// Serialize preferences.
		if (isset($this->dirty['preferences']))
		{
			$this->dirty['preferences'] = serialize($this->dirty['preferences']);
		}

		// Serialize last action detail.
		if (isset($this->dirty['last_action_detail']))
		{
			$this->dirty['last_action_detail'] = serialize($this->dirty['last_action_detail']);
		}

		// If we are creating a new member...
		if ( ! $this->exists)
		{
			parent::save();

			$activity = new Activity(array('type' => 'join', 'member' => $this));
			$activity->save();

			$inserts = array();

			foreach (Channel::all() as $channel)
			{
				if ( ! empty($channel->attributes['default_unsubscribed']))
				{
					$inserts[] = array('member_id' => $this->id, 'channel_id' => $channel->id, 'unsubscribed' => '1');
				}
			}

			if (count($inserts) > 0)
			{
				DB::table('members_channels')->insert($inserts);
			}
		}
		else
		{
			parent::save();
		}
	}

	/**
	 * Fill the Member instance with an array of attributes.
	 */
	public function fill($attributes)
	{
		if (isset($attributes['preferences']))
		{
			$attributes['preferences'] = unserialize($attributes['preferences']);
		}

		if (isset($attributes['last_action_detail']))
		{
			$attributes['last_action_detail'] = unserialize($attributes['last_action_detail']);
		}

		return parent::fill($attributes);
	}

	public function delete($id = null, $delete_posts = false)
	{
		if ($this->exists)
		{
			// Delete the member's posts if necessary.
			if ($delete_posts)
			{
				Post::where_member_id($this->id)->update(array(
					'delete_member_id' => Auth::user()->id,
					'deleted_at' => time()
				));
			}

			// Delete member and other records associated with the member.
			parent::delete();

			DB::table('members_channels')->where_member_id($this->id)->delete();

			DB::table('members_conversations')->where_member_id($this->id)->delete();

			DB::table('members_groups')->where_member_id($this->id)->delete();
		}

		return parent::delete($id);
	}

	/**
	 * Get whether or not this member is currently online.
	 * 
	 * @return bool
	 */
	public function is_online()
	{
		return $this->last_action_at >= time() - C("esoTalk.user_online_expire");
	}

	/**
	 * Get this member's last action information.
	 * 
	 * @return array
	 */
	public function last_action()
	{
		$result = array();

		if ($this->is_online() and isset($this->last_action_detail['type']))
		{
			$type = $this->last_action_detail['type'];

			if (isset(static::$last_action_types[$type]))
			{
				$result = call_user_func(static::$last_action_types[$type], $this->last_action_detail);
			}
		}
		
		return $result + array(null, null);
	}

	protected static $last_action_types = array();

	public static function register_last_action_type($type, $handler)
	{
		static::$last_action_types[$type] = $handler;
	}

	public function avatar()
	{
		
	}

	public function name()
	{
		return $this->username;
	}

	public function is_admin()
	{
		return $this->account == 'administrator';
	}

	


// /**
//  * An array of last action types => their callback functions.
//  * @var array
//  **/
// protected static $lastActionTypes = array();



// /**
//  * Returns whether or not the current user can rename a member.
//  *
//  * @return bool
//  */
// public function canRename($member)
// {
// 	// The user must be an administrator.
// 	return ET::$session->isAdmin();
// }


// /**
//  * Returns whether or not the current user can delete a member.
//  *
//  * @return bool
//  */
// public function canDelete($member)
// {
// 	return $this->canChangePermissions($member);
// }


// /**
//  * Returns whether or not the current user can change a member's permissions.
//  *
//  * @return bool
//  */
// public function canChangePermissions($member)
// {
// 	// The user must be an administrator, and the root admin's permissions can't be changed. A user also
// 	// cannot change their own permissions.
// 	return ET::$session->isAdmin() and $member["memberId"] != C("esoTalk.rootAdmin") and $member["memberId"] != ET::$session->userId;
// }


// /**
//  * Returns whether or not the current user can suspend/unsuspend a member.
//  *
//  * @return bool
//  */
// public function canSuspend($member)
// {
// 	// The user must be an administrator, or they must have the "canSuspend" permission and the member's
// 	// account be either "member" or "suspended". A user cannot suspend or unsuspend themselves, and the root
// 	// admin cannot be suspended.
// 	return
// 	(
// 		ET::$session->isAdmin()
// 		or (ET::$session->user["canSuspend"] and ($member["account"] == ACCOUNT_MEMBER or $member["account"] == ACCOUNT_SUSPENDED))
// 	)
// 	and $member["memberId"] != C("esoTalk.rootAdmin") and $member["memberId"] != ET::$session->userId;
// }


// /**
//  * Set a member's account and groups.
//  *
//  * @param array $member The details of the member to set the account/groups for.
//  * @param string $account The new account.
//  * @param array $groups The new group IDs.
//  * @return bool true on success, false on error.
//  */
// public function setGroups($member, $account, $groups = array())
// {
// 	// Make sure the account is valid.
// 	if (!in_array($account, array(ACCOUNT_MEMBER, ACCOUNT_ADMINISTRATOR, ACCOUNT_SUSPENDED, ACCOUNT_PENDING)))
// 		$this->error("account", "invalidAccount");

// 	if ($this->errorCount()) return false;

// 	// Set the member's new account.
// 	$this->updateById($member["memberId"], array("account" => $account));

// 	// Delete all of the member's existing group associations.
// 	ET::SQL()
// 		->delete()
// 		->from("member_group")
// 		->where("memberId", $member["memberId"])
// 		->exec();

// 	// Insert new member-group associations.
// 	$inserts = array();
// 	foreach ($groups as $id) $inserts[] = array($member["memberId"], $id);
// 	if (count($inserts))
// 		ET::SQL()
// 			->insert("member_group")
// 			->setMultiple(array("memberId", "groupId"), $inserts)
// 			->exec();

// 	// Now we need to create a new activity item, and to do that we need the names of the member's groups.
// 	$groupData = ET::groupModel()->getAll();
// 	$groupNames = array();
// 	foreach ($groups as $id) $groupNames[$id] = $groupData[$id]["name"];

// 	ET::activityModel()->create("groupChange", $member, ET::$session->user, array("account" => $account, "groups" => $groupNames));

// 	return true;
// }

}

// Add default last action types.
Member::register_last_action_type('viewing_conversation', function($data)
{
	if (empty($data['id']))
	{
		return array(T('general.viewing_conversation', array('conversation' => T('general.a_private_conversation'))));
	}

	return array(
		T('general.viewing_conversation', array('conversation' => $data['title'])),
		URL::to_conversation(array($data['id'], URL::slug($data["title"])))
	);
});

Member::register_last_action_type('starting_conversation', function($data)
{
	return array(T('general.starting_conversation'));
});