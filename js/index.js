/**
 * Created by saro on 28/04/15.
 */

"use strict";

var Twitter = function() {

    var baseURL = '../index.php?ajax_tweet_function=',
        current_user = {},
        current_friend_ids = [],
        current_friend_list = {},
        cursor = -1,
        next_action = "friends",
        all_friend_ids = [],
        nextCount = 0,
        notFollowingIds = [],
        all_unfollowed_ids = [],
        difference_follower_unfollower = [],
        errors = {},
        all_follower_ids = [];

    var arrayDifference = function(a, b) {
        return $.grep(a, function(i) {
            return $.inArray(i, b) == -1;
        });
    };

    var deleteFollowing = function(target_id) {
        return $.ajax({
            "url" : baseURL + 'deleteFollowing',
            "method" : "POST",
            "data" : {"ajax_tweet_param": {"target_id" : target_id, "source_id" : current_user.id}}
        });
    };

    var advertiseContent = [{"content" : "Follow me"}];

    return {
        verifyCredential : function () {
            var parent = this;
            return $.getJSON(baseURL + 'verifyCredential')
                .done(function (response) {
                    current_user = response;
                });
        },

        getCurrentUser : function() {
            return current_user;
        },

        loadAllFriendList: function(day) {
            day = day || 0;
            all_friend_ids = [];
            return $.getJSON(baseURL + 'getAllFollowing', {"ajax_tweet_param": {"user_id": current_user.id, day: day}})
                .then(function(response) {
                    all_friend_ids = response;
                    return all_friend_ids;
                });

        },

        loadUnfollowedList : function() {
            all_unfollowed_ids = [];
            return $.getJSON(baseURL + 'getAllUnfollowed', {"ajax_tweet_param": {"user_id": current_user.id}})
                .then(function(response){
                    all_unfollowed_ids = response;
                    return all_unfollowed_ids;
                })
        },

        loadFriendList : function (screen_name, mine) {
            var parent = this,
                previously_followed = !$("#previous_followed").is(":hidden") && $("#previous_followed").is(":checked");
            cursor = cursor || -1;
            screen_name = screen_name || current_user.screen_name;

            // only for logged in user
            if(!all_friend_ids.length && mine) {
                this.loadAllFriendList();
            }

            return $.getJSON(baseURL + 'getFriendList',
                {"ajax_tweet_param": {"screen_name": screen_name, "cursor" : cursor}})
                .done(function (response) {
                    if("errors" in response) {
                        errors = response["errors"][0];
                        return errors;
                    }
                    else {
                        errors = {};
                        current_friend_ids = [];
                        current_friend_list = {};
                        cursor = response.next_cursor_str;

                        if(previously_followed && all_friend_ids.length) {
                            $.each(response.users, function (index, user) {
                                if(($.inArray(user.id_str, all_friend_ids) === -1) && ($.inArray(user.id_str, all_unfollowed_ids) === -1)){
                                    current_friend_ids.push(user.id);
                                    current_friend_list[user.id] = user;
                                }
                            });
                        }
                        else {
                            $.each(response.users, function (index, user) {
                                current_friend_ids.push(user.id);
                                current_friend_list[user.id] = user;
                            });
                        }
                    }
                });

        },

        loadFollowerIds : function(screen_name){
            var parent = this;

            cursor = cursor || -1,
            screen_name = screen_name || current_user.screen_name;

            return $.getJSON(baseURL + 'getFollowerIds',
                {"ajax_tweet_param": {"screen_name": screen_name, "cursor" : cursor}})
                .then(function(response) {
                    if("ids" in response) {
                        all_follower_ids = response["ids"];
                    }
                })
        },

        loadFollowerList : function(screen_name) {
            var parent = this,
                previously_followed = !$("#previous_followed").is(":hidden") && $("#previous_followed").is(":checked"),
                count = 50;

            cursor = cursor || -1,
            screen_name = screen_name || current_user.screen_name;

            return $.getJSON(baseURL + 'getFollowerList',
                {"ajax_tweet_param": {"screen_name": screen_name, "cursor" : cursor, "count" : count}})
                .then(function (response) {
                    if("errors" in response) {
                        errors = response["errors"][0];
                        return errors;
                    }
                    else {
                        errors = {};
                        current_friend_ids = [];
                        current_friend_list = {};
                        cursor = response.next_cursor_str;

                        if (previously_followed && all_friend_ids.length) {
                            $.each(response.users, function (index, user) {
                                if(($.inArray(user.id_str, all_friend_ids) === -1) && ($.inArray(user.id_str, all_unfollowed_ids) === -1)){
                                    current_friend_ids.push(user.id);
                                    current_friend_list[user.id] = user;
                                }
                            });
                        }
                        else {
                            $.each(response.users, function (index, user) {
                                current_friend_ids.push(user.id);
                                current_friend_list[user.id] = user;
                            });
                        }
                    }
                });
        },

        getFriendList : function() {
            return current_friend_list;

        },

        loadFriendShipLookUp : function () {
            var parent = this;
            return $.getJSON(baseURL + 'getFriendShipLookUp', {"ajax_tweet_param": {"user_id": current_friend_ids}})
                .then(function (response) {
                    if("errors" in response) {
                        errors = response["errors"][0];
                        return errors;
                    }
                    else {
                        $.each(response, function (index, relation) {
                            current_friend_list[relation.id]['connections'] = relation.connections;
                        })
                        return current_friend_list;
                    }
                });
        },

        loadNotFollowers: function() {
            var parent = this,
                friendIds = [];

            friendIds = difference_follower_unfollower.slice(nextCount, nextCount+ 50);
            nextCount = nextCount + 50;
            return $.getJSON(baseURL + 'getFriendShipLookUp', {"ajax_tweet_param": {"user_id": friendIds}})
            .then(function (response) {
                if("errors" in response) {
                    errors = response["errors"][0];
                    return errors;
                }
                else {
                    errors = {};
                    notFollowingIds = [];
                    var connections = [];
                    $.each(response, function (index, relation) {
                        connections = relation.connections;
                        if ($.inArray("followed_by", connections) === -1
                            && ($.inArray("following", connections) > -1 || $.inArray("following_requested", connections) > -1)) {
                            notFollowingIds.push(relation.id);
                        }
                        else if($.inArray("none", connections) > -1){ //delete following
                            deleteFollowing(relation.id);
                        }
                    });
                    return notFollowingIds;
                }
            });
        },

        loadUsersLookup: function(userId) {
            return $.getJSON(baseURL + 'getUsersLookup', {"ajax_tweet_param": {"user_id": userId}})
                .then(function (response) {
                    current_friend_list = {};
                    $.each(response, function (index, user) {
                        current_friend_list[user.id] = user;
                    });
                });
        },

        getCursor : function() {
            return cursor;
        },

        buildList: function() {
            var parent = this,
                htmList = $("<div />"),
                userList = Handlebars.compile($("#template-listContainer").html());

            $("#listContainer").html("");
            $.each(current_friend_list, function(index, user) {
                user.follower = ($.inArray("followed_by", user.connections) !==-1) ? true : false;
                user["following"] = user["following"] || user["follow_request_sent"];
                htmList.append(userList({"user" : user}));
            });

            $("#listContainer").html(htmList.html());
            $("#listContainer").prepend('<div class="row"><div class="col-lg-12"><h4>Showing total users: '+ Object.keys(current_friend_list).length + '</h4><hr></div></div>');
            $("#listContainer").append('<div class="row"><div class="col-lg-12"><hr><h4>Showing total users: '+ Object.keys(current_friend_list).length + '</h4></div></div>');

            if(cursor > 0 || nextCount > 0) {
                $("#nextCursor").data("nextcursor", cursor).removeClass("hidden");
            }
            else {
                $("#nextCursor").data("nextcursor", -1).addClass("hidden");
            }
        },

        prepareContent : function(loadFriendShipLookUp) {
            var parent = this;

            loadFriendShipLookUp = (typeof loadFriendShipLookUp !== 'undefined') ? loadFriendShipLookUp : true;

            if(loadFriendShipLookUp) {
                parent.loadFriendShipLookUp()
                .done(function () {
                    parent.buildList();
                });
            }
            else {
                parent.buildList();
            }
        },

        friendshipCreate : function(user_id) {
            var parent = this;
            return $.ajax({
                "url" : baseURL + 'friendshipCreate',
                "method" : "POST",
                "data" : {"ajax_tweet_param": {"user_id" : user_id}},
                "dataType" : "json"
            }).done(function(response){
                errors = {};
                if("errors" in response) {
                    errors = response["errors"][0];
                }
            });
        },

        sendDirectMessage: function(screen_name) {
            var parent = this,
                message = $("#message").val();

            if(!message.length) {
                return;
            }
            return $.ajax({
                "url" : baseURL + 'sendDirectMessage',
                "method" : "POST",
                "data" : {"ajax_tweet_param": {"screen_name" : screen_name, 'message' : message}},
                "dataType" : "json"
            })
            .done(function(response){
                var row_screen_name;
                $("#listContainer").children(".row").each(function(key, value) {
                    row_screen_name = $(this).data('screen-name');
                    if(row_screen_name === screen_name) {
                        $(this).find(".message-status").removeClass("hidden");
                    }
                });
            });

        },

        friendshipDestroy : function(user_id) {
            var parent = this;
            return $.ajax({
                "url" : baseURL + 'friendshipDestroy',
                "method" : "POST",
                "data" : {"ajax_tweet_param": {"user_id" : user_id}},
                "dataType" : "json"
            })
            .done(function(){
                deleteFollowing(user_id);
            });
        },

        actionOnFilter : function(action, screen_name, already_done) {
            var parent = this,
                active_menu = $(".menu").children(".active").data("type"),
                mine = false;

            next_action = action || next_action;
            screen_name = screen_name || current_user.screen_name;
            already_done = (typeof already_done === "undefined") ? false : already_done;

            if(active_menu !== 'copy') {
                $(".copyForm").addClass("hidden");
                $("#copyForm").find("#screen_name").val("");
                mine = true;
            }

            $(".message").addClass("hidden");
            $(".card").addClass("hidden");
            $("#listContainer").html("");

            switch (next_action) {
                case "followers":
                    if(active_menu !== 'copy') {
                        $(".message").removeClass("hidden");
                    }
                    parent.loadAllFriendList()
                    .always(function() {
                        parent.loadUnfollowedList()
                            .always(function(){
                                parent.loadFollowerList(screen_name)
                                    .then(function () {
                                        //save last cursor position for better copy followers and friends
                                        parent.saveCursorScreenName(screen_name, next_action);

                                        if(!current_friend_ids.length && !Object.keys(errors).length) {
                                            parent.actionOnFilter(action, screen_name);
                                        }
                                        else {
                                            parent.prepareContent();
                                        }
                                    });
                            })
                    });
                    break;
                case "friends" :
                    parent.loadAllFriendList()
                    .always(function() {
                        parent.loadUnfollowedList()
                            .always(function(){
                                parent.loadFriendList(screen_name, mine)
                                    .then(function () {
                                        //save last cursor position for better copy followers and friends
                                        parent.saveCursorScreenName(screen_name, next_action);

                                        if(!current_friend_ids.length && !Object.keys(errors).length) {
                                            parent.actionOnFilter(action, screen_name);
                                        }
                                        else {
                                            parent.prepareContent();
                                        }
                                    });
                            });
                    });
                    break;
                case "not-followers":
                    $(".card").removeClass("hidden");
                    var day = $("#filterDay").val();

                    if((!all_friend_ids.length || !all_unfollowed_ids.length) && !already_done) {
                        var d1 = parent.loadAllFriendList(day);
                        var d2 = parent.loadUnfollowedList();
                        var d3 = parent.loadFollowerIds();

                        already_done = true;
                        $.when(d1, d2, d3)
                            .done(function() {
                                difference_follower_unfollower = arrayDifference(all_friend_ids, all_follower_ids);
                                difference_follower_unfollower = arrayDifference(difference_follower_unfollower, all_unfollowed_ids);

                                parent.actionOnFilter(action, screen_name, already_done);
                            })
                    }
                    else {
                        parent.loadNotFollowers()
                            .done(function(){
                                if(notFollowingIds.length) {
                                    parent.loadUsersLookup(notFollowingIds)
                                        .done(function () {
                                            parent.prepareContent(false);
                                        });
                                }
                                else if (difference_follower_unfollower.slice(nextCount, nextCount + 50).length && !Object.keys(errors).length) {
                                    parent.actionOnFilter(action, screen_name);
                                }
                            });
                    }
                    break;
                case "copy":
                    $(".copyForm").removeClass("hidden");
                    break;
            }

            if(Object.keys(errors).length) {
                parent.showErrorModal();
            }
        },
        setNextCount: function(val) {
            nextCount = val;
        },
        resetCursor : function() {
            cursor = -1;
        },
        resetAllFriendIds : function() {
            all_friend_ids = [];
        },
        resetAllUnFollowedIds : function() {
            all_unfollowed_ids = [];
        },
        saveCursorScreenName: function(screen_name, type) {
            var parent = this;
            type = (type === 'followers') ? 'copy' : 'copy_friends';

            if(screen_name !== current_user.screen_name) {
                return $.getJSON(baseURL + 'saveCursorScreenName',
                    {"ajax_tweet_param": {"target_screen_name": screen_name, 'cursor' : parent.getCursor(), 'source_id': current_user.id_str, 'type': type}})
            }
        },
        getCursorScreenName: function(target_screen_name, type) {
            var parent = this;
            type = type || 'copy';
            return $.getJSON(baseURL + 'getCursorScreenName', {"ajax_tweet_param": {"target_screen_name": target_screen_name, 'source_id' : current_user.id_str, 'type': type}})
                .then(function(response){
                    if("_source" in response) {
                        cursor = response["_source"]["cursor"];
                    }
                });
        },
        showErrorModal : function (){
            if(Object.keys(errors).length) {
                var htmList = $("<div />"),
                    errorTemplate = Handlebars.compile($("#template-errorModal").html());

                htmList.html(errorTemplate({"errors": errors}));
                $("body").append(htmList.html());
                $('#myModal').modal({"show":true})
                    .modal('show');
            }
        },

        hasError : function() {
            return (Object.keys(errors).length > 0) ? true : false;
        }
    }
}

