var chatrbox = (function(settings) {
    function submitShout() {
        var $messageBox = $("#chatrbox_message_field"),
            message = $messageBox.val(),
            maxShoutLimit = settings.messageSizeLimit,
            command = "";
			
        // Message is too short.
        if (message.length == 0)
            return;

        if (message[0] == "/") {
            var commandEnd = message.indexOf(' ') > -1 ? 
                message.indexOf(' ') : message.length;
            command = message.substr(1, commandEnd - 1);
            message = message.substr(commandEnd + 1);
            console.log(message);
        } else {
            // Message is too long.
            if (message.length > maxShoutLimit) {
                alert(settings.messageSizeLimitMsg);
                return;
            }
        }
        
        $.post("index.php", {
            action : "chatrbox",
            sa : "shout",
            message : message,
            command : command,
            xml : 1
        },
        function(data, textStatus, jqXHR) {
            var xml = $(data).find("chatrbox"),
            success = xml.find("success").text(),
            $shout = "";
					
            if (success) {
                var messageTime = xml.find("time").text(),
                    isNotification = xml.find("notification").text(),
                    memberName = xml.find("memberName").text(),
                    shoutHtml = "";
                    
                message = xml.find("message").text();
                $shout = $("<div></div>");
                
                shoutHtml += "[" + messageTime + "] ";
                if (isNotification == false)
                    shoutHtml += "<a href=\"index.php?action=profile;u=" + settings.contextMemberId + "\">" + memberName + "</a>: ";
                
                $shout.html(shoutHtml + message);
                $messageBox.val("");
            } else {
                // Any errors that can be shown?
                var errorMessage = xml.find("error");
                if (errorMessage.length > 0)
                    alert(errorMessage.text());
            }

            $("#chatrbox_messagebox").prepend($shout);
        }, "xml");
    }
                
    // Update Chatrbox
    window.chatrboxUpdateTimer = setInterval(function() {
        $.post("index.php", {
            action : "chatrbox",
            sa : "update",
            xml : 1
        },
        function(data, textStatus, jqXHR) {
            var xml = $(data).find("chatrbox");
            $("#chatrbox_notice").html("<strong>" + settings.chatrboxNotice + "</strong> " + xml.find("notice").text());
            $("#chatrbox_messagebox").text("");
                        
            // This isn\'t a regular shout...
            if (xml.find("banMessage").length > 0) {
                showBanMessage(xml.find("banMessage").text());
                return;
            }
                            
            xml.find("messageData").each(function() {
                var $shout = $(this),
                memberId = $shout.find("memberId").text(),
                memberName = $shout.find("memberName").text(),
                message = $shout.find("message").text(),
                messageTime = $shout.find("time").text(),
                messageHtml = "[" + messageTime + "] ";

                $shout = $("<div></div>");
                if (memberId == 0) {
                    messageHtml += memberName + ": ";
                } else if (memberId > 0) {
                    messageHtml += "<a href=\"index.php?action=profile;u=" + memberId + "\">" + memberName + "</a>: ";
                }
                                
                $shout.html(messageHtml + message);
                $("#chatrbox_messagebox").append($shout);
            });
        }, "xml");
    }, settings.refreshRate);
                
    function showBanMessage() {
        var $banMessage = $("<div></div>").html(settings.chatrboxBannedMessage);
        $("#chatrbox_messagebox").prepend($banMessage);
        $("#chatrbox_message_field").attr("disabled", true);
        clearInterval(window.chatrboxUpdateTimer);
    }

    // Set keyboard event for message box - Chatrbox
    $("#chatrbox_message_field").bind("keydown", function(event) {
        if (event.which == 13) {
            submitShout();
        }
    });
                
    $("#chatrbox_submit").bind("click", submitShout);
});