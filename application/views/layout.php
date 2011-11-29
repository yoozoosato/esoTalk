<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
* Default master view. Displays a HTML template with a header and footer.
*
* @package esoTalk
*/
?>
<!DOCTYPE html>
<html>
<head>
<?php echo Page::get_head(); ?>
</head>

<body class="<?php echo Page::get_body_class(); ?>">

<div id="messages">
<?php foreach (Page::get_flash() as $message): ?>
<div class="messageWrapper">
<div class="message <?php echo $message['className']; ?>"<?php if (!empty($message['id'])): ?> data-id="<?php echo $message['id']; ?>"<?php endif; ?>><?php echo $message['message']; ?></div>
</div>
<?php endforeach; ?>
</div>

<div id="wrapper">

<!-- HEADER -->
<div id="hdr">
<div id="hdr-content">

<h1 id="forumTitle"><?php echo HTML::link('', Page::get_logo()); ?></h1>

<ul id="mainMenu" class="menu">
<?php if ($back = Page::get_back_button()): ?>
<li><a href="<?php echo $back['url']; ?>" id="backButton">&laquo;<span> <?php echo "Back to ".$back['type']; ?><span></a></li>
<?php endif; ?>
<?php echo Menu::get_main(); ?>
</ul>


<ul id="userMenu" class="menu">
<?php echo Menu::get_user(); ?>
<li><?php echo HTML::link('conversation/start', 'New Conversation', array('class' => 'link-newConversation button')); ?></li>
</ul>

</div>
</div>

<!-- BODY -->
<div id="body">
<div id="body-content">
<?php echo $body; ?>
</div>
</div>

<!-- FOOTER -->
<div id="ftr">
<div id="ftr-content">
<ul class="menu">
<li id="goToTop"><?php echo HTML::link(Request::uri()."#", T("general.go_to_top")); ?></li>
<?php echo Menu::get_meta(); ?>
<?php echo Menu::get_statistics(); ?>
</ul>
</div>
</div>

</div>

</body>
</html>