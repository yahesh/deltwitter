<?php

  // deltwitter v0.0.0.0.0.0.0.0.0.5
  //
  // Copyright (c) 2016-2019, Yahe
  // All rights reserved.
  //
  // Usage:
  // > php ./deltwitter.php "<path to tweets.csv file>" ("<tweet id to resume>")
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

  // static definitions of special characters
  define("NF", ",");  // new field
  define("NL", "\n"); // new line
  define("SI", "\""); // string identifier

  // static definition of CSV field
  define("TWEET_ID_FIELD", "tweet_id");

  // global variables to represent state
  $content   = null; // content of CSV file
  $cur       = null; // current character
  $entry     = null; // currently read CSV record
  $length    = null; // length of content
  $nex       = null; // next character (look-ahead)
  $position  = 0;    // current position within string
  $structure = null; // structure of CSV records

  function field_name($index) {
    global $structure;

    $result = $index;

    if (is_array($structure)) {
      if (isset($structure[$index])) {
        $result = $structure[$index];
      }
    }

    return $result;
  }

  function next_char() {
    global $content;
    global $cur;
    global $length;
    global $nex;
    global $position;

    $result = false;

    // set to default
    $cur = null;
    $nex = null;

    if ($position < $length) {
      // $position should be 0 at least now
      if ($position >= 0) {
        // set the current value correctly
        $cur = $content[$position];

        // when reading the last character, $nex must be null
        if ($position < $length-1) {
          $nex = $content[$position+1];
        }

        // we could read a character
        $result = true;
      }

      // increase position
      $position++;
    }

    return $result;
  }

  function next_record() {
    global $cur;
    global $entry;
    global $nex;
    global $structure;

    // set to default
    $entry = array();

    // loop state
    $break       = false;
    $end_of_file = false;
    $field       = "";
    $in_string   = false;

    do {
      next_char();

      if ($in_string) {
        switch ($cur) {
          // a string identifier may either end the string or just be escaped by a second string identifier
          case SI: {
            switch ($nex) {
              // check if the character that follows finalizes the field or the record
              case null:
              case NF:
              case NL: {
                // we've finished the string
                $in_string = false;

                break;
              }

              // check if the character that follows is also a string identifier
              case SI: {
                // escape the string identifier
                $field .= $cur;

                // ignore the next character
                next_char();

                break;
              }

              // everything else is an error
              default: {
                $break = true;
              }
            }

            break;
          }

          // by default add character to the field
          default: {
            $field .= $cur;
          }
        }
      } else {
        switch ($cur) {
          // a comma outside a string represents a new field
          case NF: {
            // set entry field
            $entry[field_name(count($entry))] = $field;
            
            // start a new field
            $field = "";

            break;
          }

          // a newline or EOF outside a string ends the record
          case null:
          case NL: {
            // check if the line did not contain any values
            if ((null === $cur) &&
                (0 === count($entry)) &&
                (0 === strlen($field))) {
              // kill the main loop
              $end_of_file = true;
            } else {
              // set entry field
              $entry[field_name(count($entry))] = $field;

              // start a new field
              $field = "";
            }

            $break = true;
            break;
          }

          // a string identifier starts a string
          case SI: {
            // start the string
            $in_string = true;

            // when the current field is not empty, we have an error
            if (0 < strlen($field)) {
              $break = true;
            }

            break;
          }

          // by default add character to the field
          default: {
            $field .= $cur;
          }
        }
      }
    } while (!$break);

    return (!($in_string || $end_of_file));
  }

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

        // set length variable and initialize position
        $length   = strlen($content);
        $position = 0;

        // read first record that contains field names
        if (next_record()) {
          // set $structure to read structure
          $structure = $entry;

          // use TwitterOAuth to create connection
          $connection = new TwitterOAuth(API_KEY, API_SECRET_KEY, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

          // set timeouts
          $connection->setTimeouts(30, 30);

          // iterate through all records
          while (next_record()) {
            // when "tweet_id" field is not empty, it's a tweet
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
        print("ERROR: FILE COULD NOT BE READ\n");
      }
    } else {
      print("ERROR: FIRST PARAMETER IS NOT A FILE\n");
    }
  }

  main($argv);

?>
