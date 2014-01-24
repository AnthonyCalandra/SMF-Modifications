<?php

if (!defined('SMF'))
	die('Hacking attempt...');

$hooks = array(
	'integrate_bbc_buttons' => 'disable_status_bbc',
);

foreach ($hooks as $hook => $function)
	add_integration_function($hook, $function);
	
$mod_settings = array(
	'disabled_status_bbc' => implode(',', array('flash', 'img', 'table', 'code', 'quote', 'list', 'orderlist', 'hr')),
	'profile_statuses_limit_pages' => 3,
	'profile_statuses_maxlen' => 50,
);

updateSettings($mod_settings);

?>