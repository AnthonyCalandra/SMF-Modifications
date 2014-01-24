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

// Template layer
function template_hfl_above() {
    
	global $context, $scripturl, $txt, $modSettings;
	
    if (!empty($modSettings['enable_hfl_left_ad']) && !empty($modSettings['enable_hfl_right_ad']))
        $linksWidth = '70%';
    elseif (!empty($modSettings['enable_hfl_left_ad']) || !empty($modSettings['enable_hfl_right_ad']))
        $linksWidth = '85%';
    else
        $linksWidth = '100%';
        
    		
	echo '
	<table border="0" width="100%" border="0" cellspacing="0" cellpadding="4" align="center" class="tborder">
		<tr>
			<td class="catbg" align="center" colspan="3">' . $modSettings['hfl_custom_title'] . '</td>
		</tr>
		<tr>';
    if (!empty($modSettings['enable_hfl_left_ad']))
        echo '<td valign="middle" align="center" width="15%">' . $modSettings['hfl_left_ad'] . '</td>';
        
    echo '<td valign="middle" align="center" width="' . $linksWidth . '" height="60">
                <ul style="list-style-type: none;">';

    foreach ($context['hot_forum_links']['links'] as $link) {
        echo '<li><a href="' . $link['href'] . '">' . $link['title'] . '</a></li>';
    }

	echo '     </ul>     
    		</td>';
    if (!empty($modSettings['enable_hfl_right_ad']))
        echo '<td valign="middle" align="center" width="15%">' . $modSettings['hfl_right_ad'] . '</td>';
	
    echo '</tr>
        <tr>
            <td align="center" colspan="3">' . $context['hot_forum_links']['page_index'] . ' - <a href="' . $scripturl . '?action=hotforumlinks">' . $txt['hfl_archive_title'] . '</a></td>
        </tr>
	</table>';
}


// Template layer
function template_hfl_below() {
}

function template_hfl() {
    
    global $context, $txt, $settings, $scripturl;
    
    echo '
    <table width="100%" border="0" cellspacing="0" cellpadding="4" align="center" class="tborder">
        <tr>
			<td class="catbg" colspan="4">' . $txt['hfl_archive_title'] . '</td>
		</tr>
        <tr>
            <th></th>
			<th>' . $txt['hfl_date_added'] . '</th>
            <th>' . $txt['hfl_member_added'] . '</th>
            <th>' . $txt['hfl_topic_title'] . '</th>
		</tr>';
        
    foreach ($context['hot_forum_links']['links'] as $link) {
        echo '
        <tr class="windowbg2">
            <td width="' . (allowedTo('add_hot_forum_links') ? '7.5%' : '0%') . '">';
        
        if (allowedTo('add_hot_forum_links')) {
            echo '<a href="' . $scripturl . '?action=hotforumlinks;sa=modifylink;topic=' . $link['id_topic'] . '.0">
                    <img src="' . $settings['theme_url'] . '/images/buttons/modify.gif" alt="" />
                </a>
                <a href="' . $scripturl . '?action=hotforumlinks;sa=removelink;topic=' . $link['id_topic'] . '.0">
                    <img src="' . $settings['theme_url'] . '/images/buttons/delete.gif" alt="" />
                </a>';
        }

       echo '</td>
   			<td width="' . (allowedTo('add_hot_forum_links') ? '27.5%' : '35%') . '">' . $link['timestamp'] . '</td>
            <td width="15%">' . $link['member_added']['username'] . '</td>
            <td width="50%"><a href="' . $link['href'] . '">' . $link['title'] . '</a></td>
   		</tr>';
    }
        
     echo '</table>
     <br />
     <div style="text-align: center;">' . $context['hot_forum_links']['page_index'] . '</div>';
}

function template_hfl_add() {
    
    global $context, $txt, $scripturl;
    
	echo '
	<table border="0" width="50%" border="0" cellspacing="0" cellpadding="4" align="center" class="tborder">
		<tr>
			<td class="catbg" align="center">' . $txt['hfl_add_hot_link'] . '</td>
		</tr>
		<tr>
			<td valign="middle" align="center" height="60">
                <form action="' . $scripturl . '?action=hotforumlinks;sa=addlink;topic=' . $context['hot_forum_link']['topic'] . '" method="post">';
                
    echo sprintf($txt['hfl_add_link_contents'], $context['hot_forum_link']['topic'], $context['hot_forum_link']['original_title']);     
            
    echo '          <br /><input type="text" name="title" value="" />
        	        <input type="hidden" name="safe_original_title" value="' . $context['hot_forum_link']['safe_original_title'] . '" />
                    <input type="hidden" name="complete" value="1" />
                    <input type="submit" name="submit" value="' . $txt['hfl_submit'] . '" />
                </form>
            </td>
		</tr>
	</table>';
}

function template_hfl_modify() {
    
    global $context, $txt, $scripturl;
    
	echo '
	<table border="0" width="50%" border="0" cellspacing="0" cellpadding="4" align="center" class="tborder">
		<tr>
			<td class="catbg" align="center">' . $txt['hfl_modify_hot_link'] . '</td>
		</tr>
		<tr>
			<td valign="middle" align="center" height="60">
                <form action="' . $scripturl . '?action=hotforumlinks;sa=modifylink;topic=' . $context['hot_forum_link']['topic'] . '" method="post">';
                
    echo sprintf($txt['hfl_modify_link_contents'], $context['hot_forum_link']['topic'], $context['hot_forum_link']['original_title']);     
            
    echo '          <br /><input type="text" name="title" value="" />
        	        <input type="hidden" name="safe_original_title" value="' . $context['hot_forum_link']['safe_original_title'] . '" />
                    <input type="hidden" name="complete" value="1" />
                    <input type="submit" name="submit" value="' . $txt['hfl_submit'] . '" />
                </form>
            </td>
		</tr>
	</table>';
}

?>