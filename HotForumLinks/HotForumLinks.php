<?php

/*
    Hot Forum Links - a modification for SimpleMachines Forum software.
    Copyright (C) 2011  Anthony Calandra

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

function HotForumLinks() {
    
    global $context, $modSettings, $txt;
    
    // Check to make sure this mod is enabled
    if (empty($modSettings['enable_hot_forum_links']))
        fatal_lang_error('hfl_not_enabled', false);
        
    loadTemplate('HotForumLinks');
    $context['sub_template'] = 'hfl';
    $context['page_title'] = $txt['hfl_page_title'];
    
    $subActions = array(
        'addlink' => 'addLink',
        'removelink' => 'removeLink',
        'modifylink' => 'modifyLink',
    );
    if (isset($_REQUEST['sa']) && !empty($subActions[$_REQUEST['sa']]))
        $_GET['sa']();
    else
        displayLinks();
}

function HotForumLinksIndex() {
    
    global $context, $modSettings, $scripturl, $db_prefix, $txt;

    // Check to make sure this mod is enabled
    if (empty($modSettings['enable_hot_forum_links']))
        return;

    // Load the template 
    loadTemplate('HotForumLinks');
    $context['template_layers'][] = 'hfl';
    $context['hot_forum_links']['links'] = retrieveHotForumLinks();
    
    // Why bother caching this query if MyISAM stores an internal count?
    $request = db_query("SELECT COUNT(*) FROM {$db_prefix}hot_forum_links", __FILE__, __LINE__);
    list($numLinks) = mysql_fetch_row($request);
    mysql_free_result($request);
    
    $baseUrl = $scripturl . '?action=hotforumlinks';
    $start = 0;
    $totalLinks = $modSettings['hfl_per_page'] * $modSettings['hfl_num_index_pages'];
    if ($numLinks > $totalLinks)
        $numLinks = $totalLinks;

    $context['hot_forum_links']['page_index'] = $txt[139] . ': ' . 
        constructPageIndex($baseUrl, $start, $numLinks, $modSettings['hfl_per_page']);
}

function retrieveHotForumLinks($type = 'minimal', $start = 0, $numLinks = 0) {
    
    global $context, $modSettings, $scripturl, $db_prefix, $memberContext;
    
    if (empty($numLinks))
        $numLinks = $modSettings['hfl_per_page'];

    $hotForumLinks = array();
    if ($type == 'minimal') {
        $request = db_query("SELECT id_topic, title
            FROM {$db_prefix}hot_forum_links
            ORDER BY timestamp DESC
            LIMIT {$start}, {$numLinks}", __FILE__, __LINE__);
        while ($row = mysql_fetch_assoc($request)) {
            $hotForumLinks[] = array(
                'id_topic' => $row['id_topic'],
                'title' => $row['title'],
                'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0',
            );
        }
    } elseif ($type == 'full') {
        $request = db_query("SELECT id_topic, member_added, timestamp, title
            FROM {$db_prefix}hot_forum_links
            ORDER BY timestamp DESC
            LIMIT {$start}, {$numLinks}", __FILE__, __LINE__);
        while ($row = mysql_fetch_assoc($request)) {
            loadMemberData($row['member_added']);
            loadMemberContext($row['member_added']);
            $hotForumLinks[] = array(
                'id_topic' => $row['id_topic'],
                'title' => $row['title'],
                'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0',
                'member_added' => array(
                    'id' => $memberContext[$row['member_added']]['id'],
                    'username' => $memberContext[$row['member_added']]['name'],
                    'link' => $memberContext[$row['member_added']]['link'],
                ),
                'timestamp' => timeformat($row['timestamp']),
            );
        }
    }
    
    mysql_free_result($request);
    return $hotForumLinks;
}

function displayLinks() {
    
    global $context, $scripturl, $modSettings, $db_prefix, $txt;

    $request = db_query("SELECT COUNT(*) FROM {$db_prefix}hot_forum_links", __FILE__, __LINE__);
    list($numLinks) = mysql_fetch_row($request);
    mysql_free_result($request);
    
    $baseUrl = $scripturl . '?action=hotforumlinks';
    $start = empty($_REQUEST['start']) || !is_numeric($_REQUEST['start']) ? 0 : $_REQUEST['start'];
    $context['hot_forum_links']['page_index'] = constructPageIndex($baseUrl, $start, $numLinks, $modSettings['hfl_per_page']);
    $context['hot_forum_links']['links'] = retrieveHotForumLinks('full', $start);
}

function addLink() {
    
    global $context, $scripturl, $db_prefix;
    
    // Make sure special users are accessing this feature
    isAllowedTo('add_hot_forum_links');
    
    if (empty($_REQUEST['topic']) || !is_numeric($_REQUEST['topic']))
        redirectexit($scripturl);
        
    $request = db_query("SELECT id_topic
        FROM {$db_prefix}hot_forum_links
        WHERE id_topic = " . $_REQUEST['topic'], __FILE__, __LINE__);
    if (mysql_num_rows($request) > 0) {
        mysql_free_result($request);
        fatal_lang_error('hfl_already_added', false);
    }
        
    if (!isset($_REQUEST['submit'])) {
        $context['sub_template'] = 'hfl_add';
        $request = db_query("SELECT subject
            FROM {$db_prefix}messages AS topic
            WHERE topic.ID_TOPIC = " . $_REQUEST['topic'], __FILE__, __LINE__);
        list($context['hot_forum_link']['original_title']) = mysql_fetch_row($request);
        $context['hot_forum_link']['safe_original_title'] = urlencode($context['hot_forum_link']['original_title']);
        $context['hot_forum_link']['topic'] = $_REQUEST['topic'];
        mysql_free_result($request);
    } else {
        if (empty($_REQUEST['title']))
            $_REQUEST['title'] = urldecode($_REQUEST['safe_original_title']);
        db_query("INSERT INTO {$db_prefix}hot_forum_links
            VALUES (" . $_REQUEST['topic'] . ", " . $context['user']['id'] . ", " . time() . ", '" . htmlspecialchars($_REQUEST['title'], ENT_QUOTES) . "')",
            __FILE__, __LINE__);
        redirectexit($scripturl);
    }
}

function removeLink() {
    
    global $context, $scripturl, $db_prefix;
    
    // Make sure special users are accessing this feature
    isAllowedTo('add_hot_forum_links');
    
    if (empty($_REQUEST['topic']) || !is_numeric($_REQUEST['topic']))
        redirectexit($scripturl);
    
    $request = db_query("SELECT id_topic
        FROM {$db_prefix}hot_forum_links
        WHERE id_topic = " . $_REQUEST['topic'], __FILE__, __LINE__);
    if (mysql_num_rows($request) == 0) {
        mysql_free_result($request);
        fatal_lang_error('hfl_not_exist', false);
    }
    
    db_query("DELETE FROM {$db_prefix}hot_forum_links
        WHERE id_topic = " . $_REQUEST['topic'],
        __FILE__, __LINE__);
    
    if (!empty($_SESSION['old_url']))
        $redirectUrl = $scripturl . '?action=hotforumlinks';
    else
        $redirectUrl = $scripturl . '?topic=' . $_REQUEST['topic'];
    redirectexit($redirectUrl);
}

function modifyLink() {
    
    global $context, $scripturl, $db_prefix;
    
    // Make sure special users are accessing this feature
    isAllowedTo('add_hot_forum_links');
    
    if (empty($_REQUEST['topic']) || !is_numeric($_REQUEST['topic']))
        redirectexit($scripturl);
        
    $request = db_query("SELECT id_topic
        FROM {$db_prefix}hot_forum_links
        WHERE id_topic = " . $_REQUEST['topic'], __FILE__, __LINE__);
    if (mysql_num_rows($request) == 0) {
        mysql_free_result($request);
        fatal_lang_error('hfl_not_exist', false);
    }
        
    if (!isset($_REQUEST['submit'])) {
        $context['sub_template'] = 'hfl_modify';
        $request = db_query("SELECT subject
            FROM {$db_prefix}messages AS topic
            WHERE topic.ID_TOPIC = " . $_REQUEST['topic'], __FILE__, __LINE__);
        list($context['hot_forum_link']['original_title']) = mysql_fetch_row($request);
        $context['hot_forum_link']['safe_original_title'] = urlencode($context['hot_forum_link']['original_title']);
        $context['hot_forum_link']['topic'] = $_REQUEST['topic'];
        $context['hot_forum_link']['old_url'] = '';
        if (isset($_REQUEST['from_topic']))
            $_SESSION['hfl_from_topic'] = true;
        mysql_free_result($request);
    } else {
        if (empty($_REQUEST['title']))
            $_REQUEST['title'] = urldecode($_REQUEST['safe_original_title']);
        db_query("UPDATE {$db_prefix}hot_forum_links
            SET title = '" . htmlspecialchars($_REQUEST['title'], ENT_QUOTES) . "'
            WHERE id_topic = " . $_REQUEST['topic'],
            __FILE__, __LINE__);
            
        if (!empty($_SESSION['hfl_from_topic'])) {
            $redirectUrl = $scripturl;
            unset($_SESSION['hfl_from_topic']);
        } else
            $redirectUrl = $scripturl . '?action=hotforumlinks';
        redirectexit($redirectUrl);
    }
}

?>