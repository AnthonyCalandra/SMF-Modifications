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
 
// *** Modify this constant only if the installer cannot find the ROOT SMF directory ***
// No URL, EXACT FTP path only. Be sure to leave out /SSI.php in the path!
define('SSI_PATH', '');

class SMF {
    
    private static $instance = null;
    private $ssiPath = '';
    private $smcFunc = array();
    private $modSettings = array();
    private $txt = array();
    
    private function loadSMFSettings() {
        require_once($this->ssiPath . '/SSI.php');
        
        $this->smcFunc = $smcFunc;
        $this->modSettings = $modSettings;
        $this->txt = $txt;
    }
    
    public function formatUsername($clientFirstname, $clientLastname, $clientId) {
        $username = addslashes($clientFirstname . $clientLastname)
            . $clientId;
    
        // SMF doesnt accept a username longer than 25 characters by default,
        // So provide an alternative
        if (strlen($username) > 25)
            $username = addslashes($clientLastname) . $clientId;
            
        return $username;
    }
    
    public function querySMFDatabase($query, $params = array()) {
        // Bring SMF's $smcFunc functions into scope
        global $smcFunc;
        
        $this->loadSMFSettings();
        return $smcFunc['db_query']('', $query, $params);
    }
    
    public function callSMFFunction($func, $args) {
        $this->loadSMFSettings();
        require_once('smf_2_api.php');        
        return call_user_func_array('smfapi_' . $func, $args);
    }
    
    private function getSMFFiles() {
        // If there is no specified directory, attempt to find one
        if (SSI_PATH === '') {
            // Get some common directory names down
            foreach (array('smf', 'forum', 'forums', 'community') as $dirName) {
                $smfDir = dirname(ROOTDIR) . '/' . $dirName;
                if (file_exists($smfDir . '/SSI.php'))
                    return $smfDir;
            }
        } else {
            if (file_exists(SSI_PATH . '/SSI.php'))
                return SSI_PATH;
        }
        
        return false;
    }
    
    public function getSMFMemberDataById($memberId) {
        $memberData = array();
        $clientFirstname = '';
        $clientLastname = '';
        
        $request = mysql_query(
            'SELECT firstname, lastname
            FROM tblclients
            WHERE id = ' . $memberId . '
            LIMIT 1'
        );
        
        if (mysql_num_rows($request) > 0) {
            list($clientFirstname, $clientLastname) = mysql_fetch_row($request); 
        } else {
            mysql_free_result($request);
            return $memberData;
        }
        
        mysql_free_result($request);
        $username = $this->formatUsername($clientFirstname, $clientLastname, $memberId);
        $request = $this->querySMFDatabase(
            'SELECT id_member, member_name, id_group, email_address
            FROM {db_prefix}members
            WHERE member_name = {string:username}
            LIMIT 1',
            array(
                'username' => $username,
            )
        );
        
        while ($row = mysql_fetch_assoc($request)) {
            $memberData = array(
                'id' => $row['id_member'],
                'username' => $row['member_name'],
                'primary_group' => $row['id_group'],
                'email' => $row['email_address']
            );
        }
        
        mysql_free_result($request);
        return $memberData;
    }
    
    public function getSMFMemberDataByName($username) {
        $memberData = array();
        $request = $this->querySMFDatabase(
            'SELECT id_member, member_name, id_group, email_address
            FROM {db_prefix}members
            WHERE member_name = {string:username}
            LIMIT 1',
            array(
                'username' => $username,
            )
        );
        
        while ($row = mysql_fetch_assoc($request)) {
            $memberData = array(
                'id' => $row['id_member'],
                'username' => $row['member_name'],
                'primary_group' => $row['id_group'],
                'email' => $row['email_address']
            );
        }
        
        mysql_free_result($request);
        return $memberData;
    }
    
    public function getTxt() {
        return $this->txt;
    }
    
    public function getSmcFunc() {
        return $this->smcFunc;
    }
    
    public function getModSettings() {
        return $this->modSettings;
    }
    
    public static function getInstance() {
        if (self::$instance === null)
            self::$instance = new SMF();    
        return self::$instance;
    }
    
    private function __construct() {
        if ($this->ssiPath === '') {
            $this->ssiPath = $this->getSMFFiles();
            if (!$this->ssiPath)
                die('Unable to located SMF\'s root directory! Please modify the SSI_PATH 
constant defined in SMFIntegration.php (look around the top)! This constant should be a 
FULL path to the SMF directory where SSI.php is located (the root). Do not include the 
/SSI.php part!');
        }
    }
}

?>