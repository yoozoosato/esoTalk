<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * Conversation results. Displays a message if there are no results, or a conversation list and
 * footer if there are.
 *
 * @package esoTalk
 */

// If there are no conversations, show a message.
if ( ! count($conversations)): ?>
<div class='area noResults help'>
<h4><?php echo T("message.noSearchResults"); ?></h4>
<ul>
<li><?php echo T("message.reduceNumberOfGambits"); ?></li>
<?php if (!ET::$session->user): ?><li><?php echo T("message.logInToSeeAllConversations"); ?></li><?php endif; ?>
<li><?php echo T("message.fulltextKeywordWarning"); ?></li>
</ul>
</div>

<?php
// If there are conversations, however, show them!
else:
?>
<?php echo View::make('conversations/list', compact('conversations')); ?>

<div id='conversationsFooter'>

<?php if (Auth::check() and ! $selected_channels): ?>
<?php echo HTML::link('conversations/markAllAsRead', 'Mark all as read', array('class' => 'button markAllAsRead')); ?>
<?php endif;

if ($show_view_more_link): ?>
<div class='viewMore'>
<small><?php //echo sprintf(T("Your search found more than %s conversations."), C("esoTalk.search.results")); ?></small>
<a href='<?php //echo URL("conversations/".$data["channelSlug"]."?search=".urlencode($data["searchString"].($data["searchString"] ? " + " : "").T("gambit.more results"))); ?>' class='button'><?php //echo T("View more"); ?></a>
</div>
<?php endif; ?>

</div>
<?php endif; ?>