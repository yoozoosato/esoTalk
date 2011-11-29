<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * Displays the conversation list - a table with each conversation as a row.
 *
 * @package esoTalk
 */
?>
<ul class='list conversationList'>

<?php
// Loop through the conversations and output a table row for each one.
foreach ($conversations as $conversation):
echo View::make('conversations/conversation', compact('conversation'));
endforeach;

?></ul>