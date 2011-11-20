<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * JSON master view. Displays messages and data collected in the controller as a JSON object.
 *
 * @package esoTalk
 */

unset($this->data['errors']);
$this->data['messages'] = $messages = Session::get('messages', array());

echo json_encode(compact(array_keys($this->data)));