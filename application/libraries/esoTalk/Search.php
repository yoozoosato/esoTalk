<?php namespace esoTalk;

use DB;
use Auth;
use Conversation;

// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

class Search {


	/**
	 * An array of functional gambits. Each gambit is an array(callback, condition)
	 * 
	 * @var array
	 * @see addGambit
	 */
	protected static $gambits = array();


	/**
	 * An array of aliases. An alias is a string of text which is just shorthand for a more complex
	 * gambit. Each alias is an array(term, replacement term)
	 * 
	 * @var array
	 * @see addAlias
	 */
	protected static $aliases = array();


	/**
	 * Whether or not there are more results for the most recent search than what was returned.
	 * 
	 * @var bool
	 */
	public $more_results = false;


	/**
	 * The SQL query object used to construct a query that retrieves a list of matching conversation IDs.
	 * 
	 * @var Query
	 */
	public $query;


	/**
	 * An array of converastion ID filters that should be run before querying the conversations table
	 * for a final list of conversation IDs.
	 * 
	 * @var array
	 * @see addIDFilter
	 */
	protected $id_filters = array();


	/**
	 * An array of fields to order the conversation IDs by.
	 * 
	 * @var array
	 */
	protected $order_by = array();


	/**
	 * Whether or not the direction in the $orderBy fields should be reversed.
	 * 
	 * @var bool
	 */
	public $order_reverse = false;


	/**
	 * Whether or not the direction in the $orderBy fields should be reversed.
	 * 
	 * @var bool
	 */
	protected $limit = false;


	/**
	 * Whether or not to include muted conversations in the results.
	 * 
	 * @var bool
	 */
	public $include_muted = false;


	/**
	 * An array of fulltext keywords to filter the results by.
	 * 
	 * @var array
	 */
	public $fulltext = array();


	/**
	 * Add a gambit to the collection. When a search term is matched to a gambit, the specified
	 * callback function will be called. A match is determined by the return value of running
	 * $condition through eval().
	 *
	 * @param string $condition The condition to run through eval() to determine a match.
	 * 		$term represents the search term, in lowercase, in the eval() context. The condition
	 * 		should return a boolean value: true means a match, false means no match.
	 * 		Example: return $term == "sticky";
	 * @param array $function The function to call if the gambit is matched. Function will be called
	 * 		with parameters callback($sender, $term, $negate).
	 * @return void
	 */
	public static function register_gambit($condition, $function)
	{
		static::$gambits[] = compact('condition', 'function');
	}


	/**
	 * Add an alias for another gambit to the collection. When a search term is matched
	 * to an alias, it will be interpreted as $realTerm.
	 *
	 * @param string $term The alias term.
	 * @param string $realTerm The replacement term.
	 * @return void
	 */
	public static function register_alias($term, $real_term)
	{
		static::$aliases[$term] = $real_term;
	}


	/**
	 * Add an SQL query to be run before the conversations table is queried for the final list of
	 * conversation IDs. The query should return a list of conversation IDs; the results then will be
	 * limited to conversations matching this list of IDs.
	 *
	 * See some of the default gambits for examples.
	 *
	 * @param ETSQLQuery $sql The SQL query that will return a list of matching conversation IDs.
	 * @param bool $negate If set to true, the returned conversation IDs will be blacklisted.
	 * @return void
	 */
	public function add_id_filter($query, $negate = false)
	{
		$this->id_filters[] = compact('query', 'negate');
	}


	/**
	 * Add a term to include in a fulltext search.
	 *
	 * @param string $term The term.
	 * @return void
	 */
	public function fulltext($term)
	{
		$this->fulltext[] = $term;
	}


	/**
	 * Apply an order to the search results. This function will ensure that a direction (ASC|DESC) is
	 * at the end.
	 *
	 * @param string $order The field to order the results by.
	 * @return void
	 */
	public function order_by($order)
	{
		$direction = substr($order, strrpos($order, ' ') + 1);
		if ($direction != 'ASC' and $direction != 'DESC')
		{
			$order .= ' ASC';
		}
		$this->order_by[] = $order;
	}


	/**
	 * Apply a custom limit to the number of search results returned.
	 *
	 * @param int $limit The limit.
	 * @return void
	 */
	public function limit($limit)
	{
		$this->limit = $limit;
	}


	/**
	 * Determines whether or not the user is "flooding" the search system, based on the number of searches
	 * they have performed in the last minute.
	 *
	 * @return bool|int If the user is not flooding, returns false, but if they are, returned the number
	 * 		of seconds until they can perform another search.
	 */
	public function is_flooding()
	{
		// if (C("esoTalk.search.searchesPerMinute") <= 0) return false;
		// $time = time();
		// $period = 60;

		// // If we have a record of their searches in the session, check how many searches they've performed in the last minute.
		// $searches = ET::$session->get("searches");
		// if (!empty($searches)) {

		// 	// Clean anything older than $period seconds out of the searches array.
		// 	foreach ($searches as $k => $v) {
		// 		if ($v < $time - $period) unset($searches[$k]);
		// 	}

		// 	// Have they performed >= [searchesPerMinute] searches in the last minute? If so, they are flooding.
		// 	if (count($searches) >= C("esoTalk.search.searchesPerMinute"))
		// 		return $period - $time + min($searches);
		// }

		// // However, if we don't have a record in the session, query the database searches table.
		// else {

		// 	// Get the user's IP address.
		// 	$ip = (int)ip2long(ET::$session->ip);

		// 	// Have they performed >= $config["searchesPerMinute"] searches in the last minute?
		// 	$sql = ET::SQL()
		// 		->select("COUNT(ip)")
		// 		->from("search")
		// 		->where("type='conversations'")
		// 		->where("ip=:ip")->bind(":ip", $ip)
		// 		->where("time>:time")->bind(":time", $time - $period);

		// 	if ($sql->exec()->result() >= C("esoTalk.search.searchesPerMinute"))
		// 		return $period;

		// 	// Log this search in the searches table.
		// 	ET::SQL()->insert("search")->set("type", "conversations")->set("ip", $ip)->set("time", $time)->exec();

		// 	// Proactively clean the searches table of searches older than $period seconds.
		// 	ET::SQL()->delete()->from("search")->where("type", "conversations")->where("time<:time")->bind(":time", $time - $period)->exec();
		// }

		// // Log this search in the session array.
		// $searches[] = $time;
		// ET::$session->store("searches", $searches);

		return false;
	}


	/**
	 * Deconstruct a search string and return a list of conversation IDs that fulfill it.
	 *
	 * @param array $channelIDs A list of channel IDs to include results from.
	 * @param string $searchString The search string to deconstruct and find matching conversations.
	 * @param bool $orderBySticky Whether or not to put stickied conversations at the top.
	 * @return array|bool An array of matching conversation IDs, or false if there are none.
	 */
	public function get_conversation_ids($channel_ids = array(), $search_string = "", $sticky_at_top = false)
	{
		if ($search_string and ($seconds = $this->is_flooding()))
		{
			$this->errors = new Messages(array('search' => sprintf(T("message.waitToSearch"), $seconds)));

			return false;
		}

		// Initialize the SQL query that will return the resulting conversation IDs.
		$this->query = DB::table('conversations');

		// Only get conversations in the specified channels.
		if (count($channel_ids))
		{
			$this->query->where_in('channel_id', $channel_ids);
		}

		// Process the search string into individial terms. Replace all "-" signs with "+!", and then
		// split the string by "+". Negated terms will then be prefixed with "!". Only keep the first
		// 5 terms, just to keep the load on the database down!
		$terms = !empty($search_string) ? explode('+', strtolower(str_replace('-', '+!', trim($searchString, ' +-')))) : array();

		$terms = array_slice($terms, 0, 5);

		// Take each term, match it with a gambit, and execute the gambit's function.
		foreach ($terms as $term)
		{
			$term = trim($term);

			// Are we dealing with a negated search term, ie. prefixed with a "!"?
			if ($negate = ($term[0] == '!'))
			{
				$term = trim($term, '! ');
			}

			// If the term is an alias, translate it into the appropriate gambit.
			if (array_key_exists($term, static::$aliases))
			{
				$term = static::$aliases[$term];
			}

			// Find a matching gambit by evaluating each gambit's condition, and run its callback function.
			foreach (static::$gambits as $gambit)
			{
				if (eval($gambit['condition']))
				{
					call_user_func_array($gambit['function'], array($this, $term, $negate));
					continue 2;
				}
			}

			// If we didn't find a gambit, use this term as a fulltext term.
			if ($negate)
			{
				$term = '-'.str_replace(' ', ' -', $term);
			}

			$this->fulltext($term);
		}

		// If an order for the search results has not been specified, apply a default.
		// Order by sticky and then last post time.
		if ( ! count($this->query->orderings))
		{
			if ($sticky_at_top)
			{
				$this->query->order_by('sticky', 'desc');
			}
			$this->query->order_by('last_post_at', 'desc');
		}

		// If we're not including muted conversations, add a where predicate to the query to exclude them.
		if ( ! $this->include_muted and Auth::check())
		{
			$this->query->raw_where('id NOT IN (SELECT conversation_id FROM '.DB::prefix().'members_conversations WHERE member_id = ? AND muted = 1)', array(Auth::user()->id));
		}

		// Now we need to loop through the ID filters and run them one-by-one. When a query returns a selection
		// of conversation IDs, subsequent queries are restricted to filtering those conversation IDs,
		// and so on, until we have a list of IDs to pass to the final query.
		$good_conversation_ids = array();
		$bad_conversation_ids = array();
		$id_condition = '';

		foreach ($this->id_filters as $filter)
		{
			extract($filter);

			if (count($good_conversation_ids))
			{
				$query->where_in('conversation_id', $good_conversation_ids);
			}
			elseif (count($bad_conversation_ids))
			{
				$query->where_not_in('conversation_id', $bad_conversation_ids);
			}

			// Get the list of conversation IDs so that the next condition can use it in its query.
			$ids = $query->distinct()->get(array('conversation_id'));

			foreach ($ids as &$id)
			{
				$id = reset($id);
			}

			// If this condition is negated, then add the IDs to the list of bad conversations.
			// If the condition is not negated, set the list of good conversations to the IDs, provided there are some.
			if ($negate)
			{
				$bad_conversation_ids = array_merge($bad_conversation_ids, $ids);
			}
			elseif (count($ids))
			{
				$good_conversation_ids = $ids;
			}
			else
			{
				return false;
			}

			// Strip bad conversation IDs from the list of good conversation IDs.
			if (count($good_conversation_ids))
			{
				$good_conversation_ids = array_diff($good_conversation_ids, $bad_conversation_ids);
				if (!count($good_conversation_ids)) return false;
			}
		}

		// Reverse the order if necessary - swap DESC and ASC.
		if ($this->order_reverse)
		{
			foreach ($this->query->orderings as &$order)
			{
				$order['direction'] = ($order['direction'] == 'desc') ? 'asc' : 'desc';
			}
		}

		// Now check if there are any fulltext keywords to filter by.
		if (count($this->fulltext))
		{
			// Run a query against the posts table to get matching conversation IDs.
			$fulltext_string = implode(" ", $this->fulltext);

			$query = DB::table('posts')
				->raw_where('MATCH (title, content) AGAINST (? IN BOOLEAN MODE)', array($fulltext_string));
				// ->order_by(DB::raw('MATCH (title, content) AGAINST (?)'))
			
			if (count($good_conversation_ids))
			{
				$query->where_in('conversation_id', $good_conversation_ids);
			}
			elseif (count($bad_conversation_ids))
			{
				$query->where_not_in('conversation_id', $bad_conversation_ids);
			}

			$ids = $query->distinct()->get(array('conversation_id'));

			foreach ($ids as &$id)
			{
				$id = reset($id);
			}

			// Change the ID condition to this list of matching IDs, and order by relevance.
			if (count($ids))
			{
				$good_conversation_ids = $ids;
			}
			else
			{
				return false;
			}

			// $this->query->orderings = array();
			// $this->query->order_by(DB::raw('FIELD(id, '.implode(',', $id)));
		}

		// Set a default limit if none has previously been set. Set it with one more result than we'll
		// need so we can see if there are "more results."
		if ( ! $this->query->limit) $this->query->take(C("esoTalk.search.results") + 1);

		// Finish constructing the final query using the ID whitelist/blacklist we've come up with.
		if (count($good_conversation_ids))
		{
			$this->query->where_in('conversation_id', $good_conversation_ids);
		}
		elseif (count($bad_conversation_ids))
		{
			$this->query->where_not_in('conversation_id', $bad_conversation_ids);
		}

		// Make sure conversations that the user isn't allowed to see are filtered out.
		Conversation::add_allowed_predicate($this->query);

		// Execute the query, and collect the final set of conversation IDs.
		$ids = $this->query->get(array('id'));
		
		foreach ($ids as &$id)
		{
			$id = reset($id);
		}

		// If there's one more result than we actually need, indicate that there are "more results."
		if ($this->query->limit == C("esoTalk.search.results") + 1 and count($ids) == $this->query->limit) {
			array_pop($ids);
			$this->more_results = true;
		}

		return count($ids) ? $ids : false;
	}


	/**
	 * Get a full list of conversation details for a list of conversation IDs.
	 *
	 * @param array $conversationIDs The list of conversation IDs to fetch details for.
	 * @param bool $checkForPermission Whether or not to add a check onto the query to make sure the
	 * 		user has permission to view all of the conversations.
	 */
	// public function getResults($conversationIDs, $checkForPermission = false)
	// {
	// 	// Construct a query to get details for all of the specified conversations.
	// 	$sql = ET::SQL()
	// 		->select("s.*") // Select the status fields first so that the conversation fields take precedence.
	// 		->select("c.*")
	// 		->select("sm.username", "startMember")
	// 		->select("sm.avatarFormat", "startMemberAvatarFormat")
	// 		->select("lpm.username", "lastPostMember")
	// 		->select("lpm.avatarFormat", "lastPostMemberAvatarFormat")
	// 		->select("IF((IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)>:markedAsRead AND (s.lastRead IS NULL OR s.lastRead<c.countPosts)),(c.countPosts - IF(s.lastRead IS NULL,0,s.lastRead)),0)", "unread")
	// 		->from("conversation c")
	// 		->from("member_conversation s", "s.conversationId=c.conversationId AND s.type='member' AND s.id=:memberId", "left")
	// 		->from("member sm", "c.startMemberId=sm.memberId", "left")
	// 		->from("member lpm", "c.lastPostMemberId=lpm.memberId", "left")
	// 		->from("channel ch", "c.channelId=ch.channelId", "left")
	// 		->bind(":markedAsRead", ET::$session->preference("markedAllConversationsAsRead"))
	// 		->bind(":memberId", ET::$session->userId);

	// 	// If we need to, filter out all conversations that the user isn't allowed to see.
	// 	if ($checkForPermission) ET::conversationModel()->addAllowedPredicate($sql);

	// 	// Add a labels column to the query.
	// 	ET::conversationModel()->addLabels($sql);

	// 	// Limit the results to the specified conversation IDs
	// 	$sql->where("c.conversationId IN (:conversationIds)")->orderBy("FIELD(c.conversationId,:conversationIdsOrder)");
	// 	$sql->bind(":conversationIds", $conversationIDs, PDO::PARAM_INT);
	// 	$sql->bind(":conversationIdsOrder", $conversationIDs, PDO::PARAM_INT);

	// 	$this->trigger("beforeGetResults", array(&$sql));

	// 	// Execute the query and put the details of the conversations into an array.
	// 	$result = $sql->exec();
	// 	$results = array();
	// 	$model = ET::conversationModel();

	// 	while ($row = $result->nextRow()) {

	// 		// Expand the comma-separated label flags into a workable array of active labels.
	// 		$row["labels"] = $model->expandLabels($row["labels"]);

	// 		$row["replies"] = max(0, $row["countPosts"] - 1);
	// 		$results[] = $row;

	// 	}

	// 	$this->trigger("afterGetResults", array(&$results));

	// 	return $results;
	// }

}

require 'gambits.php';