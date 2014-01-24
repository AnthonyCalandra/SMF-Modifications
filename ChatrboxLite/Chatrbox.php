<?php

if (!defined('SMF'))
    die('Why hello there, are you lost?');

require_once('Chatrbox-Commands.php');

function Chatrbox() {

    global $modSettings;

    // Store an empty response to avoid troublesome template errors.
    // This gets overwritten with any subsequent responses.
    submitResponse();

    if (empty($modSettings['enableChatrbox']))
        return;

    // Guests aren't allowed to use the shoutbox!
    is_not_guest();

    $subActions = array(
        'shout' => 'parseShout',
        'update' => 'update'
    );

    if (isset($subActions[$_REQUEST['sa']]))
        $subActions[$_REQUEST['sa']]();
    else
        redirectexit();
}

function initChatrbox() {

    global $context, $smcFunc, $modSettings, $settings, $txt;

    // Do not intercept XHR requests, make sure Chatrbox is enabled, no guests, etc...
    if (isset($_REQUEST['xml']))
        return;

    if ($context['user']['is_guest'])
        return;

    if (empty($modSettings['enableChatrbox']))
        return;
    
    if (!empty($modSettings['chatrboxShowOnlyIndex']) &&
            !(empty($context['current_action']) && empty($_REQUEST['board']) && empty($_REQUEST['topic'])))
        return;

    $context['html_headers'] .= '
        <link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/css/chatrbox.css" />
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/chatrbox.js"></script>
	<script type="text/javascript">
            $(document).ready(function() {
                chatrbox({
                    refreshRate : ' . $modSettings['chatrboxRefreshRate'] . ',
                    contextMemberId : ' . $context['user']['id'] . ', 
                    contextMemberName : "' . $context['user']['name'] . '", 
                    chatrboxNotice : "' . $txt['chatrboxNotice'] . '",
                    chatrboxBannedMessage : "' . $txt['chatrboxBannedMessage'] . '",
                    messageSizeLimit : ' . $modSettings['chatrboxMsgSizeLimit'] . ',
                    messageSizeLimitMsg : "' . sprintf($txt['chatrboxMessageTooLong'], $modSettings['chatrboxMsgSizeLimit']) . '"
                }); 
            });
	</script>';

    $context['template_layers'][] = 'chatrbox';
    $context['chatrbox']['shouts'] = array();
    if (isUserBanned(false))
        return;

    $request = $smcFunc['db_query']('', 
        'SELECT shout.id_message, shout.id_member, shout.message,
            shout.message_time, 
            IFNULL(member.member_name, IF(shout.id_member = 0, "Guest", "")) AS member_name
        FROM {db_prefix}chatrbox AS shout
        LEFT JOIN {db_prefix}members AS member ON (shout.id_member = member.id_member)
        ORDER BY message_time DESC
        LIMIT 0, {int:max_shouts}', 
        array(
            'max_shouts' => 25
        )
    );

    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        // A pruning command was issued, leave everything else.
        if ($row['message'] === '/prune')
            break;

        $context['chatrbox']['shouts'][] = array(
            'id' => $row['id_message'],
            'memberId' => $row['id_member'],
            'memberName' => getStyledName($row['id_member'], $row['member_name']),
            'message' => parse_bbc($row['message'], true, '', getAllowedBBC()),
            'time' => formatMilitaryTime($row['message_time'])
        );
    }

    $smcFunc['db_free_result']($request);
    $commands = getCommands();
    $context['chatrbox']['canDeleteShouts'] = canExecuteCommand($commands['delete'][1]);
}

function parseShout() {

    global $smcFunc, $modSettings;

    if (isUserBanned() || isSpamming())
        return false;

    $message = $smcFunc['htmltrim']($_POST['message']);
    $message = $smcFunc['htmlspecialchars']($message, ENT_QUOTES);
    // Are we possibly dealing with a command?
    if (isset($_POST['command']) && $_POST['command'] !== '') {
        // If it isn't a command, we've sent the message; now terminate.
        executeCommand($_POST['command'], $message);
        return true;
    }

    // Max/min shout length.
    if (strlen($message) > $modSettings['chatrboxMsgSizeLimit'] || strlen($message) == 0)
        return false;

    submitShout($message);
}

function update() {

    global $smcFunc, $modSettings;

    if (isUserBanned())
        return false;

    $shouts = array();
    $request = $smcFunc['db_query']('', 
        'SELECT shout.id_message, shout.id_member, shout.message,
            shout.message_time, IFNULL(member.member_name, 
            IF(shout.id_member = 0, "Guest", "")) AS member_name
        FROM {db_prefix}chatrbox AS shout
        LEFT JOIN {db_prefix}members AS member ON (shout.id_member = member.id_member)
        ORDER BY message_time DESC
        LIMIT 0, {int:max_shouts}', 
        array(
            'max_shouts' => 25
        )
    );

    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        // A pruning command was issued, leave everything else.
        if ($row['message'] === '/prune')
            break;

        $shouts[] = array(
            'id' => $row['id_message'],
            'memberId' => $row['id_member'],
            'memberName' => getStyledName($row['id_member'], $row['member_name']),
            'message' => parse_bbc($row['message'], true, '', getAllowedBBC()),
            'time' => formatMilitaryTime($row['message_time'])
        );
    }

    // Silently place the notice in with the shouts because we need it... ;)
    $shouts['notice'] = $modSettings['chatrboxNotice'];
    submitResponse('update', $shouts);
    $smcFunc['db_free_result']($request);
}

function getCommands() {
    /*
      array(number of arguments, user permissions)
      - Number of arguments:
            Numeric value with how many args are given.
            Example: /command Anthony` 123
            Anthony` = arg 1
            123 = arg 2
      - User permissions:
            String containing either special character or CSV-group ids.
            Characters:
            * = All users
            + = All staff members (discluding board moderators)
            1,2,3 = Groups 1, 2, or 3
            NOTE: Finer-grained control over permissions can be done by setting the
            permission to * then interfacing with SMF's permissions yourself
            in the command's function.
     */
    $commands = array(
        'notice' => array(1, '+'),
        'ban' => array(1, '+'),
        'unban' => array(1, '+'),
        'prune' => array(0, '+'),
        'me' => array(1, '*')
    );
    
    return $commands;
}

function canExecuteCommand($permissions) {

    global $user_info;

    // Admins can do anything!
    if ($user_info['is_admin'])
        return true;

    if ($permissions === '*') {
        return true;
    } else if ($permissions === '+') {
        // Global moderators
        if ($user_info['groups'][0] == 2)
            return true;
    } else if ($permissions !== '') {
        $groups = explode(',', $permissions);
        foreach ($groups as $permGroup) {
            if (in_array($permGroup, $user_info['groups']))
                return true;
        }
    }

    return false;
}

function executeCommand($commandName, $message) {

    global $txt;

    $commands = getCommands();
    // This command doesn't actually exist!
    if (!isset($commands[$commandName])) {
        submitResponse('shout', array(
            'success' => false,
            'error' => $txt['chatrboxCommandNotExists']
        ));
        return false;
    }

    $command = $commands[$commandName];

    // The user isn't allowed to execute this command.
    if (!canExecuteCommand($command[1])) {
        submitResponse('shout', array(
            'success' => false,
            'error' => $txt['chatrboxCommandNotAllowed']
        ));
        return false;
    }

    // Does the command have more than one argument? If so, parse each argument.
    // If not, check if it has 1 and send the rest of the message, or nothing.
    $commandArguments = ($command[0] > 1) ? explode(' ', $message, $command[0]) :
            (($command[0] == 1) ? array($message) : array());

    // The command was entered with an invalid number of arguments.
    if ($command[0] != count($commandArguments)) {
        submitResponse('shout', array(
            'success' => false,
            'error' => sprintf($txt['chatrboxCommandInvalidArgs'], $command[0])
        ));
        return false;
    }

    return call_user_func_array('command_' . $commandName, $commandArguments);
}

function submitShout($message, $notification = false) {

    global $smcFunc, $context;

    $messageTime = time();
    $smcFunc['db_insert']('replace', '{db_prefix}chatrbox', 
        array(
            'id_member' => 'int', 'message' => 'string', 'message_time' => 'int'
        ), 
        array(
            !$notification ? $context['user']['id'] : -1, $message, $messageTime
        ), 
        array('id_message')
    );

    // Every valid message cancels out a spammy one.
    // This keeps a fair/accurate count.
    if ($_SESSION['chatrbox']['numSpamMessages'] > 0)
        $_SESSION['chatrbox']['numSpamMessages']--;

    $response = array(
        'message' => parse_bbc($message, true, '', getAllowedBBC()),
        'time' => formatMilitaryTime($messageTime),
        'success' => true,
        'notification' => $notification
    );
    if (!$notification)
        $response['memberName'] = getStyledName($context['user']['id'], $context['user']['name']);
    
    submitResponse('shout', $response);
    return true;
}

function submitResponse($responseType = 'shout', $responseData = array()) {

    global $context;

    loadTemplate('Xml');
    $context['sub_template'] = 'chatrbox_' . $responseType . '_xml';
    $context['chatrbox']['xml_data'] = $responseData;
}

function isUserBanned($sendResponse = true) {

    global $context, $smcFunc, $txt;

    $request = $smcFunc['db_query']('', 
        'SELECT id_member
        FROM {db_prefix}chatrbox_bans
        WHERE id_member = {int:id_member}
        LIMIT 1', 
        array(
            'id_member' => $context['user']['id']
        )
    );

    // You're clean... NEXT!
    if ($smcFunc['db_num_rows']($request) == 0) {
        $smcFunc['db_free_result']($request);
        return false;
    }

    $smcFunc['db_free_result']($request);
    if ($sendResponse)
        submitResponse('ban', array(
            'banned' => true,
            'message' => $txt['chatrboxBannedMessage']
        ));

    $context['chatrbox']['banned'] = true;
    return true;
}

function isSpamming() {

    global $smcFunc, $context, $txt;

    // Hold data in a session variable to maintain count without an extra DB call.
    if (!isset($_SESSION['chatrbox']['numSpamMessages']))
        $_SESSION['chatrbox']['numSpamMessages'] = 0;

    $request = $smcFunc['db_query']('', 
        'SELECT message_time
        FROM {db_prefix}chatrbox AS shout
        WHERE id_member = {int:id_member}
        ORDER BY message_time DESC
        LIMIT 1', 
        array(
            'id_member' => $context['user']['id']
        )
    );

    list($lastMessageTime) = $smcFunc['db_fetch_row']($request);
    $smcFunc['db_free_result']($request);
    // We got one!
    if ((time() - $lastMessageTime) < 1) {
        // Time for a warning...
        if ($_SESSION['chatrbox']['numSpamMessages'] >= 5) {
            submitResponse('shout', array(
                'success' => false,
                'error' => $txt['chatrboxStopSpamming']
            ));
        }

        $_SESSION['chatrbox']['numSpamMessages']++;
        return true;
    }

    return false;
}

function getStyledName($memberId, $memberName) {
    
    // This is a system notification.
    if ($memberId < 0)
        return $memberName;
    
    call_integration_hook('integrate_chatrbox_username', array($memberId, &$memberName));
    return $memberName;
}

function getAllowedBBC() {
    
    global $modSettings;
    
    // Parse what is given in the ACP, or if it's disabled... Nothing.
    return $modSettings['chatrboxEnableBBC'] ? 
        explode(',', $modSettings['chatrboxAllowedBBC']) : array('');
}

?>