<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * Displays the channel breadcrumb tabs in the channel list and the conversations list.
 *
 * Works with a few things set in $data:
 * 		currentChannels: an array of currently selected channel IDs.
 * 		channelTabs: an array of channels which should be displayed as the current depth.
 * 		channelPath: an array of channels leading up to the current depth.
 *
 * @package esoTalk
 */

// Show the path leading up to the current channel depth. We output this as right to left (i.e. the deepest
// channel first, and so on until "all channels".) ?>
<li class='pathItem<?php if (isset($selected_channels)): ?> selected<?php endif; ?>'>
<?php if (!empty($parents)):
$parents = array_reverse($parents);
foreach ($parents as $channel): ?>
	<?php echo HTML::link_to_conversations($channel->title, array($channel->slug), array('data-channel' => $channel->slug, 'title' => strip_tags($channel->description), 'class' => 'channel-'.$channel->id)); ?>
<?php endforeach; ?>
<?php endif;

// Always show the "all channels" link. ?>
<?php echo HTML::link_to_conversations('All Channels', array('all'), array('data-channel' => 'all', 'class' => 'channel-all')); ?>
</li>

<?php
// Show the channels at the current depth.
if (!empty($siblings)): ?>
<?php foreach ($siblings as $channel): ?>
<li<?php if (in_array($channel->id, $selected_channels)): ?> class='selected'<?php endif; ?>>
<?php echo HTML::link_to_conversations($channel->title, array($channel->slug), array('title' => strip_tags($channel->description), 'class' => 'channel-'.$channel->id.(in_array($channel->id, $selected_channels) ? ' channel' : ''), 'data-channel' => $channel->slug)); ?></li>
<?php endforeach; ?>
<?php endif; ?>