<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * Displays a login sheet/form.
 *
 * @package esoTalk
 */

?>
<div id="loginSheet" class="sheet">
<div class="sheetContent">

<h3><?php echo T('general.log_in'); ?></h3>

<?php echo Form::open(); ?>
<?php echo Form::token(); ?>
<?php echo Form::hidden('return', Input::get('return')); ?>

<?php Event::trigger('login_form_before'); ?>

<div class="section">

<ul class="form">

<li>
<label><?php echo T('general.username_or_email'); ?></label>
<?php echo Form::text('username', Input::get('username')); ?>
</li>

<li>
<label><?php echo T('general.password'); ?> <small><?php echo HTML::link('user/forgot', T('general.forgot'), array('class' => 'link-forgot', 'tabindex' => '-1')); ?></small></label>
<?php echo Form::password('password'); ?>
</li>

<li>
<div class="checkboxGroup">
<label class="checkbox"><?php echo Form::checkbox('remember', '1', Input::get('remember')), T('general.keep_me_logged_in'); ?></label>
</div>
</li>

</ul>

</div>

<?php Event::trigger('login_form_after'); ?>

<div class="buttons">
<small><?php //printf(T("Don't have an account? <a href='%s' class='link-join'>Sign up!</a>"), URL("user/join")); ?></small>
<?php
echo Form::submit(T('general.log_in'), array('class' => 'button'));
echo HTML::link(Input::get('return'), 'Cancel', array('class' => 'button'));
?>
</div>

<?php echo Form::close(); ?>

</div>
</div>