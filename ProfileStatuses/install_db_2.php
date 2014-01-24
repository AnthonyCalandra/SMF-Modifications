<?php

if (!defined('SMF'))
	die('Hacking attempt...');

global $smcFunc;

db_extend('packages');

$smcFunc['db_add_column']("{db_prefix}members",
	array(
        	'name' => 'profilestatuses', 
        	'type' => 'int(1)', 
        	'null' => false,
        ), array(), 'do_nothing', ''
);
      
$smcFunc['db_create_table']('{db_prefix}log_statuses', array(	
	array(
		'name' => 'id_status',
		'type' => 'int',		
		'auto' => true,
	),
	array(
		'name' => 'id_member',
		'type' => 'int',		
		'auto' => false,
	),
	array(
		'name' => 'reply_count',
		'type' => 'int',		
		'auto' => false,
	),
	array(
		'name' => 'locked',
		'type' => 'tinyint(1)',		
		'auto' => false,
	),
	array(
		'name' => 'post_date',
		'type' => 'int',		
		'auto' => false,
	),
	array(
		'name' => 'post',
		'type' => 'text',		
		'auto' => false,
	)
), array(		
	array(			
		'name' => 'id_status',			
		'type' => 'primary',			
		'columns' => array('id_status'),		
	),	
), array(), 'ignore');

$smcFunc['db_create_table']('{db_prefix}log_status_replies', array(	
	array(
		'name' => 'id_reply',
		'type' => 'int',		
		'auto' => true,
	),
	array(
		'name' => 'id_member',
		'type' => 'int',		
		'auto' => false,
	),
	array(
		'name' => 'id_status',
		'type' => 'int',		
		'auto' => false,
	),
	array(
		'name' => 'reply_date',
		'type' => 'int',		
		'auto' => false,
	),
	array(
		'name' => 'reply',
		'type' => 'text',		
		'auto' => false,
	)
), array(		
	array(			
		'name' => 'id_reply',			
		'type' => 'primary',			
		'columns' => array('id_reply'),		
	),	
), array(), 'ignore');

?>