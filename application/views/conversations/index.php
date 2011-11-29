<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * Displays the conversation list, including the filter area (search form, gambits, and channel breadcrumb.)
 *
 * @package esoTalk
 */
?>

<div id="conversationsFilter" class="bodyHeader">

<?php echo Form::open(null, 'get', array('class' => 'search big', 'id' => 'search')); ?>
<fieldset>
<?php echo Form::text('search', $search_string, array('spellcheck' => 'false', 'placeholder' => T('Filter conversations...'))); ?>
<?php echo HTML::link_to_conversations('x', array($channel_slug), array('class' => 'control-reset')); ?>
</fieldset>
<?php echo Form::close(); ?>

<ul id="channels" class="channels tabs">
<li><?php echo HTML::link_to_channels(('Channel List'), array(), array('class' => 'channel-list', 'title' => ('Channel List'))); ?></li>
<?php echo $channel_tabs; ?>
</ul>

<div id="gambits">
<p class="help"><?php echo T('message.gambitsHelp'); ?></p>
<?php
$url_prefix = URL::to_conversations(array($channel_slug)).'?search='.urlencode(!empty($search_string) ? $search_string.' + ' : '');
ksort($gambits);
foreach ($gambits as $gambit => $class):
	echo HTML::link($url_prefix.$gambit, $gambit, array('class' => $class));
endforeach;
?>
</div>

</div>

<div id="conversations">
<?php echo View::make('conversations/results', compact('conversations', 'show_view_more_link')); ?>
</div>