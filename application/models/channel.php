<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * The channel model provides functions for retrieving and managing channel data.
 *
 * @package esoTalk
 */
class Channel extends Eloquent {

	/**
	 * The post validation messages.
	 *
	 * @var array
	 */
	public $errors;

	/**
	 * Get the channel's group data.
	 *
	 * @return Post
	 */
	public function groups()
	{
		return $this->has_and_belongs_to_many('Group', 'channels_groups');
	}

	/**
	 * Determine if the member is valid.
	 *
	 * @return bool
	 */
	public function valid()
	{
		$rules = array(
			'title' => 'required',
			'slug'  => 'required|alpha_dash|unique:channels'
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
		// Serialize attributes.
		if (isset($this->dirty['attributes']))
		{
			$this->dirty['attributes'] = serialize($this->dirty['attributes']);
		}

		// If we are creating a new member...
		if ( ! $this->exists)
		{
			// Add the channel at the end at the root level.
			$right = Channel::max('rgt');
			$this->dirty['lft'] = ++$right;
			$this->dirty['rgt'] = ++$right;
			
			parent::save();

			// permissions?
		}
		else
		{
			parent::save();
		}
	}

	/**
	 * Fill the Channel instance with an array of attributes.
	 */
	public function fill($attributes)
	{
		if (isset($attributes['attributes']))
		{
			$attributes['attributes'] = unserialize($attributes['attributes']);
		}

		return parent::fill($attributes);
	}

	public static function with_permission($permission = 'view')
	{
		$channels = static::all();

		$groups = Auth::user()->groups;

		// Go through each of the channels and remove ones that the user doesn't have this permission for.
		foreach ($channels as $k => $channel)
		{
			// if ( ! Group::intersect($groups, $channel->groups, true)) unset($channels[$k]);
		}

		return $channels;
	}

	public static function has_permission($id, $permission = 'view')
	{
		$query = static::where_id($id);

		static::add_permission_predicate($query);

		return $query->count();
	}

	public static function add_permission_predicate($query, $permission = 'view', $member = null, $id_column = 'c.id')
	{
		// If no member was specified, use the current user.
		if (!$member) $member = Auth::user();

		// Get an array of group IDs for this member.
		$groups = $member->groups;

		// If the user is an administrator, don't add any SQL, as admins can do anything!
		if (isset($groups[GROUP_ID_ADMINISTRATOR])) return;

		$query->raw_where($id_column.' IN (SELECT id FROM '.DB::prefix().'channels_groups WHERE group_id IN (...) AND '.$permission.' = 1)', array_keys($groups));
	}


	/**
	 * Set permissions for a channel.
	 *
	 * @param int $channelId The ID of the channel to set permissions for.
	 * @param array $permissions An array of permissions to set.
	 */
	public function setPermissions($channelId, $permissions)
	{
		// Delete already-existing permissions for this channel.
		ET::SQL()
			->delete()
			->from("channel_group")
			->where("channelId=:channelId")
			->bind(":channelId", $channelId, PDO::PARAM_INT)
			->exec();

		// Go through each group ID and set its permission types.
		foreach ($permissions as $groupId => $types) {
			$set = array();
			foreach ($types as $type => $v) {
				if ($v) $set[$type] = 1;
			}
			ET::SQL()
				->insert("channel_group")
				->set("channelId", $channelId)
				->set("groupId", $groupId)
				->set($set)
				->exec();
		}

		// Reset channels in the global cache.
		ET::$cache->remove(self::CACHE_KEY);
	}


	/**
	 * Set a member's status entry for a channel (their record in the member_channel table.)
	 *
	 * @param int $channelId The ID of the channel to set the member's status for.
	 * @param int $memberId The ID of the member to set the status for.
	 * @param array $data An array of key => value data to save to the database.
	 * @return void
	 */
	public function setStatus($channelId, $memberId, $data)
	{
		$keys = array(
			"memberId" => $memberId,
			"channelId" => $channelId
		);
		ET::SQL()->insert("member_channel")->set($keys + $data)->setOnDuplicateKey($data)->exec();
	}


	/**
	 * Delete a channel and its conversations (or optionally move its conversations to another channel.)
	 *
	 * @param int $channelId The ID of the channel to delete.
	 * @param bool|int $moveToChannelId The ID of the channel to move conversations to, or false to delete them.
	 * @return bool true on success, false on error.
	 */
	public function deleteById($channelId, $moveToChannelId = false)
	{
		$channelId = (int)$channelId;

		// Do we want to move the conversations to another channel?
		if ($moveToChannelId !== false) {

			// If the channel does exist, move all the conversation over to it.
			if (array_key_exists((int)$moveToChannelId, $this->getAll())) {
				ET::SQL()
					->update("conversation")
					->set("channelId", (int)$moveToChannelId)
					->where("channelId=:channelId")
					->bind(":channelId", $channelId)
					->exec();
			}

			// But if it doesn't, set an error.
			else $this->error("moveToChannelId", "invalidChannel");

		}

		// Or do we want to simply delete the conversations?
		else ET::conversationModel()->delete(array("channelId" => $channelId));

		if ($this->errorCount()) return false;

		$result = parent::deleteById($channelId);

		// Reset channels in the global cache.
		ET::$cache->remove(self::CACHE_KEY);

		return $result;
	}

}