<?php
if(!isset($_SESSION))   session_start();

include_once("secret.php");
include_once("backend.php");

if(isset($_SESSION['ot']) && isset($_SESSION['ots']))
{
    $oauth_access_token = $_SESSION['ot'];
    $oauth_access_token_secret = $_SESSION['ots'];
}
else
{
    header("Location:twit-login.php");
    die();
}

/** Set access tokens here - see: https://dev.twitter.com/apps/ **/
$settings = array(
    'oauth_access_token' => $oauth_access_token,
    'oauth_access_token_secret' => $oauth_access_token_secret,
    'consumer_key' => $consumer_key,
    'consumer_secret' => $consumer_secret
);
$api_end_point = "https://api.twitter.com/1.1";

if(isset($_REQUEST['ajax_tweet_function'])) {
    $param = array(
                'settings' => $settings,
                'api_end_point' => $api_end_point,
                'params' => isset($_REQUEST['ajax_tweet_param']) ? $_REQUEST['ajax_tweet_param'] : ""
            );
    echo call_user_func($_REQUEST['ajax_tweet_function'], $param);
    exit();
}
?>
<?php include_once("header.php") ?>
<div class="container">
    <div id="userInfo" class="row">

    </div>
    <div class="row">
        <div class="col-lg-12 menu">
            <a href="#" data-type="followers" class="btn btn-primary filter">Followers</a>
            <a href="#" data-type="friends" class="btn btn-primary filter">Friends</a>
            <a href="#" data-type="not-followers" class="btn btn-primary filter">Not Following</a>
            <a href="#" data-type="copy" class="btn btn-primary filter">Copy</a>
        </div>
    </div>
    <div class="row hidden copyForm">
        <form id="copyForm" role="form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="screen_name" placeholder="@username" class="form-control" />
            </div>
            <div class="form-group">
                <label for="request_type">Request type</label>
                <select id="request_type" class="form-control">
                    <option value="followers">Followers</option>
                    <option value="friends">Friends</option>
                </select>
            </div>
            <div class="form-group">
                <label for="hide">Hide previously followed user:</label>
                 <input type="checkbox" id="previous_followed" class="form-control" />
                <label for="app">(Through this app)</label>
            </div>
            <button type="submit" value="Go" name="copy" class="btn btn-default">Submit</button>
        </form>
    </div>
    <div class="row hidden message">
        <label for="message">Send Direct Message</label>
        <textarea rows="3" id="message" class="form-control"></textarea>
        <button type="button" value="Go" id="send_message" class="btn btn-default">Send Message</button>
        Word count (Should be less than or equal 140):<label for="message length" id="message_length">0</label>
    </div>
    <div class="row card hidden">
        <h5><strong>Filter</strong></h5>
        <ul>
            <li>
                <p>Exclude people you have followed in the past
                    <select id="filterDay">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="7">7</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="60">60</option>
                    </select> days
                    <input type="button" id="filterNotFollowing" value="Filter" />
                </p>
            </li>
        </ul>
    </div>
    <div class="row donateButton">
        <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
        <!-- Undosto twitter -->
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="ca-pub-8883766202094793"
             data-ad-slot="4741842253"
             data-ad-format="auto"></ins>
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </div>
    <div id="listContainer">

    </div>
    <p class="pull-right">
        <button data-nextcursor="" id="nextCursor" class="hidden nextButton">Next</button>
    </p>
</div>
<!-- template part start -->
<script id="template-userInfo" type="text/x-handlebars-template">
    <div class="col-lg-12">
        <div class="media">
            <a class="thumbnail pull-left" href="https://twitter.com/{{user.screen_name}}">
                <img class="media-object" src="{{user.profile_image_url}}">
            </a>
            <div class="media-body">
                <h4 class="media-heading">{{user.name}}</h4>
                <p>
                    <span class="btn btn-xs btn-info">@{{user.screen_name}}</span>
                    <span class="btn btn-xs btn-info">{{user.followers_count}} followers</span>
                    <span class="btn btn-xs btn-warning">{{user.friends_count}} friends</span>
                    <a href="/logout.php" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-log-out"></span> Signout</a>
                </p>
            </div>
        </div>
    </div>
</script>
<script id="template-listContainer" type="text/x-handlebars-template">
<div class="row rowBorder" data-screen-name="{{user.screen_name}}">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <a class="thumbnail pull-left" href="https://twitter.com/{{user.screen_name}}">
            <img class="media-object" src="{{user.profile_image_url}}">
        </a>
        <div class="media-body">
            <h4 class="media-heading">{{user.name}}
                {{#if user.follower}}
                    <button class="follower btn btn-xs btn-success" title="follows me"><i class="glyphicon glyphicon-ok"></i> Follows me</button>
                {{/if}}
            </h4>
            <p>
                <a class="btn btn-xs btn-info" href="https://twitter.com/{{user.screen_name}}" target="_blank">@{{user.screen_name}}</a>
                <button class="btn btn-success btn-xs hidden message-status">Message Sent</button>
                {{#if user.following}}
                    <button class="unfollow btn btn-xs btn-danger pull-right" title="I follow" data-id="{{user.id}}"><i class="glyphicon glyphicon-remove"></i> Unfollow</button>
                {{else}}
                    <button class="follow btn btn-xs btn-success pull-right" title="I dont follow" data-id="{{user.id}}"><i class="glyphicon glyphicon-plus"></i> Follow</button>
                {{/if}}
            </p>
        </div>
    </div>
</div>
</script>
<script id="template-errorModal" type="text/x-handlebars-template">
    <!-- Modal -->
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Error</h4>
                </div>
                <div class="modal-body">
                    <p>{{{errors.message}}}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>
</script>
<script id="template-adModal" type="text/x-handlebars-template">
    <!-- Modal -->
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><a href="/advertise.php">Advertise with us</a></h4>
                </div>
                <div class="modal-body">
                    <p>{{{advertise.content}}}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>
</script>
<!-- template part end -->


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/handlebars.js"></script>
<script src="js/index.js"></script>
<script src="js/twitter.js"></script>
</body>
</html>