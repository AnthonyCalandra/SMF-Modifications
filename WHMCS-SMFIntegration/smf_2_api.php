<?php

/**
 * Simple Machines Forum(SMF) API for SMF 2.0
 *
 * Use this to integrate your SMF version 2.0 forum with 3rd party software
 * If you need help using this script or integrating your forum with other
 * software, feel free to contact andre@r2bconcepts.com
 *
 * @package   SMF 2.0 API
 * @author    Simple Machines http://www.simplemachines.org
 * @author    Andre Nickatina <andre@r2bconcepts.com>
 * @copyright 2011 Simple Machines
 * @link      http://www.simplemachines.org Simple Machines
 * @link      http://www.r2bconcepts.com Red2Black Concepts
 * @license   http://www.simplemachines.org/about/smf/license.php BSD
 * @version   0.1.2
 *
 * NOTICE OF LICENSE
 ***********************************************************************************
 * This file, and ONLY this file is released under the terms of the BSD License.   *
 *                                                                                 *
 * Redistribution and use in source and binary forms, with or without              *
 * modification, are permitted provided that the following conditions are met:     *
 *                                                                                 *
 * Redistributions of source code must retain the above copyright notice, this     *
 * list of conditions and the following disclaimer.                                *
 * Redistributions in binary form must reproduce the above copyright notice, this  *
 * list of conditions and the following disclaimer in the documentation and/or     *
 * other materials provided with the distribution.                                 *
 * Neither the name of Simple Machines LLC nor the names of its contributors may   *
 * be used to endorse or promote products derived from this software without       *
 * specific prior written permission.                                              *
 *                                                                                 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"     *
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE       *
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE      *
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE        *
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR             *
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE *
 * GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)     *
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT      *
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT   *
 * OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. *
 **********************************************************************************/
 
 /**
    Modified by Anthony`/Anthony Calandra.
 */

// Don't do anything if SMF is not loaded
if (!defined('SMF'))
	return false;

/**
 * Delete members
 *
 * Delete a member or an array of members by member id
 *
 * @param  int || int array $users the member id(s)
 * @return bool true when complete or false if user array empty
 * @since  0.1.0
 */
function smfapi_deleteMembers($users)
{
	global $sourcedir, $modSettings, $user_info, $smcFunc;

	// try give us a while to sort this out...
	@set_time_limit(600);
	// try to get some more memory
	if (@ini_get('memory_limit') < 128) {
		@ini_set('memory_limit', '128M');
    }

	// if it's not an array, make it so
	if (!is_array($users)) {
		$users = array($users);
    } else {
		$users = array_unique($users);
    }
    
    foreach ($users as &$user) {
        if (!is_int($user)) {
            $data = smfapi_getUserData($user);
            $user = $data['id_member'] + 0;
        }
    }

	// make sure there's no void user in here
	$users = array_diff($users, array(0));

	if (empty($users)) {
		return false;
    }

	// make these peoples' posts guest posts
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}messages
		SET id_member = {int:guest_id}, poster_email = {string:blank_email}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'blank_email' => '',
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}polls
		SET id_member = {int:guest_id}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	// make these peoples' posts guest first posts and last posts
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}topics
		SET id_member_started = {int:guest_id}
		WHERE id_member_started IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}topics
		SET id_member_updated = {int:guest_id}
		WHERE id_member_updated IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}log_actions
		SET id_member = {int:guest_id}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}log_banned
		SET id_member = {int:guest_id}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}log_errors
		SET id_member = {int:guest_id}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	// delete the member
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}members
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	// delete the logs...
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_actions
		WHERE id_log = {int:log_type}
			AND id_member IN ({array_int:users})',
		array(
			'log_type' => 2,
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_boards
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_comments
		WHERE id_recipient IN ({array_int:users})
			AND comment_type = {string:warntpl}',
		array(
			'users' => $users,
			'warntpl' => 'warntpl',
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_group_requests
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_karma
		WHERE id_target IN ({array_int:users})
			OR id_executor IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_mark_read
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_notify
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_online
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_subscribed
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_topics
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}collapsed_categories
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	// make their votes appear as guest votes - at least it keeps the totals right
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}log_polls
		SET id_member = {int:guest_id}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	// delete personal messages
	smfapi_deleteMessages(null, null, $users);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}personal_messages
		SET id_member_from = {int:guest_id}
		WHERE id_member_from IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'users' => $users,
		)
	);

	// they no longer exist, so we don't know who it was sent to
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}pm_recipients
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	// it's over, no more moderation for you
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}moderators
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}group_moderators
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	// if you don't exist we can't ban you
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}ban_items
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	// remove individual theme settings
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}themes
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);

	// I'm not your buddy, chief
	$request = $smcFunc['db_query']('', '
		SELECT id_member, pm_ignore_list, buddy_list
		FROM {db_prefix}members
		WHERE FIND_IN_SET({raw:pm_ignore_list}, pm_ignore_list) != 0 OR FIND_IN_SET({raw:buddy_list}, buddy_list) != 0',
		array(
			'pm_ignore_list' => implode(', pm_ignore_list) != 0 OR FIND_IN_SET(', $users),
			'buddy_list' => implode(', buddy_list) != 0 OR FIND_IN_SET(', $users),
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request)) {
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}members
			SET
				pm_ignore_list = {string:pm_ignore_list},
				buddy_list = {string:buddy_list}
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => $row['id_member'],
				'pm_ignore_list' => implode(',', array_diff(explode(',', $row['pm_ignore_list']), $users)),
				'buddy_list' => implode(',', array_diff(explode(',', $row['buddy_list']), $users)),
			)
		);
    }

	$smcFunc['db_free_result']($request);

	// make sure no member's birthday is still sticking in the calendar...
	smfapi_updateSettings(array(
		'calendar_updated' => time(),
	));

	smfapi_updateStats('member');

	return true;
}

/**
 * Gets the user's info
 *
 * Will take the users email, username or member id and return their data
 *
 * @param  int || string $username the user's email address username or member id
 * @return array $results containing the user info || bool false
 * @since  0.1.0
 */
function smfapi_getUserData($username='')
{
    if ('' == $username) {
        return false;
    }

    $user_data = array();

    // we'll try id || email, then username
    if (is_numeric($username)) {
        // number is most likely a member id
        $user_data = smfapi_getUserById($username);
    } else {
        // the email can't be an int
        $user_data = smfapi_getUserByEmail($username);
    }

    if (!$user_data) {
        $user_data = smfapi_getUserByUsername($username);
    }

    if (empty($user_data)) {
        return false;
    } else {
        return $user_data;
    }
}

/**
 * Gets the user's info from their member name (username)
 *
 * Will take the users member name and return an array containing all the
 * user's information in the db. Will return false on failure
 *
 * @param  string $username the user's member name
 * @return array $results containing the user info || bool false
 * @since  0.1.0
 */
function smfapi_getUserByUsername($username='')
{
    global $smcFunc;

    if ('' == $username || !is_string($username)) {
        return false;
    }

    $request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}members
			WHERE member_name = {string:member_name}
			LIMIT 1',
			array(
				'member_name' => $username,
			)
		);
	$results = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

    if (empty($results)) {
        return false;
	} else {
	    // return all the results.
	    return $results;
    }
}

/**
 * Gets the user's info from their member id
 *
 * Will take the users member id and return an array containing all the
 * user's information in the db. Will return false on failure
 *
 * @param  int $id the user's member id
 * @return array $results containing the user info || bool false
 * @since  0.1.0
 */
function smfapi_getUserById($id='')
{
    global $smcFunc;

    if ('' == $id || !is_numeric($id)) {
        return false;
    } else{
        $id += 0;
        if (!is_int($id)) {
            return false;
        }
    }

    $request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}members
			WHERE id_member = {int:id_member}
			LIMIT 1',
			array(
				'id_member' => $id,
			)
		);
	$results = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

    if (empty($results)) {
        return false;
	} else {
	    // return all the results.
	    return $results;
    }
}

/**
 * Gets the user's info from their email address
 *
 * Will take the users email address and return an array containing all the
 * user's information in the db. Will return false on failure
 *
 * @param  string $email the user's email address
 * @return array $results containing the user info || bool false
 * @since  0.1.0
 */
function smfapi_getUserByEmail($email='')
{
    global $smcFunc;

    if ('' == $email || !is_string($email) || 2 > count(explode('@', $email))) {
        return false;
    }

    $request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}members
			WHERE email_address = {string:email_address}
			LIMIT 1',
			array(
				'email_address' => $email,
			)
		);
	$results = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

    if (empty($results)) {
        return false;
	} else {
	    // return all the results.
	    return $results;
    }
}

/**
 * Generate validation code
 *
 * Generate a random validation code for registration purposes
 *
 * @return string random validation code (10 char)
 * @since  0.1.0
 */
function smfapi_generateValidationCode()
{
	global $smcFunc, $modSettings;

	$request = $smcFunc['db_query']('get_random_number', '
		SELECT RAND()',
		array(
		)
	);

	list ($dbRand) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return substr(preg_replace('/\W/', '', sha1(microtime()
           . mt_rand() . $dbRand . $modSettings['rand_seed'])), 0, 10);
}

/**
 * Register a member
 *
 * Register a new member with SMF
 *
 * @param  array $regOptions the registration options
 * @return int $memberId the user's member id || bool false
 * @since  0.1.0
 */
function smfapi_registerMember($regOptions)
{
	global $scripturl, $modSettings, $sourcedir;
	global $user_info, $options, $settings, $smcFunc;

    $reg_errors = array();

	// check username
	if (empty($regOptions['member_name'])) {
		$reg_errors[] = 'username empty';
    }

    if (false !== smfapi_getUserbyUsername($regOptions['member_name'])) {
        $reg_errors[] = 'username taken';
    }

	// check email
	if (empty($regOptions['email'])
        || preg_match('~^[0-9A-Za-z=_+\-/][0-9A-Za-z=_\'+\-/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$~', $regOptions['email']) === 0
        || strlen($regOptions['email']) > 255) {
		    $reg_errors[] = 'email invalid';
    }

    if (false !== smfapi_getUserbyEmail($regOptions['email'])) {
        $reg_errors[] = 'email already in use';
    }

	// generate a validation code if it's supposed to be emailed
	// unless there was one passed in for us to use
	$validation_code = '';
	if (!isset($regOptions['require']) || empty($regOptions['require'])) {
        //we need to set it to something...
        $regOptions['require'] = 'nothing';
	}
	if ($regOptions['require'] == 'activation') {
        if (isset($regOptions['validation_code'])) {
		    $validation_code = $regOptions['validation_code'];
		} else {
            $validation_code = smfapi_generateValidationCode();
		}
    }

    if (!isset($regOptions['password_check']) || empty($regOptions['password_check'])) {
        //make them match if the check wasn't set or it will fail the comparison below
        $regOptions['password_check'] = $regOptions['password'];
    }
	if ($regOptions['password'] != $regOptions['password_check']) {
        $reg_errors[] = 'password check failed';
    }

	// password empty is an error
	if ('' == $regOptions['password']) {
        $reg_errors[] = 'password empty';
	}

	// if there's any errors left return them at once
	if (!empty($reg_errors)) {
		return $reg_errors;
    }

	// some of these might be overwritten (the lower ones that are in the arrays below)
	$regOptions['register_vars'] = array(
		'member_name' => $regOptions['member_name'],
		'email_address' => $regOptions['email'],
		'passwd' => sha1(strtolower($regOptions['member_name']) . $regOptions['password']),
		'password_salt' => substr(md5(mt_rand()), 0, 4) ,
		'posts' => 0,
		'date_registered' => time(),
		'member_ip' => $user_info['ip'],
		'member_ip2' => isset($_SERVER['BAN_CHECK_IP'])?$_SERVER['BAN_CHECK_IP']:'',
		'validation_code' => $validation_code,
		'real_name' => isset($regOptions['real_name'])?$regOptions['real_name']:$regOptions['member_name'],
		'personal_text' => $modSettings['default_personal_text'],
		'pm_email_notify' => 1,
		'id_theme' => 0,
		'id_post_group' => 4,
		'lngfile' => isset($regOptions['lngfile'])?$regOptions['lngfile']:'',
		'buddy_list' => '',
		'pm_ignore_list' => '',
		'message_labels' => '',
		'website_title' => isset($regOptions['website_title'])?$regOptions['website_title']:'',
		'website_url' => isset($regOptions['website_url'])?$regOptions['website_url']:'',
		'location' => isset($regOptions['location'])?$regOptions['location']:'',
		'icq' => isset($regOptions['icq'])?$regOptions['icq']:'',
		'aim' => isset($regOptions['aim'])?$regOptions['aim']:'',
		'yim' => isset($regOptions['yim'])?$regOptions['yim']:'',
		'msn' => isset($regOptions['msn'])?$regOptions['msn']:'',
		'time_format' => isset($regOptions['time_format'])?$regOptions['time_format']:'',
		'signature' => isset($regOptions['signature'])?$regOptions['signature']:'',
		'avatar' => isset($regOptions['avatar'])?$regOptions['avatar']:'',
		'usertitle' => '',
		'secret_question' => isset($regOptions['secret_question'])?$regOptions['secret_question']:'',
		'secret_answer' => isset($regOptions['secret_answer'])?$regOptions['secret_answer']:'',
		'additional_groups' => '',
		'ignore_boards' => '',
		'smiley_set' => '',
		'openid_uri' => isset($regOptions['openid_uri'])?$regOptions['openid_uri']:'',
	);

	// maybe it can be activated right away?
	if ($regOptions['require'] == 'nothing')
		$regOptions['register_vars']['is_activated'] = 1;
	// maybe it must be activated by email?
	elseif ($regOptions['require'] == 'activation')
		$regOptions['register_vars']['is_activated'] = 0;
	// otherwise it must be awaiting approval!
	else
		$regOptions['register_vars']['is_activated'] = 3;

	if (isset($regOptions['memberGroup']))
	{
        // make sure the id_group will be valid, if this is an administator
		$regOptions['register_vars']['id_group'] = $regOptions['memberGroup'];

		// check if this group is assignable
		$unassignableGroups = array(-1, 3);
		$request = $smcFunc['db_query']('', '
			SELECT id_group
			FROM {db_prefix}membergroups
			WHERE min_posts != {int:min_posts}' . '
				OR group_type = {int:is_protected}',
			array(
				'min_posts' => -1,
				'is_protected' => 1,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$unassignableGroups[] = $row['id_group'];
        }

		$smcFunc['db_free_result']($request);

		if (in_array($regOptions['register_vars']['id_group'], $unassignableGroups)) {
			$regOptions['register_vars']['id_group'] = 0;
        }
	}

	// integrate optional user theme options to be set
	$theme_vars = array();

	if (!empty($regOptions['theme_vars'])) {
		foreach ($regOptions['theme_vars'] as $var => $value) {
			$theme_vars[$var] = $value;
        }
    }

	// right, now let's prepare for insertion
	$knownInts = array(
		'date_registered', 'posts', 'id_group', 'last_login', 'instant_messages', 'unread_messages',
		'new_pm', 'pm_prefs', 'gender', 'hide_email', 'show_online', 'pm_email_notify', 'karma_good', 'karma_bad',
		'notify_announcements', 'notify_send_body', 'notify_regularity', 'notify_types',
		'id_theme', 'is_activated', 'id_msg_last_visit', 'id_post_group', 'total_time_logged_in', 'warning',
	);
	$knownFloats = array(
		'time_offset',
	);

	$column_names = array();
	$values = array();

	foreach ($regOptions['register_vars'] as $var => $val) {
		$type = 'string';
		if (in_array($var, $knownInts)) {
			$type = 'int';
        } elseif (in_array($var, $knownFloats)) {
			$type = 'float';
        } elseif ($var == 'birthdate') {
			$type = 'date';
        }

		$column_names[$var] = $type;
		$values[$var] = $val;
	}

	// register them into the database
	$smcFunc['db_insert']('',
		'{db_prefix}members',
		$column_names,
		$values,
		array('id_member')
	);

	$memberID = $smcFunc['db_insert_id']('{db_prefix}members', 'id_member');

	// update the number of members and latest member's info - and pass the name, but remove the 's
	if ($regOptions['register_vars']['is_activated'] == 1) {
		smfapi_updateStats('member', $memberID, $regOptions['register_vars']['real_name']);
    } else {
		smfapi_updateStats('member');
    }

	// theme variables too?
	if (!empty($theme_vars)) {
		$inserts = array();
		foreach ($theme_vars as $var => $val) {
			$inserts[] = array($memberID, $var, $val);
        }
		$smcFunc['db_insert']('insert',
			'{db_prefix}themes',
			array('id_member' => 'int', 'variable' => 'string-255', 'value' => 'string-65534'),
			$inserts,
			array('id_member', 'variable')
		);
	}

	// okay, they're for sure registered... make sure the session is aware of this for security
	$_SESSION['just_registered'] = 1;

	return $memberID;
}

/**
 * Put data in the cache
 *
 * Adds data to whatever cache method we're using
 *
 * @param  string $key the cache data identifier
 * @param  mixed $value the value to be stored
 * @param  int $ttl how long are we going to cache this data (in seconds)
 * @return void
 * @since  0.1.0
 */
function smfapi_cachePutData($key, $value, $ttl = 120)
{
	global $boardurl, $sourcedir, $modSettings, $memcached;
	global $cache_hits, $cache_count, $db_show_debug, $cachedir;

	if (empty($modSettings['cache_enable']) && !empty($modSettings)) {
		return;
    }

	$cache_count = isset($cache_count) ? $cache_count + 1 : 1;

	if (isset($db_show_debug) && $db_show_debug === true) {
		$cache_hits[$cache_count] = array('k' => $key,
                                          'd' => 'put',
                                          's' => $value === null ? 0 : strlen(serialize($value)));
		$st = microtime();
	}

	$key = md5($boardurl . filemtime($sourcedir . '/Load.php'))
           . '-SMF-' . strtr($key, ':', '-');
	$value = $value === null ? null : serialize($value);

	// eAccelerator...
	if (function_exists('eaccelerator_put')) {
		if (mt_rand(0, 10) == 1) {
			eaccelerator_gc();
        }

		if ($value === null) {
			@eaccelerator_rm($key);
        } else {
			eaccelerator_put($key, $value, $ttl);
        }
	}
	// turck MMCache?
	elseif (function_exists('mmcache_put')) {
		if (mt_rand(0, 10) == 1) {
			mmcache_gc();
        }

		if ($value === null) {
			@mmcache_rm($key);
        } else {
			mmcache_put($key, $value, $ttl);
        }
	}
	// alternative PHP Cache, ahoy!
	elseif (function_exists('apc_store')) {
		// An extended key is needed to counteract a bug in APC.
		if ($value === null) {
			apc_delete($key . 'smf');
        } else {
			apc_store($key . 'smf', $value, $ttl);
        }
	}
	// zend Platform/ZPS/etc.
	elseif (function_exists('output_cache_put')) {
		output_cache_put($key, $value);
    } elseif (function_exists('xcache_set') && ini_get('xcache.var_size') > 0) {
		if ($value === null) {
			xcache_unset($key);
        } else {
			xcache_set($key, $value, $ttl);
        }
	}
	// otherwise custom cache?
	else {
		if ($value === null) {
			@unlink($cachedir . '/data_' . $key . '.php');
        } else {
			$cache_data = '<' . '?' . 'php if (!defined(\'SMF\')) die; if ('
                          . (time() + $ttl)
                          . ' < time()) $expired = true; else{$expired = false; $value = \''
                          . addcslashes($value, '\\\'') . '\';}' . '?' . '>';
			$fh = @fopen($cachedir . '/data_' . $key . '.php', 'w');

			if ($fh) {
				// write the file.
				set_file_buffer($fh, 0);
				flock($fh, LOCK_EX);
				$cache_bytes = fwrite($fh, $cache_data);
				flock($fh, LOCK_UN);
				fclose($fh);

				// check that the cache write was successful; all the data should be written
				// if it fails due to low diskspace, remove the cache file
				if ($cache_bytes != strlen($cache_data)) {
					@unlink($cachedir . '/data_' . $key . '.php');
                }
			}
		}
	}

	if (isset($db_show_debug) && $db_show_debug === true) {
		$cache_hits[$cache_count]['t'] = array_sum(explode(' ', microtime())) - array_sum(explode(' ', $st));
    }

    return;
}

/**
 * Update member data
 *
 * Update member data such as 'passwd' (hash), 'email_address', 'is_activated'
 * 'password_salt', 'member_name' or any other user info in the db
 *
 * @param  int $member member id (will also work with string username or email)
 * @param  associative array $data the data to be updated (htmlspecialchar'd)
 * @return bool whether update was successful or not
 * @since  0.1.0
 */
function smfapi_updateMemberData($member='', $data='')
{
	global $modSettings, $user_info, $smcFunc;

    if ('' == $member || '' == $data) {
        return false;
    }

    $user_data = smfapi_getUserData($member);

    if (!$user_data) {
        $member = $user_info['id'];
    } elseif (isset($user_data['id_member'])) {
        $member = $user_data['id_member'];
    } else {
        return false;
    }

	$parameters = array();
    $condition = 'id_member = {int:member}';
    $parameters['member'] = $member;

	// everything is assumed to be a string unless it's in the below.
    $knownInts = array(
		'date_registered', 'posts', 'id_group', 'last_login', 'instant_messages', 'unread_messages',
		'new_pm', 'pm_prefs', 'gender', 'hide_email', 'show_online', 'pm_email_notify', 'pm_receive_from', 'karma_good', 'karma_bad',
		'notify_announcements', 'notify_send_body', 'notify_regularity', 'notify_types',
		'id_theme', 'is_activated', 'id_msg_last_visit', 'id_post_group', 'total_time_logged_in', 'warning',
	);
	$knownFloats = array(
		'time_offset',
	);

	$setString = '';
	foreach ($data as $var => $val) {
		$type = 'string';
		if (in_array($var, $knownInts)) {
			$type = 'int';
        } elseif (in_array($var, $knownFloats)) {
			$type = 'float';
        }

		$setString .= ' ' . $var . ' = {' . $type . ':p_' . $var . '},';
		$parameters['p_' . $var] = $val;
	}

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}members
		SET' . substr($setString, 0, -1) . '
		WHERE ' . $condition,
		$parameters
	);

	// clear any caching?
	if (!empty($modSettings['cache_enable']) && $modSettings['cache_enable'] >= 2
        && !empty($members)) {

        if ($modSettings['cache_enable'] >= 3) {
            smfapi_cachePutData('member_data-profile-' . $member, null, 120);
            smfapi_cachePutData('member_data-normal-' . $member, null, 120);
            smfapi_cachePutData('member_data-minimal-' . $member, null, 120);
        }
        smfapi_cachePutData('user_settings-' . $member, null, 60);
	}

    return true;
}

/**
 * Update SMF settings
 *
 * Updates settings in the $modSettings array and stores them in the db. Also
 * clears the modSettings cache
 *
 * @param  associative array $changeArray the settings to update
 * @param  bool $update whether to update or replace in db
 * @param  bool $debug deprecated
 * @return bool whether settings were changed or not
 * @since  0.1.0
 */
function smfapi_updateSettings($changeArray, $update = false, $debug = false)
{
	global $modSettings, $smcFunc;

	if (empty($changeArray) || !is_array($changeArray)) {
		return false;
    }

	// in some cases, this may be better and faster, but for large sets we don't want so many UPDATEs.
	if ($update) {
		foreach ($changeArray as $variable => $value) {
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}settings
				SET value = {' . ($value === false || $value === true ? 'raw' : 'string') . ':value}
				WHERE variable = {string:variable}',
				array(
					'value' => $value === true ? 'value + 1' : ($value === false ? 'value - 1' : $value),
					'variable' => $variable,
				)
			);
			$modSettings[$variable] = $value === true ? $modSettings[$variable] + 1 : ($value === false ? $modSettings[$variable] - 1 : $value);
		}

		// clean out the cache and make sure the cobwebs are gone too
		smfapi_cachePutData('modSettings', null, 90);

		return true;
	}

	$replaceArray = array();
	foreach ($changeArray as $variable => $value) {
		// don't bother if it's already like that ;).
		if (isset($modSettings[$variable]) && $modSettings[$variable] == $value) {
			continue;
        }
		// if the variable isn't set, but would only be set to nothing'ness, then don't bother setting it.
		elseif (!isset($modSettings[$variable]) && empty($value)) {
			continue;
        }

		$replaceArray[] = array($variable, $value);

		$modSettings[$variable] = $value;
	}

	if (empty($replaceArray)) {
		return false;
    }

	$smcFunc['db_insert']('replace',
		'{db_prefix}settings',
		array('variable' => 'string-255', 'value' => 'string-65534'),
		$replaceArray,
		array('variable')
	);

	// clear the cache of modsettings data
	smfapi_cachePutData('modSettings', null, 90);

    return true;
}

/**
 * Session regenerate id
 *
 * Regenerate the session id. In case PHP version < 4.3.2
 *
 * @return bool whether session id was regenerated or not
 * @since  0.1.0
 */
if (!function_exists('session_regenerate_id')) {

	function session_regenerate_id()
	{
		// too late to change the session now
		if (headers_sent()) {
			return false;
        } else {
            session_id(strtolower(md5(uniqid(mt_rand(), true))));
        }

		return true;
	}
}

/**
 * Update forum statistics for new member registration
 *
 * Updates latest member || updates member counts
 *
 * @param  string $type the type of update (we're only doing member)
 * @param  int $parameter1 the user's member id || null
 * @param  string $parameter2 the user's real name || null
 * @return bool whether stats were updated or not
 * @since  0.1.0
 */
function smfapi_updateStats($type='member', $parameter1 = null, $parameter2 = null)
{
	global $sourcedir, $modSettings, $smcFunc;

	switch ($type) {

	    case 'member':
		    $changes = array(
			    'memberlist_updated' => time(),
		    );

		    // #1 latest member ID, #2 the real name for a new registration
		    if (is_numeric($parameter1)) {
			    $changes['latestMember'] = $parameter1;
			    $changes['latestRealName'] = $parameter2;

			    smfapi_updateSettings(array('totalMembers' => true), true);
		    }
		    // we need to calculate the totals.
		    else {
			    // Update the latest activated member (highest id_member) and count.
			    $result = $smcFunc['db_query']('', '
				    SELECT COUNT(*), MAX(id_member)
				    FROM {db_prefix}members
				    WHERE is_activated = {int:is_activated}',
				    array(
					    'is_activated' => 1,
				    )
			    );
			    list ($changes['totalMembers'], $changes['latestMember']) = $smcFunc['db_fetch_row']($result);
			    $smcFunc['db_free_result']($result);

			    // Get the latest activated member's display name.
			    $result = $smcFunc['db_query']('', '
				    SELECT real_name
				    FROM {db_prefix}members
				    WHERE id_member = {int:id_member}
				    LIMIT 1',
				    array(
					    'id_member' => (int) $changes['latestMember'],
				    )
			    );
			    list ($changes['latestRealName']) = $smcFunc['db_fetch_row']($result);
			    $smcFunc['db_free_result']($result);

			    // Are we using registration approval?
			    if ((!empty($modSettings['registration_method'])
                    && $modSettings['registration_method'] == 2)
                    || !empty($modSettings['approveAccountDeletion'])) {

				    // Update the amount of members awaiting approval - ignoring COPPA accounts, as you can't approve them until you get permission.
				    $result = $smcFunc['db_query']('', '
					    SELECT COUNT(*)
					    FROM {db_prefix}members
					    WHERE is_activated IN ({array_int:activation_status})',
					    array(
						    'activation_status' => array(3, 4),
					    )
				    );
				    list ($changes['unapprovedMembers']) = $smcFunc['db_fetch_row']($result);
				    $smcFunc['db_free_result']($result);
			    }
		    }

		smfapi_updateSettings($changes);
		break;

		default:
            return false;
	}

	return true;
}

/**
 * Delete personal messages
 *
 * Deletes the personal messages for a member or an array of members.
 * Called by the delete member function
 *
 * @param  array $personal_messages the messages to delete
 * @param  string $folder the folder to delete from
 * @param  int || array $owner the member id(s) that need message deletion
 * @return bool whether deletion occurred or not
 * @since  0.1.0
 */
function smfapi_deleteMessages($personal_messages, $folder = null, $owner = null)
{
	global $user_info, $smcFunc;

	if ($owner === null) {
		$owner = array($user_info['id']);
    } elseif (empty($owner)) {
		return false;
    } elseif (!is_array($owner)) {
		$owner = array($owner);
    }

	if (null !== $personal_messages) {
		if (empty($personal_messages) || !is_array($personal_messages)) {
			return false;
        }

		foreach ($personal_messages as $index => $delete_id) {
			$personal_messages[$index] = (int) $delete_id;
        }

		$where = 'AND id_pm IN ({array_int:pm_list})';
	} else {
		$where = '';
    }

	if ('sent' == $folder || null === $folder) {
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}personal_messages
			SET deleted_by_sender = {int:is_deleted}
			WHERE id_member_from IN ({array_int:member_list})
				AND deleted_by_sender = {int:not_deleted}' . $where,
			array(
				'member_list' => $owner,
				'is_deleted' => 1,
				'not_deleted' => 0,
				'pm_list' => $personal_messages !== null ? array_unique($personal_messages) : array(),
			)
		);
	}

	if ('sent' != $folder || null === $folder) {
		// calculate the number of messages each member's gonna lose...
		$request = $smcFunc['db_query']('', '
			SELECT id_member, COUNT(*) AS num_deleted_messages, CASE WHEN is_read & 1 >= 1 THEN 1 ELSE 0 END AS is_read
			FROM {db_prefix}pm_recipients
			WHERE id_member IN ({array_int:member_list})
				AND deleted = {int:not_deleted}' . $where . '
			GROUP BY id_member, is_read',
			array(
				'member_list' => $owner,
				'not_deleted' => 0,
				'pm_list' => $personal_messages !== null ? array_unique($personal_messages) : array(),
			)
		);
		// ...and update the statistics accordingly - now including unread messages
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			if ($row['is_read']) {
				smfapi_updateMemberData($row['id_member'], array('instant_messages' => $where == '' ? 0 : 'instant_messages - '
                                 . $row['num_deleted_messages']));
            } else {
				smfapi_updateMemberData($row['id_member'], array('instant_messages' => $where == '' ? 0 : 'instant_messages - '
                                 . $row['num_deleted_messages'], 'unread_messages' => $where == '' ? 0 : 'unread_messages - '
                                 . $row['num_deleted_messages']));
            }

			// if this is the current member we need to make their message count correct
			if ($user_info['id'] == $row['id_member']) {
				$user_info['messages'] -= $row['num_deleted_messages'];
				if (!($row['is_read']))
					$user_info['unread_messages'] -= $row['num_deleted_messages'];
			}
		}

		$smcFunc['db_free_result']($request);

		// do the actual deletion
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}pm_recipients
			SET deleted = {int:is_deleted}
			WHERE id_member IN ({array_int:member_list})
				AND deleted = {int:not_deleted}' . $where,
			array(
				'member_list' => $owner,
				'is_deleted' => 1,
				'not_deleted' => 0,
				'pm_list' => $personal_messages !== null ? array_unique($personal_messages) : array(),
			)
		);
	}

	// if sender and recipients all have deleted their message, it can be removed
	$request = $smcFunc['db_query']('', '
		SELECT pm.id_pm AS sender, pmr.id_pm
		FROM {db_prefix}personal_messages AS pm
			LEFT JOIN {db_prefix}pm_recipients AS pmr ON (pmr.id_pm = pm.id_pm AND pmr.deleted = {int:not_deleted})
		WHERE pm.deleted_by_sender = {int:is_deleted}
			' . str_replace('id_pm', 'pm.id_pm', $where) . '
		GROUP BY sender, pmr.id_pm
		HAVING pmr.id_pm IS null',
		array(
			'not_deleted' => 0,
			'is_deleted' => 1,
			'pm_list' => $personal_messages !== null ? array_unique($personal_messages) : array(),
		)
	);

	$remove_pms = array();

	while ($row = $smcFunc['db_fetch_assoc']($request)) {
		$remove_pms[] = $row['sender'];
    }

	$smcFunc['db_free_result']($request);

	if (!empty($remove_pms)) {
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}personal_messages
			WHERE id_pm IN ({array_int:pm_list})',
			array(
				'pm_list' => $remove_pms,
			)
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}pm_recipients
			WHERE id_pm IN ({array_int:pm_list})',
			array(
				'pm_list' => $remove_pms,
			)
		);
	}

	// any cached numbers may be wrong now
	smfapi_cachePutData('labelCounts:' . $user_info['id'], null, 720);

	return true;
}

?>