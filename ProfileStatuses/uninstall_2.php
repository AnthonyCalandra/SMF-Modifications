<?php

if (!defined('SMF'))
	die('Hacking attempt...');
	
global $modSettings;

$hooks = array(
	'integrate_bbc_buttons' => 'disable_status_bbc',
);

foreach ($hooks as $hook => $function)
	remove_integration_function($hook, $function);

?>