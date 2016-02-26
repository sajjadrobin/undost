<?php
include_once("elasticModel.php");

Class Model {
    private $link;
    private $elastic;


    function Model() {
        /*

        $host = "127.0.0.1"; //ini_get("mysqli.default_host")
        $user = "root";
        $password = "password";
        $db = "twitter";
        $port = 3306;
        //$link = mysqli_connect($host, $user, $password, $db, $port) or die("Error " . mysqli_connect_error());

        //$this->link = $link;
        */
        //elastic search initiate
        $this->elastic = new ElasticModel();
    }

    function saveFollowing ($source_id, $target_id) {
        assert($source_id > 0);
        assert($target_id > 0);
        $time = time();

        /*
        $query = "INSERT INTO following (source_id, target_id, created) VALUES (?,?,?)";
        $stmt = $this->link->prepare($query);
        $stmt->bind_param('ssi', $source_id, $target_id, $time);
        $response = $stmt->execute();
        $stmt->close();

        //save to elasticsearch
        if($response) {
            $body = array(
                    'source_id' => $source_id,
                    'target_id' => $target_id,
                    'time' => $time
                );
            $this->elastic->saveFollowing($body);
        }
        return $response;
        //*/
        $body = array(
            'source_id' => $source_id,
            'target_id' => $target_id,
            'time' => $time
        );
        $this->elastic->saveFollowing($body);
    }

    //save unfollow action into unfollowed table
    function deleteFollower ($source_id, $target_id) {
        assert($source_id > 0);
        assert($target_id > 0);
        $time = time();

        /*
        $query = "INSERT INTO unfollowed (source_id, target_id, created) VALUES (?,?,?)";
        $stmt = $this->link->prepare($query);
        $stmt->bind_param('ssi', $source_id, $target_id, $time);
        $response = $stmt->execute();
        $stmt->close();

        //save to elasticsearch
        if($response) {
            $body = array(
                'source_id' => $source_id,
                'target_id' => $target_id,
                'time' => $time
            );
            $this->elastic->saveUnFollowing($body);
        }
        return $response;
        //*/
        $body = array(
            'source_id' => $source_id,
            'target_id' => $target_id,
            'time' => $time
        );
        $this->elastic->saveUnFollowing($body);
    }

    function getAllFollowing ($source_id, $day = 0) {
        assert($source_id > 0);
        assert($day >= 0);
        $result =  $this->elastic->getFollowingList($source_id, $day);
        if(!empty($result)) {
            $temp = array();
            foreach($result as $rt) {
                $temp[] = $rt["_source"]["target_id"];
            }
            $result = $temp;
        }
        return json_encode($result);
    }

    function getAllUnfollowed($source_id) {
        assert($source_id > 0);
        $result =  $this->elastic->getUnfollowedList($source_id);
        if(!empty($result)) {
            $temp = array();
            foreach($result as $rt) {
                $temp[] = $rt["_source"]["target_id"];
            }
            $result = $temp;
        }
        return json_encode($result);
    }

    //remove following row from following tables
    function deleteFollowing($source_id, $target_id) {
        assert($source_id > 0);
        assert($target_id > 0);

        /*
        $query = "DELETE FROM following WHERE source_id = ? AND target_id = ?";
        $stmt = $this->link->prepare($query);
        $stmt->bind_param('ss',$source_id,$target_id);
        $response = $stmt->execute();
        $stmt->close();

        if($response) {
            $this->elastic->deleteFollowing($source_id, $target_id);
        }
        return $response;
        //*/
        $this->elastic->deleteFollowing($source_id, $target_id);
    }

    //get previous stored cursor for a source, target pair
    function saveCursorScreenName($source_id, $target_screen_name, $cursor, $type) {
        assert($source_id > 0);
        assert(strlen($target_screen_name) > 0);
        assert($cursor > 0);

        $result = $this->elastic->saveCursorScreenName($source_id, $target_screen_name, $cursor, $type);

        return $result;
    }

    function getCursorScreenName ($source_id, $target_screen_name, $type) {
        assert($source_id > 0);
        assert(strlen($target_screen_name) > 0);

        $result = $this->elastic->getCursorScreenName($source_id, $target_screen_name, $type);
        return json_encode($result);
    }
}
