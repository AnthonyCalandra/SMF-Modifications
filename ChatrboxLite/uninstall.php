<?php

if (!defined('SMF'))
	die('Hacking attempt...');

$hooks = array(
    'integrate_pre_include' => '$sourcedir/Chatrbox.php',
    'integrate_theme_include' => '$boarddir/Themes/default/Chatrbox.template.php',
    'integrate_load_theme' => 'initChatrbox',
    'integrate_admin_areas' => 'integrateChatrboxAdminAreas',
    'integrate_modify_modifications' => 'integrateChatrboxModifyModifications',
    'integrate_actions' => 'integrateChatrboxController'
);

foreach ($hooks as $hook => $function)
	remove_integration_function($hook, $function);

?>