<?php
include_once('TwitterAPIExchange.php');
include_once("model/model.php");

function post($settings)
{
    /** URL for REST request, see: https://dev.twitter.com/docs/api/1.1/ **/
    $url = 'https://api.twitter.com/1.1/blocks/create.json';
    $requestMethod = 'POST';

    /** POST fields required by the URL above. See relevant docs as above **/
    $postfields = array(
        'screen_name' => 'usernameToBlock',
        'skip_status' => '1'
    );

    /** Perform a POST request and echo the response **/
    $twitter = new TwitterAPIExchange($settings);
    echo $twitter->buildOauth($url, $requestMethod)
        ->setPostfields($postfields)
        ->performRequest();
}

/**
 * https://dev.twitter.com/rest/reference/get/followers/ids
 * @param $param
 */

function getFollowerIds($param)
{
    $url = $param['api_end_point'] . '/followers/ids.json';
    $screen_name = $param['params']['screen_name'];
    $cursor = !empty($param['params']['cursor']) ? $param['params']['cursor'] : -1;
    $count = 5000;

    $getfield = "?screen_name=$screen_name&cursor=$cursor&count=$count&stringify_ids=true";
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();

    return $response;
}


/**
 * https://dev.twitter.com/rest/reference/get/followers/list
 * @param $param
 */
function getFollowerList($param) {
    $url = $param['api_end_point'] . '/followers/list.json';
    $screen_name = $param['params']['screen_name'];
    $cursor = !empty($param['params']['cursor']) ? $param['params']['cursor'] : -1;

    $getfield = "?screen_name=$screen_name&cursor=$cursor&skip_status=true&include_user_entities=false";
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();

    return $response;
}

/**
 * https://dev.twitter.com/rest/reference/get/friends/list
 * @param $param
 * @return string
 * @throws Exception
 */
function getFriendList($param) {
    $url = $param['api_end_point'] . '/friends/list.json';
    $screen_name = $param['params']['screen_name'];
    $cursor = !empty($param['params']['cursor']) ? $param['params']['cursor'] : -1;

    $getfield = "?screen_name=$screen_name&cursor=$cursor&skip_status=true&include_user_entities=false";
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();

    return $response;
}

/**
 * Check relationship between two arbitrary users
 * @param $param
 */
function getFriendShipShow($param) {
    $url = $param['api_end_point'] . '/friendships/show.json';
    $source_id = $param['params']['source_id'];
    $target_id = $param['params']['target_id'];
    $getfield = "?source_id=$source_id&target_id=$target_id";
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();

    return $response;
}

/**
 * https://dev.twitter.com/rest/reference/get/friendships/lookup
 * @param $param
 * Return relationship between source id and a list of target ids
 */
function getFriendShipLookUp($param) {
    $url = $param['api_end_point'] . '/friendships/lookup.json';
    $user_id = implode(",", $param['params']['user_id']);
    $getfield = "?user_id=$user_id";
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();

    return $response;
}

function createFriend($param) {
    $url = $param['api_end_point'] . '/friendships/create.json';
    $postField = array("screen_name" => $param['params']['user_id'], "follow" => true);
    $requestMethod = 'POST';

    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->buildOauth($url, $requestMethod)
        ->setPostfields($postField)
        ->performRequest();

    return $response;
}

function verifyCredential($param) {
    if(!isset($_SESSION)) session_start();


    $url = $param['api_end_point'] . '/account/verify_credentials.json';
    $getfield = '?include_entities=false&skip_status=true';
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();


    $_SESSION['current_user'] = $response;

    return $response;
}

/**
 * https://dev.twitter.com/rest/reference/post/friendships/destroy
 * @param $param
 * Unfollow a user
 */
function friendshipDestroy($param) {
    $url = $param['api_end_point'] . '/friendships/destroy.json';
    $user_id = $param['params']['user_id'];

    $requestMethod = 'POST';
    $postFields = array("user_id" => $user_id);

    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->buildOauth($url, $requestMethod)
        ->setPostfields($postFields)
        ->performRequest();

    //save to db
    if(!array_key_exists("errors", json_decode($response))) {
        $current_user =  json_decode($_SESSION['current_user'], true);
        $source_id = $current_user['id'];
        $target_id = $user_id;
        $model = new Model();
        $model->deleteFollower($source_id, $target_id);
    }
    return $response;
}

function friendshipCreate($param) {
    $url = $param['api_end_point'] . '/friendships/create.json';
    $user_id = $param['params']['user_id'];

    $requestMethod = 'POST';
    $postFields = array("user_id" => $user_id, 'follow' => true);

    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->buildOauth($url, $requestMethod)
        ->setPostfields($postFields)
        ->performRequest();

    //save to db
    if(!array_key_exists("errors", json_decode($response))) {
        $current_user =  json_decode($_SESSION['current_user'], true);
        $source_id = $current_user['id'];
        $target_id = $user_id;
        $model = new Model();
        $model->saveFollowing($source_id, $target_id);
    }

    return $response;
}

function getAllFollowing($param) {
    $user_id = $param['params']['user_id'];
    $day = !empty($param['params']['day']) ? $param['params']['day'] : 0;

    $day = ($day < 0) ? 0 : ($day > 60) ? 60 : $day;
    $model = new Model();
    return $model->getAllFollowing($user_id, $day);
}

function getAllUnfollowed($param) {
    $user_id = $param['params']['user_id'];
    $model = new Model();
    return $model->getAllUnfollowed($user_id);
}
/**
 * https://dev.twitter.com/rest/reference/get/users/lookup
 * @param $param
 * Return fully hydrated user objects for up to 100 users per request
 */
function getUsersLookup ($param) {
    $url = $param['api_end_point'] . '/users/lookup.json';
    $user_id = implode(",", $param['params']['user_id']);

    $getfield = "?user_id=$user_id";
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();

    return $response;
}

function deleteFollowing($param) {
    $source_id = $param['params']['source_id'];
    $target_id = $param['params']['target_id'];

    $model = new Model();
    $model->deleteFollowing($source_id, $target_id);
}

function saveCursorScreenName($param) {
    $source_id = $param['params']['source_id'];
    $target_screen_name = $param['params']['target_screen_name'];
    $cursor = $param['params']['cursor'];
    $type = $param['params']['type'];

    $model = new Model();
    $model->saveCursorScreenName($source_id, $target_screen_name, $cursor, $type);
}

function getCursorScreenName ($param) {
    $source_id = $param['params']['source_id'];
    $target_screen_name = $param['params']['target_screen_name'];
    $type = $param['params']['type'];

    $model = new Model();
    return $model->getCursorScreenName($source_id, $target_screen_name, $type);
}

function sendDirectMessage($param) {
    $url = $param['api_end_point'] . '/direct_messages/new.json';
    $screen_name = $param['params']['screen_name'];
    $text = $param['params']['message'];

    $requestMethod = 'POST';
    $postFields = array("screen_name" => $screen_name, 'text' => $text);

    $twitter = new TwitterAPIExchange($param['settings']);
    $response = $twitter->buildOauth($url, $requestMethod)
        ->setPostfields($postFields)
        ->performRequest();

    return $response;
}