/**
 * Created by saro on 28/04/15.
 */
jQuery(document).ready(
	function ($) {
		"user strict";

		var twitterObj = new Twitter();

		twitterObj.verifyCredential()
		.done(function(){
			var userHtml =  Handlebars.compile($("#template-userInfo").html());
			$("#userInfo").html(userHtml({"user" : twitterObj.getCurrentUser()}));
		});

		$(document).on("click", 'a.filter', function(event) {
			if(event.preventDefault) {
				event.preventDefault();
			}

			var $this = $(this),
				action = $this.data('type');

			$this.addClass("active")
				.siblings().removeClass("active");

			if(action === "not-followers") {
				$(".card").removeClass("hidden");
				$(".copyForm").addClass("hidden");
				$("#copyForm").find("#screen_name").val("");
				$("#listContainer").html("");
			}
			else {
				twitterObj.resetCursor();
				twitterObj.actionOnFilter(action);
			}
		});

		$(document).on("click", "#filterNotFollowing", function(event) {
			if(event.preventDefault) {
				event.preventDefault();
			}

			twitterObj.resetCursor();
			twitterObj.actionOnFilter("not-followers");
		});

		$(document).on("click", "#nextCursor", function(event) {
			if(event.stopPropagation) {
				event.stopPropagation();
			}

			var action = "",
				screen_name = "";

			if(!$("#copyForm").is(":hidden")) {
				action = $("#copyForm").find("#request_type").val();
				screen_name = $("#copyForm").find("#screen_name").val();

				if(screen_name.indexOf("@") === 0) {
					screen_name = screen_name.slice(1, screen_name.length);
				}
			}
			twitterObj.actionOnFilter(action, screen_name);

			var position = $("#listContainer").position();
			$("html,body").animate({"scrollTop" : position.top - 125}, 50);

		});

		$(document).on("click", ".unfollow", function(event){
			if(event.stopPropagation) {
				event.stopPropagation();
			}

			var $this = $(this),
                user_id = $(this).data("id");

			twitterObj.friendshipDestroy(user_id)
            .done(function(response){
				if(!("errors" in response)) {
					$this
						.removeClass("unfollow btn-danger")
						.addClass("follow btn-success")
						.text("Follow");

					var position = $this.parents("div.row").position();
					$("html,body").animate({"scrollTop": position.top - 125}, 50);
				}
				else {
					twitterObj.showErrorModal();
				}
            });
		});

        $(document).on("click", ".follow", function(event){
            if(event.stopPropagation) {
                event.stopPropagation();
            }

            var $this = $(this),
                user_id = $(this).data("id");

            twitterObj.friendshipCreate(user_id)
                .done(function(response){
					if(!("errors" in response)) {
						$this
							.removeClass("follow btn-success")
							.addClass("unfollow btn-danger")
							.text("Unfollow");

						var position = $this.parents("div.row").position();
						$("html,body").animate({"scrollTop" : position.top - 125}, 50);
					}
					else {
						twitterObj.showErrorModal();
					}
                });
        });

		$(document).on("submit", "#copyForm", function(event){
			if(event.preventDefault) {
				event.preventDefault();
			}
			var $this = $(this),
				screen_name = $this.find("#screen_name").val(),
				request_type = $this.find("#request_type").val();

			if(screen_name.indexOf("@") === 0) {
				screen_name = screen_name.slice(1, screen_name.length);
			}

			var type = (request_type === 'followers') ? 'copy' : 'copy_friends';
			twitterObj.getCursorScreenName(screen_name, type)
				.always(function(){
					twitterObj.actionOnFilter(request_type, screen_name);
				})
		});

		$(document).on("change", '#filterDay', function(event) {
			if(event.stopPropagation) {
				event.stopPropagation();
			}

			var $this = $(this),
				action = $(".active.filter").data('type');

			twitterObj.setNextCount(0);
			twitterObj.resetAllFriendIds();
			twitterObj.resetAllUnFollowedIds();
			twitterObj.actionOnFilter(action);
		});

		$(document).on("click", "#send_message", function(event){
			if(event.preventDefault) {
				event.preventDefault();
			}

			var screen_name,
				row;

			$("#listContainer").children(".row").each(function(key, value){
				screen_name = $(this).data('screen-name');
				if(screen_name) {
					twitterObj.sendDirectMessage(screen_name);
				}
			});
		});

		$(document).on("keyup", "#message" , function(event){
			$("#message_length").html($(this).val().length);
		})
		/*
		$(document).ajaxError(function(event, jqxhr, settings, thrownError){

			console.log(event);
			console.log(jqxhr);
			console.log(settings);
			console.log(thrownError);
		});//*/
	}
)