<?php

  // deltwitter v0.6b1
  //
  // Copyright (c) 2016-2020, Yahe
  // All rights reserved.
  //
  // Usage:
  // > php ./deltwitter.php "<path to tweet.js file>" ("<tweet id to resume>")
  //
  // This application is released under the BSD license.
  // See the LICENSE file for further information.

  // ========== STOP EDITING HERE IF YOU DO NOT KNOW WHAT YOU ARE DOING ==========

  // some composer magic
  require_once(__DIR__."/vendor/autoload.php");

  // we use the TwitterOAuth class
  use Abraham\TwitterOAuth\TwitterOAuth;

  // include the configuration
  require_once(__DIR__."/config.php");

  // static definition of success return code
  define("SUCCESS_CODE", 200);

  // static definition of JS field
  define("TWEET_ARRAY_PREFIX", "window.YTD.tweet.part0 =");
  define("TWEET_ID_FIELD",     "id");

  function main($arguments) {
    global $content;
    global $entry;
    global $length;
    global $position;
    global $structure;

    // check the first parameter to see if it's a valid file
    if (isset($arguments[1]) && is_file($arguments[1])) {
      // read whole file into memory, we don't care about memory usage
      $content = file_get_contents($arguments[1]);
      if (false !== $content) {
        // start with handling from (optional) second argument
        $start_id = null;
        if (isset($arguments[2]) && is_numeric($arguments[2])) {
          $start_id = $arguments[2];
        }
        $started = (null === $start_id);

        // check that the file starts with the tweet array prefix
        if (0 === stripos($content, TWEET_ARRAY_PREFIX)) {
          // remove the tweet array prefix,
          // JSON-decode the result
          $content = json_decode(trim(substr($content, strlen(TWEET_ARRAY_PREFIX))), true);

          if (is_array($content) && (0 < count($content))) {
            // use TwitterOAuth to create connection
            $connection = new TwitterOAuth(API_KEY, API_SECRET_KEY, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

            // set timeouts
            $connection->setTimeouts(30, 30);

            foreach ($content as $entry) {
              if (isset($entry[TWEET_ID_FIELD]) && (0 < strlen($entry[TWEET_ID_FIELD]))) {
                if ($started) {
                  // execute the destroy POST request
                  $result = $connection->post("statuses/destroy/".$entry[TWEET_ID_FIELD],
                                              ["id" => $entry[TWEET_ID_FIELD]]);

                  // check the return code
                  if (SUCCESS_CODE === $connection->getLastHttpCode()) {
                    print("DELETED: ".$entry[TWEET_ID_FIELD]."\n");
                  } else {
                    print("FAILED:  ".$entry[TWEET_ID_FIELD]."\n");
                  }
                } else {
                  // check if we've reached the ID to resume
                  $started = (0 === strcasecmp($start_id, $entry[TWEET_ID_FIELD]));

                  if ($started) {
                    print("RESUMED: ".$entry[TWEET_ID_FIELD]."\n");
                  } else {
                    print("IGNORED: ".$entry[TWEET_ID_FIELD]."\n");
                  }
                }
              } else {
                print("SKIPPED: ".json_encode($entry)."\n");
              }
            }
          } else {
            print("ERROR: STRUCTURE COULD NOT BE READ\n");
          }
        } else {
          print("ERROR: FILE DOES NOT CONTAIN TWEET ARRAY PREFIX\n");
        }
      } else {
        print("ERROR: FILE COULD NOT BE READ\n");
      }
    } else {
      print("ERROR: FIRST PARAMETER IS NOT A FILE\n");
    }
  }

  main($argv);

?>
