<?php

if (!defined('SMF'))
    die('Why hello there, are you lost?');
    
function command_notice($notice) {
    
    global $context, $txt;
    
    submitShout(sprintf($txt['chatrboxNoticeShout'], 
        getStyledName($context['user']['id'], $context['user']['name'])), true);
    updateSettings(array(
        'chatrboxNotice' => $notice
    ), true);
    return true;
}

function command_unban($idBannedMember) {
    
    global $smcFunc, $context, $memberContext, $txt;

    if (!is_numeric($idBannedMember)) {
        submitResponse('shout', array(
            'success' => false,
            'error' => $txt['chatrboxUnbanById']
        ));
        return false;
    }
    
    if ($idBannedMember == $context['user']['id'])
        return false;
    
    $smcFunc['db_query']('',
        'DELETE FROM {db_prefix}chatrbox_bans
         WHERE id_member = {int:id_member}',
        array(
            'id_member' => $idBannedMember
        )
    );
    
    if ($smcFunc['db_affected_rows']() > 0) {
        loadMemberData($idBannedMember);
        loadMemberContext($idBannedMember);
        submitShout(sprintf($txt['chatrboxUnbanShout'], 
            $memberContext[$idBannedMember]['name'], 
            getStyledName($context['user']['id'], $context['user']['name'])), true);
    }

    return true;
}

function command_ban($idBannedMember) {
    
    global $smcFunc, $context, $memberContext, $txt;

    if (!is_numeric($idBannedMember)) {
        submitResponse('shout', array(
            'success' => false,
            'error' => $txt['chatrboxBanById']
        ));
        return false;
    }
    
    // Banning yourself!? For the lulz I assume?
    if ($idBannedMember == $context['user']['id'])
        return false;
    
    $smcFunc['db_insert']('ignore', '{db_prefix}chatrbox_bans',
        array(
            'id_member' => 'int', 'id_banned_by' => 'int', 'ban_time' => 'int'
        ),
        array(
            $idBannedMember, $context['user']['id'], time()
        ),
        array()
    );
    
    if ($smcFunc['db_affected_rows']() > 0) {
        loadMemberData($idBannedMember);
        loadMemberContext($idBannedMember);
        submitShout(sprintf($txt['chatrboxBanShout'], 
            $memberContext[$idBannedMember]['name'], 
            getStyledName($context['user']['id'], $context['user']['name'])), true); 
    } else {
        submitResponse('shout', array(
            'success' => false,
            'error' => $txt['chatrboxUserAlreadyBanned']
        ));
    }

    return true;
}

function command_prune() {
    
    global $context, $txt;
    
    submitShout(sprintf($txt['chatrboxPruneShout'], 
        getStyledName($context['user']['id'], $context['user']['name'])), true);
    // Keep the command so it can be a point in the database where
    // All subsequent messages (including this) won't be shown.
    submitShout('/prune');
}

function command_me($message) {
    
    global $context;
    
    submitShout(getStyledName($context['user']['id'], $context['user']['name']) . ' ' . $message, true);
}

?>