<?php namespace esoTalk;

Search::register_gambit('return $term == strtolower(T("gambits.starred"));', function($search, $term, $negate)
{
	if ( ! Auth::check()) return;

	$query = DB::table('members_conversations')
		->where('member_id', '=', Auth::user()->id)
		->where('starred', '=', 1);

	$search->add_id_filter($query, $negate);
});


Search::register_gambit('return $term == strtolower(T("gambits.muted"));', function($search, $term, $negate)
{
	if ( ! Auth::check() or $negate) return;

	$search->include_muted = true;

	$query = DB::table('members_conversations')
		->where('member_id', '=', Auth::user()->id)
		->where('muted', '=', 1);

	$search->add_id_filter($query);
});


Search::register_gambit('return $term == strtolower(T("gambits.draft"));', function($search, $term, $negate)
{
	if ( ! Auth::check()) return;

	$query = DB::table('members_conversations')
		->where('member_id', '=', Auth::user()->id)
		->where_not_null('draft');

	$search->add_id_filter($query, $negate);
});


Search::register_gambit('return $term == strtolower(T("gambits.private"));', function($search, $term, $negate)
{
	$search->query->where('private', '=', ($negate ? '0' : '1'));
});


Search::register_gambit('return $term == strtolower(T("gambits.sticky"));', function($search, $term, $negate)
{
	$search->query->where('sticky', '=', ($negate ? '0' : '1'));
});


Search::register_gambit('return $term == strtolower(T("gambits.locked"));', function($search, $term, $negate)
{
	$search->query->where('locked', '=', ($negate ? '0' : '1'));
});


Search::register_gambit('return strpos($term, strtolower(T("gambits.author:"))) === 0;', function($search, $term, $negate)
{
	// Get the name of the member.
	$username = trim(substr($term, strlen(T('gambits.author:'))));

	// If the user is referring to themselves, then we already have their member ID.
	if ($username == T('gambits.myself'))
	{
		$id = Auth::user()->id;
	}

	// Otherwise, make a query to find the member ID of the specified member name.
	else
	{
		$id = DB::raw('(SELECT id FROM members WHERE username = ?)', array($username));
	}

	// Apply the condition.
	$search->query->where('start_member_id', ($negate ? '!=' : '='), $id);
});


Search::register_gambit('return strpos($term, strtolower(T("gambits.contributor:"))) === 0;', function($search, $term, $negate)
{
	// Get the name of the member.
	$username = trim(substr($term, strlen(T("gambit.contributor:"))));

	// If the user is referring to themselves, then we already have their member ID.
	if ($username == T('gambits.myself'))
	{
		$id = Auth::user()->id;
	}

	// Otherwise, make a query to find the member ID of the specified member name.
	else
	{
		$id = DB::raw('(SELECT id FROM members WHERE username = ?)', array($username));
	}

	$query = DB::table('post')->where('member_id', '=', $id);

	$search->add_id_filter($query, $negate);
});


Search::register_gambit('return preg_match(T("gambits.gambitActive"), $term, $this->matches);', function($search, $term, $negate)
{
	$quantifier = $search->matches['a'];
	$amount = $search->matches['b'];
	$unit = $search->matches['c'];

	// Multiply the "amount" part (b) of the regular expression matches by the value of the "unit" part (c).
	switch ($unit)
	{
		case T('gambit.minute'): $amount *= 60; break;
		case T('gambit.hour'): $amount *= 3600; break;
		case T('gambit.day'): $amount *= 86400; break;
		case T('gambit.week'): $amount *= 604800; break;
		case T('gambit.month'): $amount *= 2626560; break;
		case T('gambit.year'): $amount *= 31536000;
	}

	// Set the 'quantifier' part (a); default to <= (i.e. 'last').
	$quantifier = (!$quantifier or $quantifier == T('gambit.last')) ? '<=' : $quantifier;

	// If the gambit is negated, use the inverse of the selected quantifier.
	if ($negate)
	{
		switch ($quantifier)
		{
			case '<': $quantifier = '>='; break;
			case '<=': $quantifier = '>'; break;
			case '>': $quantifier = '<='; break;
			case '>=': $quantifier = '<';
		}
	}

	// Apply the condition and force use of an index.
	$search->query->raw_where('? - ? '.$quantifier.' last_post_at', array(time(), $amount));

	// $search->sql->useIndex("conversation_lastPostTime");
});


Search::register_gambit('return preg_match(T("gambits.gambitHasNReplies"), $term, $this->matches);', function($search, $term, $negate)
{
	$quantifier = $search->matches['a'];
	$amount = $search->matches['b'];

	// Work out which quantifier to use; default to "=".
	$quantifier = $quantifier ?: '=';

	// If the gambit is negated, use the inverse of the quantifier.
	if ($negate) {
		switch ($quantifier) {
			case '<': $quantifier = '>='; break;
			case '<=': $quantifier = '>'; break;
			case '>': $quantifier = '<='; break;
			case '>=': $quantifier = '<'; break;
			case '=': $quantifier = '!=';
		}
	}

	// Increase the amount by one as we are checking replies, but the column in the conversations
	// table is a post count (it includes the original post.)
	$amount++;

	// Apply the condition.
	$search->query->raw_where('count_posts '.$quantifier.' ?', array($amount));
});


Search::register_gambit('return $term == strtolower(T("gambits.order by replies"));', function($search, $term, $negate)
{
	$search->query->order_by('count_posts', ($negate ? 'asc' : 'desc'));
	// $search->sql->useIndex("conversation_countPosts");
});


Search::register_gambit('return $term == strtolower(T("gambits.order by newest"));', function($search, $term, $negate)
{
	$search->query->order_by('created_at', ($negate ? 'asc' : 'desc'));
	// $search->sql->useIndex("conversation_startTime");
});


Search::register_gambit('return $term == strtolower(T("gambits.unread"));', function($search, $term, $negate)
{
	if ( ! Auth::check()) return;

	$read_query = DB::table('conversations')
		->join('members_conversations', 'members_conversations.conversation_id', '=', 'conversations.id')
		->where('members_conversations.last_read', '>=', 'conversations.count_posts')
		->where('members_conversations.member_id', '=', Auth::user()->id)
		->select('conversations.id');

	$search->query
		->raw_where('id NOT IN ('.$read_query->grammar->select($read_query).')')
		->where('last_post_at', '>=', Auth::user()->preferences['mark_all_as_read_time']);
});


Search::register_gambit('return $term == strtolower(T("gambits.reverse"));', function($search, $term, $negate)
{
	if ( ! $negate) $search->order_reverse = true;
});


Search::register_gambit('return $term == strtolower(T("gambits.more results"));', function($search, $term, $negate)
{
	if ( ! $negate) $search->query->take(C('esoTalk.search.moreResults'));
});



if ( ! C('esoTalk.search.disableRandomGambit'))
{
	Search::register_gambit('return $term == strtolower(T("gambit.random"));', function($search, $term, $negate)
	{
		if ( ! $negate) $search->query->order_by(DB::raw('RAND()'));
	});
}



Search::register_alias(T('gambits.active today'), T('gambits.active 1 day'));
Search::register_alias(T('gambits.has replies'), T('gambitss.has >0 replies'));
Search::register_alias(T('gambits.has no replies'), T('gambits.has 0 replies'));
Search::register_alias(T('gambits.dead'), T('gambits.active >30 day'));

?>