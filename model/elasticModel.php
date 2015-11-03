<?php
$document_root = $_SERVER['DOCUMENT_ROOT'];
include_once ($document_root."/vendor/autoload.php");

Class ElasticModel {

    private $globalParam;
    private $client;

    function ElasticModel() {
        $params = array();
        $params['hosts'] = array('127.0.0.1:9200');

        $this->globalParam = $params;
        $this->client = new Elasticsearch\Client($this->globalParam);
    }

    function saveFollowing($body) {
        $params = array();
        $params['index'] = 'twitter';
        $params['type'] = 'following';
        $params['body'] = $body;

        //check duplicate document
        $duplicate = $this->duplicateDocumentCheck($params['index'], $params['type'],
                        array('source_id' => $params['body']['source_id'], 'target_id' => $params['body']['target_id']));

        if(empty($duplicate)){
            $this->client->index($params);
        }
    }

    function saveUnFollowing($body) {
        $params = array();
        $params['index'] = 'twitter';
        $params['type'] = 'unfollowed';
        $params['body'] = $body;

        //check duplicate document
        $duplicate = $this->duplicateDocumentCheck($params['index'], $params['type'],
            array('source_id' => $params['body']['source_id'], 'target_id' => $params['body']['target_id']));

        if(empty($duplicate)){
            $this->client->index($params);
        }
    }

    function getFollowingList($source_id, $day = 0) {
        assert($source_id > 0);
        assert($day >= 0);

        $params = array(
            "search_type" => "scan",    // use search_type=scan
            "scroll" => "10s",          // how long between scroll requests. should be small!
            "size" => 20,               // how many results *per shard* you want back
            "index" => "twitter",
            "type" => "following",
            "body" => array(
                "query" => array(
                    "filtered" => array(
                        "query" => array(
                            "match" => array("source_id" => $source_id)

                        ),
                        "filter" => array(
                            "range" => array("time" => array("lte" => time() - $day*24*60*60))
                        )
                    )
                )
            )
        );

        $docs = $this->client->search($params);   // Execute the search
        $scroll_id = $docs['_scroll_id'];   // The response will contain no results, just a _scroll_id
        $result = array();

        // Now we loop until the scroll "cursors" are exhausted
        while (\true) {

            // Execute a Scroll request
            $response = $this->client->scroll(
                array(
                    "scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
                    "scroll" => "10s"           // and the same timeout window
                )
            );

            // Check to see if we got any search hits from the scroll
            if (count($response['hits']['hits']) > 0) {
                $result = array_merge($result, $response['hits']['hits']);
                $scroll_id = $response['_scroll_id'];
            } else {
                // No results, scroll cursor is empty.  You've exported all the data
                break;
            }
        }

        return $result;
    }

    function getUnfollowedList($source_id) {
        assert($source_id > 0);

        $params = array(
            "search_type" => "scan",    // use search_type=scan
            "scroll" => "10s",          // how long between scroll requests. should be small!
            "size" => 20,               // how many results *per shard* you want back
            "index" => "twitter",
            "type" => "unfollowed",
            "body" => array(
                "query" => array(
                    "match" => array("source_id" => $source_id)

                )
            )
        );

        $docs = $this->client->search($params);   // Execute the search
        $scroll_id = $docs['_scroll_id'];   // The response will contain no results, just a _scroll_id
        $result = array();

        // Now we loop until the scroll "cursors" are exhausted
        while (\true) {

            // Execute a Scroll request
            $response = $this->client->scroll(
                array(
                    "scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
                    "scroll" => "10s"           // and the same timeout window
                )
            );

            // Check to see if we got any search hits from the scroll
            if (count($response['hits']['hits']) > 0) {
                $result = array_merge($result, $response['hits']['hits']);
                $scroll_id = $response['_scroll_id'];
            } else {
                // No results, scroll cursor is empty.  You've exported all the data
                break;
            }
        }
        return $result;
    }

    function deleteFollowing($source_id, $target_id) {
        assert($source_id > 0);
        assert($target_id > 0);

        $searchParams['index'] = 'twitter';
        $searchParams['type'] = 'following';
        $searchParams['body']['query']['bool']['must'] = array(
            array('match' => array('source_id' => $source_id)),
            array('match' => array('target_id' => $target_id))
        );

        $retDoc = $this->client->search($searchParams);
        if (count($retDoc['hits']['hits']) > 0) {
            $doc_id = $retDoc['hits']['hits'][0]['_id'];

            //delete the document
            $deleteParams = array();
            $deleteParams['index'] = 'twitter';
            $deleteParams['type'] = 'following';
            $deleteParams['id'] = $doc_id;
            $retDelete = $this->client->delete($deleteParams);
        }

    }

    function saveCursorScreenName($source_id, $target_screen_name, $cursor, $type='copy') {
        assert($source_id > 0);
        assert(strlen($target_screen_name) > 0);
        assert($cursor > 0);

        $params = array();
        $params['index'] = 'twitter';
        $params['type'] = $type;
        $params['body'] = array('source_id' => $source_id,
            'target_screen_name' => $target_screen_name, 'cursor' => $cursor);

        $document = $this->getCursorScreenName($source_id, $target_screen_name, $type);
        if(empty($document)) {
            $this->client->index($params);
        }
        else {
            $params['id'] = $document["_id"];
            $params['body'] = array('doc' => array('cursor' => $cursor));
            $this->client->update($params);
        }
    }

    function getCursorScreenName ($source_id, $target_screen_name, $type = 'copy') {
        assert($source_id > 0);
        assert(strlen($target_screen_name) > 0);

        $searchParams['index'] = 'twitter';
        $searchParams['type'] = $type;
        $searchParams['body']['query']['bool']['must'] = array(
            array('match' => array('source_id' => $source_id)),
            array('match' => array('target_screen_name' => $target_screen_name))
        );

        $retDoc = $this->client->search($searchParams);
        $document = array();

        if (count($retDoc['hits']['hits']) > 0) {
            $document = $retDoc['hits']['hits'][0];
        }
        return $document;
    }

    function duplicateDocumentCheck ($index = 'twitter', $type, $params = array()) {
        $searchParams['index'] = $index;
        $searchParams['type'] = $type;

        if(!empty($params)) {
            foreach($params as $key => $ps) {
                $searchParams['body']['query']['bool']['must'][] = array('match' => array($key => $ps));
            }
        }

        $retDoc = $this->client->search($searchParams);
        $document = array();

        if (count($retDoc['hits']['hits']) > 0) {
            $document = $retDoc['hits']['hits'][0];
        }
        return $document;
    }
}
