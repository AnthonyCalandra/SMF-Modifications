// Special thanks to: http://stackoverflow.com/questions/5586558/jquery-ui-sortable-disable-update-function-before-receive
// For the idea with setting up the newPosition flag to prevent multiple unneeded calls to updateBoards
(function($) {
    $.fn.smfAjaxBoardReordering = function(options) {
        // Set up some default settings
        var settings = $.extend({
            "successText" : "Successfully moved board.",
            "categoryConfirmText" : "Are you sure you wish to move this board to a new category?",
            "parentBoardClass" : "windowbg2",
        }, options);
        // Flag indicating a board is actually changing
        var newPosition = false;
        // The function which sends the POST request back to the server
        var updateBoards = function(ui, listObj) {
                var categoryId = $(listObj).attr("id").substring(9, $(listObj).attr("id").lastIndexOf("_")),
                    curBoardElement = $(ui.item),
                    boardId = curBoardElement.attr("id").substring(6),
                    newIndex = curBoardElement.index(),
                    previousBoard = 0;

                // Not liking having to check any next tr elements -.-
                if ($(ui.item).next().length > 0) {
                    var nextBoardId = $(ui.item).next().attr("id");
                    // Silently abort...
                    if (nextBoardId.substring(nextBoardId.lastIndexOf("_") + 1) == "children") {
                        return false;
                    }
                }

                // If the index isnt zero (top), we can grab the previous board to use
                if (newIndex != 0) {
                    var previousBoard = curBoardElement.prev();
                    
                    // Make sure we arent grabbing child boards
                    if (previousBoard.attr("id").substring(previousBoard.attr("id").lastIndexOf("_") + 1) == "children")
                            previousBoard = previousBoard.prev();
                            
                    previousBoard = previousBoard.attr("id").substring(6);
                }

                // Quick hax to move the child boards under parent board
                if ($("#board_" + boardId + "_children").length > 0)
                    $("#board_" + boardId + "_children").insertAfter(curBoardElement);

                // Send off the data!
                $.post("index.php", {
                    action : "xmlhttp",
                    sa : "reorderboards",
                    index : newIndex,
                    boardId : boardId,
                    targetCategoryId : categoryId,
                    prevBoardId : previousBoard,
                    xml : 1,
                },

                function(data, textStatus, jqXHR) {
                    var xml = $(data);
                    if (xml.find("reorderBoards").text() == 1) {
                        alert(settings.successText);
                    }
                }, "xml");
                
                return true;
        };

        this.sortable({
            connectWith : ".content",
            axis : "y",
            items : "tr." + settings.parentBoardClass,
            cancel : "tr:not(." + settings.parentBoardClass + ")", // Prevent child boards being moved
            revert : true, // Adds a neat animation when the list item is dropped
            update : function(event, ui) { // Invoked when the list has been newly sorted
                // Check if the new board is being received from a different category
                newPosition = !ui.sender;
            },
            stop : function(event, ui) {
                if (newPosition) { 
                    updateBoards(ui, this);
                    newPosition = false; 
                }
                
                return true;
            },
            receive : function(event, ui) {
                if (!confirm("Are you sure you wish to move this board to a new category?")) {
                    $(ui.sender).sortable("cancel");
                    return false;    
                }
                
                updateBoards(ui, this);
                return true;
            }
        });

        return this;
    }
})(jQuery);