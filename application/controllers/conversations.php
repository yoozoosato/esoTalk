<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * The conversations controller displays a list of conversations, and allows filtering by channels
 * and gambits. It also handles marking all conversations as read, and has a method which provides
 * auto-refresh results for the conversations view.
 *
 * @package esoTalk
 */
class Conversations_Controller extends Base_Controller {

	public $restful = true;

	/**
	 * Display a list of conversations, optionally filtered by channel(s)
	 * and a search string.
	 *
	 * @return void
	 */
	public function get_index($channel_slug = false)
	{
		// Add the default gambits to the gambit cloud. The gambits array
		// is structured as the gambit text => the CSS class to apply.
		// Additionally, we can add some more personal gambits if there
		// is a user logged in.
		$gambits = array(
			T('gambit.active_last_?_hours')              => 'gambit-activeLastHours',
			T('gambit.active_last_?_days')               => 'gambit-activeLastDays',
			T('gambit.active_today')                     => 'gambit-activeToday',
			T('gambit.author:').T('gambit.member')       => 'gambit-author',
			T('gambit.contributor:').T('gambit.member')  => 'gambit-contributor',
			T('gambit.dead')                             => 'gambit-dead',
			T('gambit.has_replies')                      => 'gambit-hasReplies',
			T('gambit.has_>10_replies')                  => 'gambit-replies',
			T('gambit.locked')                           => 'gambit-locked',
			T('gambit.more_results')                     => 'gambit-more',
			T('gambit.order_by_newest')                  => 'gambit-orderByNewest',
			T('gambit.order_by_replies')                 => 'gambit-orderByReplies',
			T('gambit.random')                           => 'gambit-random',
			T('gambit.reverse')                          => 'gambit-reverse',
			T('gambit.sticky')                           => 'gambit-sticky',
		);

		if (Auth::check())
		{
			$gambits += array(
				T('gambit.contributor:').T('gambit.myself')  => 'gambit-contributorMyself',
				T('gambit.author:').T('gambit.myself')       => 'gambit-authorMyself',
				T('gambit.draft')                            => 'gambit-draft',
				T('gambit.muted')                            => 'gambit-muted',
				T('gambit.private')                          => 'gambit-private',
				T('gambit.starred')                          => 'gambit-starred',
				T('gambit.unread')                           => 'gambit-unread'
			);
		}

		// Sort out which channels we will be getting conversations from.
		// We start off by getting a list of all viewable channels. Then,
		// we get a list of selected channels (i.e. channels listed in the
		// $channel_slug) and a list of all the channel IDs that we will
		// get conversation results from (may include descendant channels.)
		$channels = Channel::with_permission();

		$member_channels = Auth::user()->channels;

		list($selected_channels, $channel_ids, $include_descendants) = $this->get_selected_channels($channels, $channel_slug);

		// Now we need to construct some arrays to determine which channel
		// 'tabs' to show in the view. $siblings is a list of channels 
		// with the same parent as the current selected channel(s).
		// $parents is a breadcrumb trail to the depth of the currently
		// selected channel(s).
		$parents = $siblings = array();

		// Work out what channel we will use as the 'parent' channel.
		// This will be the last item in $parents, and its children will
		// be in $siblings.
		$parent = false;

		// If channels have been selected, use the first of them.
		if (count($selected_channels))
		{
			$parent = $channels[$selected_channels[0]];
		}

		// If the currently selected channel has no children, or if we're
		// not including descendants, use its parent as the parent channel.
		if (($parent and $parent->lft >= $parent->rgt - 1) or !$include_descendants)
		{
			$parent = Arr::get($channels, $parent->parent_id);
		}

		// If no channel is selected, make a faux parent channel.
		if (!$parent)
		{
			$parent = new Channel;
			$parent->lft = 0;
			$parent->rgt = PHP_INT_MAX;
			$parent->depth = -1;
		}

		// Now, finally, go through all the channels and add ancestors of
		// the $parent channel to the $parents, and direct children to the
		// list of $siblings. Make sure we don't include any channels which
		// the user has unsubscribed to.
		$member_channels = Auth::user()->channels;

		foreach ($channels as $channel)
		{
			if ($channel->lft > $parent->lft and $channel->rgt < $parent->rgt and $channel->depth == $parent->depth + 1 and empty($member_channels[$channel->id]->unsubscribed))
			{
				$siblings[] = $channel;
			}
			elseif ($channel->lft <= $parent->lft and $channel->rgt >= $parent->rgt)
			{
				$parents[] = $channel;
			}
		}

		// Store the currently selected channel in the session, so that it can be automatically selected
		// if "New conversation" is clicked.
		if (!empty($selected_channels))
		{
			Session::put('channel_id', $selected_channels[0]);
		}

		// Get the search string request value.
		$search_string = Input::get('search');

		// Last, but definitely not least... perform the search!
		$search = new Search;
		$conversation_ids = $search->get_conversation_ids($channel_ids, $search_string, count($selected_channels));

		if ($conversation_ids)
		{
			$conversations = Conversation::where_in('id', $conversation_ids)->get();
		}

		// Were there any errors? Show them as messages.
		if ($search->errors)
		{
			Page::flash($search->errors, 'warning');
		}

		// Add fulltext keywords to be highlighted. Make sure we keep ones "in quotes" together.
		else
		{
			$words = array();
			foreach ($search->fulltext as $term)
			{
				if (preg_match_all('/"(.+?)"/', $term, $matches))
				{
					$words[] = $matches[1];
					$term = preg_replace('/".+?"/', '', $term);
				}
				$words = array_unique(array_merge($words, explode(' ', $term)));
			}
			Session::put('highlight', $words);
		}

		$data = array(
			'conversations' => $conversations,
			'show_view_more_link' => $search->more_results,
			'gambits' => $gambits
		);

		$data_channels = compact('parents', 'siblings', 'channels', 'selected_channels');

		$channels_view = View::make('channels/tabs', $data_channels);

		// If we're loading the page in full...
		if ( ! Request::ajax()) {

			// Update the user's last action.
			// Auth::user()->save_last_action('search');

			// Construct a canonical URL and add to the breadcrumb stack.
			$slugs = array();
			foreach ($selected_channels as $id)
			{
				$slugs[] = $channels[$id]->slug;
			}
			$slugs = count($slugs) ? implode(' ', $slugs) : 'all';
			$url = URL::to_conversations(array($slugs, $search_string));
			Page::navigate('conversations', 'search', $url);
			Page::canonical_url($url);

			// Add a link to the RSS feed in the bar.
			// Menu::add_to_meta('feed', HTML::link_to_<a href='".URL(str_replace("conversations/", "conversations/index.atom/", $url))."' id='feed'>".T("Feed")."</a>");

			// Construct a list of keywords to use in the meta tags.
			// $keywords = array();
			// foreach ($channelInfo as $c) {
			// 	if ($c["depth"] == 0) $keywords[] = strtolower($c["title"]);
			// }

			// // Add meta tags to the header.
			// $this->addToHead("<meta name='keywords' content='".sanitizeHTML(($k = C("esoTalk.meta.keywords")) ? $k : implode(",", $keywords))."'>");
			// list($lastKeyword) = array_splice($keywords, count($keywords) - 1, 1);
			// $this->addToHead("<meta name='description' content='".sanitizeHTML(($d = C("esoTalk.meta.description")) ? $d
			// 	: sprintf(T("forumDescription"), C("esoTalk.forumTitle"), implode(", ", $keywords), $lastKeyword))."'>");

			// If this is not technically the homepage (if it's a search page) the we don't want it to be indexed.
			// if ($searchString) $this->addToHead("<meta name='robots' content='noindex, noarchive'>");

			// Add JavaScript language definitions and variables.
			// $this->addJSLanguage("Starred", "Unstarred", "gambit.member", "gambit.more results", "Filter conversations", "Jump to last");
			// $this->addJSVar("searchUpdateInterval", C("esoTalk.search.updateInterval"));
			// $this->addJSVar("currentSearch", $searchString);
			// $this->addJSVar("currentChannels", $currentChannels);
			// $this->addJSFile("js/lib/jquery.cookie.js");
			// $this->addJSFile("js/autocomplete.js");
			// $this->addJSFile("js/search.js");

			// Add an array of channels in the form slug => id for the JavaScript to use.
			// $channels = array();
			// foreach ($channelInfo as $id => $c) $channels[$id] = $c["slug"];
			// $this->addJSVar("channels", $channels);

			// Get a bunch of statistics...
			// $queries = array(
			// 	"post" => ET::SQL()->select("COUNT(*)")->from("post")->get(),
			// 	"conversation" => ET::SQL()->select("COUNT(*)")->from("conversation")->get(),
			// 	"member" => ET::SQL()->select("COUNT(*)")->from("member")->get()
			// );
			// $sql = ET::SQL();
			// foreach ($queries as $k => $query) $sql->select("($query) AS $k");
			// $stats = $sql->exec()->firstRow();

			// // ...and show them in the footer.
			// foreach ($stats as $k => $v) {
			// 	$stat = Ts("statistic.$k", "statistic.$k.plural", number_format($v));
			// 	if ($k == "member") $stat = "<a href='".URL("members")."'>$stat</a>";
			// 	$this->addToMenu("statistics", "statistic-$k", $stat, array("before" => "statistic-online"));
			// }

			$this->layout->content = View::make('conversations/index', $data)->with('channel_tabs', $channels_view);


		}

		else
		{
			$this->layout->results = View::make('conversations/results', $data);
			$this->layout->channels = $channels_view;
		}

	}


	/**
	 * Given the channel slug from a request, work out which channels are
	 * selected, whether or not to include descendant channels in the
	 * results, and construct a full list of channel IDs to consider when
	 * getting the list of conversations.
	 *
	 * @param string $channelSlug The channel slug from the request.
	 * @return array An array containing:
	 * 		0 => a full list of channel information.
	 * 		1 => the list of currently selected channel IDs.
	 * 		2 => the full list of channel IDs to consider (including descendant channels of selected channels.)
	 * 		3 => whether or not descendant channels are being included.
	 */
	protected function get_selected_channels($channels, $channel_slug = "")
	{
		// Get a list of the currently selected channels.
		$selected_channels = array();
		$include_descendants = true;

		if (!empty($channel_slug))
		{
			$channel_slugs = explode(" ", $channel_slug);

			// If the first channel is empty (ie. the URL is conversations/+channel-slug), set a flag
			// to turn off the inclusion of descendant channels when considering conversations.
			if ($channel_slugs[0] == "") {
				$include_descendants = false;
				array_shift($channel_slugs);
			}

			// Go through the channels and add their IDs to the list of current channels.
			foreach ($channel_slugs as $slug)
			{
				foreach ($channels as $channel)
				{
					if ($channel->slug == $slug)
					{
						$selected_channels[] = $channel->id;
						break;
					}
				}
			}
		}

		$member_channels = Auth::user()->channels;

		// Get an array of channel IDs to consider when getting the list of conversations.
		// If we're not including descendants, this is the same as the list of current channels.
		if (!$include_descendants)
		{
			$channel_ids = $selected_channels;
		}

		// Otherwise, loop through all the channels and add IDs of descendants. Make sure we don't include
		// any channels which the user has unsubscribed to.
		else
		{
			$channel_ids = array();
			
			foreach ($selected_channels as $id)
			{
				$channel_ids[] = $id;

				// $root_unsubscribed = !empty($member_channels[$id]->unsubscribed);

				foreach ($channels as $channel)
				{
					if ($channel->lft > $channels[$id]->lft and $channel->rgt < $channels[$id]->rgt and empty($member_channels[$channel->id]->unsubscribed))
					{
						$channel_ids[] = $channel->id;
					}
				}
			}
		}

		// If by now we don't have any channel IDs, we must be viewing "all channels." In this case,
		// add all the channels.
		if (empty($channel_ids))
		{
			foreach ($channels as $id => $channel)
			{
				if (empty($member_channels[$id]->unsubscribed))
				{
					$channel_ids[] = $id;
				}
			}
		}

		return array($selected_channels, $channel_ids, $include_descendants);
	}


	/**
	 * Mark all conversations as read and return to the index page.
	 *
	 * @return void
	 */
	public function markAllAsRead()
	{
		// Update the user's preferences.
		ET::$session->setPreferences(array("markedAllConversationsAsRead" => time()));

		// For a normal response, redirect to the conversations page.
		if ($this->responseType === RESPONSE_TYPE_DEFAULT) $this->redirect(URL("conversations"));

		// For an ajax response, just pretend this is a normal search response.
		$this->index();
	}


	/**
	 * Return updated HTML for each row in the conversations table, and indicate if there are new results for the
	 * specified channel and search query.
	 *
	 * @param string $channelSlug The channel slug.
	 * @param string $query The search query.
	 * @return void
	 */
	public function update($channelSlug = "", $query = "")
	{
		// This must be done as an AJAX request.
		$this->responseType = RESPONSE_TYPE_AJAX;

		list($channelInfo, $currentChannels, $channelIds, $includeDescendants) = $this->getSelectedChannels($channelSlug);

		// Work out which conversations we need to get details for (according to the input value.)
		$conversationIds = explode(",", R("conversationIds"));

		// Make sure they are all integers.
		foreach ($conversationIds as $k => $v) {
			if (!($conversationIds[$k] = (int)$v)) unset($conversationIds[$k]);
		}

		if (!count($conversationIds)) return;

		// Get the full result data for these conversations, and construct an array of rendered conversation rows.
		$results = ET::searchModel()->getResults($conversationIds, true);
		$rows = array();
		foreach ($results as $conversation) {
			$rows[$conversation["conversationId"]] = $this->getViewContents("conversations/conversation", array("conversation" => $conversation, "channelInfo" => $channelInfo));
		}

		// Add that to the response.
		$this->json("conversations", $rows);

		// Now we need to work out if there are any new results for this channel/search query.

		// If the "random" gambit is in the search string, then don't go any further (because the results will
		// obviously differ!)
		$terms = $query ? explode("+", strtolower(str_replace("-", "+!", trim($query, " +-")))) : array();
		foreach ($terms as $v) {
			if (trim($v) == T("gambit.random"))	return;
		}

		// Get a list of conversation IDs for the channel/query.
		$newConversationIds = ET::searchModel()->getConversationIDs($channelIds, $query, count($currentChannels));
		$newConversationIds = array_slice((array)$newConversationIds, 0, 20);

		// Get the difference of the two sets of conversationId's.
		$diff = array_diff((array)$newConversationIds, (array)$conversationIds);
		if (count($diff)) $this->message(T("message.newSearchResults"));

		$this->render();
	}

}