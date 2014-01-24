<?php

if (!defined('SMF'))
    die('Hacking attempt...');

$mod_settings = array(
    'chatrboxNotice' => 'Welcome to Chatrbox! A shoutbox solution created by Anthony`. :)',
    'chatrboxMsgSizeLimit' => 50,
    'chatrboxRefreshRate' => 3000,
    'chatrboxShowOnlyIndex' => true
);

updateSettings($mod_settings);

$hooks = array(
    'integrate_pre_include' => '$sourcedir/Chatrbox.php',
    'integrate_theme_include' => '$boarddir/Themes/default/Chatrbox.template.php',
    'integrate_load_theme' => 'initChatrbox',
    'integrate_admin_areas' => 'integrateChatrboxAdminAreas',
    'integrate_modify_modifications' => 'integrateChatrboxModifyModifications',
    'integrate_actions' => 'integrateChatrboxController'
);

foreach ($hooks as $hook => $function)
    add_integration_function($hook, $function);

?>