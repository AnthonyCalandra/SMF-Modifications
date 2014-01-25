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

function whmcs_smf_integration_hook_create_smf_account($vars) {
    
    global $CONFIG;
    
    $smf = SMF::getInstance();
    $username = $smf->formatUsername($vars['firstname'], $vars['lastname'],
        $vars['userid']);
    
    $modSettings = $smf->getModSettings();
   	$regOptions = array(
  		'member_name' => $username,
  		'email' => $vars['email'],
  		'password' => $vars['password'],
  		'password_check' => $vars['password'],
  		'openid' => '',
  		'auth_method' => '',
  		'check_reserved_name' => true,
  		'check_password_strength' => false,
  		'check_email_ban' => false,
  		'send_welcome_email' => !empty($modSettings['send_welcomeEmail']),
  		'require' => 'nothing',
   	);
    $memberId = $smf->callSMFFunction('registerMember', array($regOptions, true));
    if (!$memberId)
        return;
    
    // Insert custom field data
    $smf->querySMFDatabase(
        'INSERT INTO {db_prefix}themes
        VALUES ({int:id_member}, 1, {string:var}, {string:value})',
        array(
            'id_member' => $memberId,
            'var' => 'cust_whmcsi',
            'value' => '[url=' . $CONFIG['Domain'] . '/admin/clientssummary.php?userid=' . $vars['userid'] .']
[img]' . $CONFIG['Domain'] . '/admin/images/icons/clients.png[/img][/url]'
        )
    );
}

function whmcs_smf_integration_hook_modify_smf_account($vars) {
    $smf = SMF::getInstance();
    $oldUsername = $smf->formatUsername($vars['olddata']['firstname'], 
        $vars['olddata']['lastname'], $vars['olddata']['userid']);
    $oldMemberData = $smf->getSMFMemberDataByName($oldUsername);
    if (empty($oldMemberData))
        return;

    $newUsername = $smf->formatUsername($vars['firstname'], $vars['lastname'], 
        $vars['userid']);

    $smf->callSMFFunction('updateMemberData', array(
        $oldMemberData['id'],
        array(
            'member_name' => $newUsername,
            'real_name' => $newUsername,
            'email_address' => $vars['email'],
        )
    ));
}

function whmcs_smf_integration_hook_delete_smf_account($vars) {
    $smf = SMF::getInstance();
    $memberData = $smf->getSMFMemberDataById($vars['userid']);
    if (empty($memberData))
        return;

    $memberId = $memberData['id'];
    $smf->callSMFFunction('deleteMembers', array($memberId));
    
    // Delete custom field data
    $smf->querySMFDatabase(
        'DELETE FROM {db_prefix}custom_fields
        WHERE id_member = {int:id_member}
            AND variable = {string:variable}',
        array(
            'id_member' => $memberData['id'],
            'variable' => 'cust_whmcsi',
        )
    );
}

add_hook('ClientAdd', 1, 'whmcs_smf_integration_hook_create_smf_account');
add_hook('ClientEdit', 1, 'whmcs_smf_integration_hook_modify_smf_account');
add_hook('ClientDelete', 1, 'whmcs_smf_integration_hook_delete_smf_account');

?>