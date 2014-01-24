<?php

if (!defined('SMF'))
	die('Hacking attempt...');

$hooks = array(
	'integrate_admin_areas' => 'integrateAdminAreasTOA',
	'integrate_modify_modifications' => 'integrateModifyModificationsTOA'
);

foreach ($hooks as $hook => $function)
	remove_integration_function($hook, $function);

?>