<?php

/**
 * WHMCS-SMF Integration
 *
 * @author Anthony Calandra
 * @copyright 2012 Anthony Calandra
 * @license LICENSE.txt BSD
 *
 * @version 1.0
 */

if (!defined("WHMCS"))
    die("This file cannot be accessed directly!");
    
require_once('SMFIntegration.php');

function whmcs_smf_integration_config() {
    $configArray = array(
        'name' => 'WHMCS-SMF Integration',
        'description' => 'Integrates features with the SMF 2.0 forum software. Please 
read the README.txt file in the module package!',
        'version' => '1.0',
        'author' => 'Anthony Calandra',
        'fields' => array(),
    );
    
    return $configArray;
}

function whmcs_smf_integration_activate() {
    
    global $CONFIG;
    
    $smf = SMF::getInstance();
    // Disable SMF registration, and add some nice settings
    $modSettings = $smf->getModSettings();
    if ($modSettings['registration_method'] != 3) {
        $smf->callSMFFunction('updateSettings', array(
            array(
                'registration_method' => 3,
                'whmcs_url' => $CONFIG['Domain'],
            ),
        ));
    }
    
    // Setup the custom field
    $smf->querySMFDatabase(
        'INSERT INTO {db_prefix}custom_fields
        VALUES (0, {string:col_name}, {string:field_name}, \'\', {string:field_type},
            255, \'\', {string:mask}, 0, 1, {string:show_profile}, 1, 1, 1, 0, \'\', \'\', 0)',
        array(
            'col_name' => 'cust_whmcsi',
            'field_name' => 'WHMCS Info',
            'field_type' => 'text',
            'mask' => 'nohtml',
            'show_profile' => 'none',
        )
    );

    // Disallow regular members from removing their SMF profiles
    $smf->querySMFDatabase(
        'DELETE FROM {db_prefix}permissions
        WHERE id_group = 0 AND permission = {string:perm}',
        array(
            'perm' => 'profile_remove_own',
        )
    );

    return array(
        'status' => 'success',
        'description' => 'Successfully activated! wat now...',
    );
}

function whmcs_smf_integration_deactivate() {
    $smf = SMF::getInstance();
    // Enable SMF registration to default setting
    $modSettings = $smf->getModSettings();
    $smf->callSMFFunction('updateSettings', array(
        array(
            'registration_method' => 0,
        ),
    ));
    
    // Reset SMF profile permission
    $smf->querySMFDatabase(
        'INSERT INTO {db_prefix}permissions
        VALUES(0, {string:perm}, 1)',
        array(
            'perm' => 'profile_remove_own',
        )
    );
    
    return array(
        'status' => 'success',
        'description' => 'Successfully deactivated!',
    );
}

function whmcs_smf_integration_upgrade($vars) {}

?>