<?php

if (!defined('SMF'))
	die('Hacking attempt...');

$hooks = array(
	'integrate_admin_areas' => 'integrateAdminAreasTOA',
	'integrate_modify_modifications' => 'integrateModifyModificationsTOA'
);

foreach ($hooks as $hook => $function)
	add_integration_function($hook, $function);

?>