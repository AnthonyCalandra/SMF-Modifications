<?php

function template_chatrbox_above() {

    global $context, $modSettings, $txt;

   echo '
	<div id="chatrbox">
		<div class="cat_bar">
			<h3 class="catbg">' . $txt['chatrbox'] . '</h3>
		</div>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div id="chatrbox_content_layer">
				<div id="chatrbox_shout_panel">
					<input type="text" id="chatrbox_message_field" name="message" />
					<input type="button" name="submit" value="' . $txt['chatrboxSubmitShout'] . '" id="chatrbox_submit" class="button_submit" />
				</div>
				<hr class="hr_seperator" />
				<div id="chatrbox_notice"><strong>' . $txt['chatrboxNotice'] . '</strong> ' . parse_bbc($modSettings['chatrboxNotice']) . '</div>
				<div id="chatrbox_panels">
					<div id="chatrbox_tabs">
						<ul>
							<li>' . $txt['chatrboxShoutbox'] . '</li>
						</ul>
					</div>
					<div id="chatrbox_messagebox">';
    
    // Banned user?
    if (isset($context['chatrbox']['banned']) && $context['chatrbox']['banned']) {
        echo '<div>' . $txt['chatrboxBannedMessage'] . '</div>';
    } else { // If they aren't banned, relay shoutbox messages.
        foreach ($context['chatrbox']['shouts'] as $shout) {
            $memberLink = '';
            if ($shout['memberId'] == 0) {
                $memberLink = $shout['memberName'] . ':';
            } else if ($shout['memberId'] > 0) {
                $memberLink = '<a href="index.php?action=profile;u=' . $shout['memberId'] . '">' . $shout['memberName'] . '</a>:';
            }

            echo '<div>[' . $shout['time'] . '] ' . $memberLink . ' ' . $shout['message'] . '</div>';
        } 
    }

    echo '				</div>
				</div>
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>
        <br />';
}

function template_chatrbox_shout_xml() {

    global $context;

    echo '<', '?xml version="1.0" encoding="', $context['character_set'], '"?', '>
<smf>
	<chatrbox>';

    foreach ($context['chatrbox']['xml_data'] as $tag => $data) {
        formatData($tag, $data);
    }

    echo '</chatrbox>
</smf>';
}

function template_chatrbox_update_xml() {

    global $context;

    echo '<', '?xml version="1.0" encoding="', $context['character_set'], '"?', '>
<smf>
	<chatrbox>';
    
    formatData('notice', parse_bbc($context['chatrbox']['xml_data']['notice']));
    // We don't want the notice during the foreach iteration.
    unset($context['chatrbox']['xml_data']['notice']);
    foreach ($context['chatrbox']['xml_data'] as $messages) {
        echo '<messageData>';
        foreach ($messages as $row => $message) {
            formatData($row, $message);
        }

        echo '</messageData>';
    }

    echo '</chatrbox>
</smf>';
}

function template_chatrbox_ban_xml() {

    global $context;

    echo '<', '?xml version="1.0" encoding="', $context['character_set'], '"?', '>
<smf>
	<chatrbox>';

    formatData('banMessage', parse_bbc($context['chatrbox']['xml_data']['message']));

    echo '</chatrbox>
</smf>';
}

function formatData($tag, $data) {
    if (!is_numeric($data))
        $data = '<![CDATA[' . $data . ']]>';

    echo '<' . $tag . '>' . $data . '</' . $tag . '>';
}

function template_chatrbox_below() {}

?>