<?php

if (!defined('SMF'))
	die('Hacking attempt...');

// Default settings
$mod_settings = array(
	'hfl_per_page' => 25,
    'hfl_custom_title' => 'Hot Forum Links',
    'hfl_num_index_pages' => 5,
);

updateSettings($mod_settings);
db_query("CREATE TABLE IF NOT EXISTS {$db_prefix}hot_forum_links(
    id_topic INT,
    member_added INT,
    timestamp INT,
    title TEXT,
    PRIMARY KEY(id_topic)
)", __FILE__, __LINE__);

?>