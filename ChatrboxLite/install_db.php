<?php

if (!defined('SMF'))
    die('Hacking attempt...');

$smcFunc['db_insert']('replace', '{db_prefix}scheduled_tasks', 
    array(
        'next_time' => 'int', 'time_offset' => 'int', 'time_regularity' => 'int',
        'time_unit' => 'string', 'disabled' => 'int', 'task' => 'string'
    ), 
    array(
        time() + 86400, 0, 1, 'd', 0, 'PruneChatrbox'
    ), 
    array('id_task')
);
  
db_extend('packages');

$smcFunc['db_create_table']('{db_prefix}chatrbox', 
    array(	
        array(
            'name' => 'id_message',
            'type' => 'int',		
            'auto' => true
        ),
        array(
            'name' => 'id_member',
            'type' => 'int' 
        ),
        array(
            'name' => 'message',
            'type' => 'text'  
        ),
        array(
            'name' => 'message_time',
            'type' => 'int'
        )
    ), 
    array(		
        array(			
            'name' => 'id_message',			
            'type' => 'primary',			
            'columns' => array('id_message'),		
        ),	
    ), array(), 'ignore');

$smcFunc['db_create_table']('{db_prefix}chatrbox_bans', 
    array(	
        array(
            'name' => 'id_member',
            'type' => 'int' 
        ),
        array(
            'name' => 'id_banned_by',
            'type' => 'int'  
        ),
        array(
            'name' => 'ban_time',
            'type' => 'int'
        )
    ), 
    array(		
        array(			
            'name' => 'id_member',			
            'type' => 'primary',			
            'columns' => array('id_member'),		
        ),	
    ), array(), 'ignore');

?>