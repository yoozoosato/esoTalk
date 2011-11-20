<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * Displays a modal message sheet. Used by ETController::renderMessage().
 *
 * @package esoTalk
 */
?>
<div class='sheet' id='messageSheet'>
<div class='sheetContent'>

<h3><?php echo $title; ?></h3>

<div class='section help'><?php echo $message; ?></div>

<div class='buttons'>
<?php echo HTML::link(Input::get('return'), 'OK', array('class' => 'button')); ?>
</div>

</div>
</div>