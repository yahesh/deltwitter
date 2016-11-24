<?php

  // deltwitter v0.0.0.0.0.0.0.0.0.1
  //
  // Copyright (c) 2016, Kenneth Newwood
  // All rights reserved.
  //
  // This script is a one-off approach to deleting all tweets and retweets
  // ever done on twitter by a given user account. It does not use the
  // official twitter API but rather reuses the REST endpoints of the
  // twitter.com website that don't seem to have any limit restrictions.
  //
  // The author of this script assures you that it has worked properly at
  // the time of writing. The author also assures you that this script
  // will NOT be updated in case the inner workings of the twitter.com
  // website change. If it does not work anymore you're on your own to
  // figure out what's broken and how to fix it.
  //
  // Preparation:
  // * In order to use this script you first have to log into the
  //   twitter.com website, start your preferred HTTP request interceptor
  //   (e.g. HTTP Live Headers for Mozilla Firefox) and capture the request
  //   that's sent when deleting a tweet by hand.
  // * The request will contain a lot of cookie values that you will have
  //   to configure down below. It's best to also set the USER_AGENT constant
  //   to the one of your browser. The constants in the script are named after
  //   their corresponding cookie value names.
  // * In addition you will have to request your twitter archive through your
  //   account settings page. It may take a while until you receive the
  //   corresponding download link.
  // * The twitter archive contains a file called "tweets.csv" that you have
  //   to use together with the script.
  // * Now you can execute the script by calling it and providing the path to
  //   the tweets.csv file along. Depending on the number of tweets and
  //   retweets you have this process may take a while.
  // * The tweet id of every deleted tweet and retweet is printed to the screen.
  // * Should it be necessary to abort the process and resume later, you should
  //   save the last processed tweet id.
  // * You can use a tweet id as an optional second parameter so that the deletion
  //   can proceed with the next tweet or retweet in the CSV file.
  //
  // Usage:
  // > php ./deltwitter.php "<path to tweets.csv file>" ("<tweet id to resume>")
  //
  // Things to note:
  // * This script contains a super-duper hand-written top-down parser for
  //   CSV files that uses a single look-ahead character and which does
  //   NOT care to handle any corner cases except multi-line values and
  //   escaped quotation marks. If this CSV parser fails for you, your
  //   totally free to fix it for your special cases. You've been warned.
  // * This script does not care to escape any values, be it parts of URLs,
  //   HTTP headers or even shell parameters. You'd be totally stupid to
  //   call this script with untrusted parameters. You've been warned.
  // * This script was written with the intention to be a lowest-effort
  //   approach to automating the deletion of tweets and retweets. If
  //   it breaks anything, it's up to you to fix it. There will be
  //   absolutely no support given to anyone using this script. You've
  //   been warned.
  //
  // This application is released under the BSD license.
  // See the LICENSE file for further information.

  // configure by knowledge
  define("USERNAME", ""); // this is meant to contain your twitter username in all lowercase

  // configure according to logged in browser headers
  define("_GA",                ""); // this is meant to contain the "_ga" cookie value
  define("_TWITTER_SESS",      ""); // this is meant to contain the "_twitter_sess" cookie value
  define("AUTH_TOKEN",         ""); // this is meant to contain the "auth_token" cookie value
  define("AUTHENTICITY_TOKEN", ""); // this is meant to contain the "authenticity_token" post data value
  define("KDT",                ""); // this is meant to contain the "kdt" cookie value
  define("GUEST_ID",           ""); // this is meant to contain the "guest_id" cookie value
  define("TWID",               ""); // this is meant to contain the "twid" cookie value
  define("USER_AGENT",         ""); // this is meant to contain a normal-looking user-agent string

  // ========== STOP EDITING HERE IF YOU DO NOT KNOW WHAT YOU ARE DOING ==========

  // static definitions of placeholders
  define("ID_PLACEHOLDER",   "<%ID%>");
  define("REFERER_REPLACER", "<%REFERER%>");
  define("URL_PLACEHOLDER",  "<%URL%>");

  // static definitions of URLs
  define("DELETE_REFERER",    USERNAME."/status/<%ID%>");
  define("DELETE_URL",        "https://twitter.com/i/tweet/destroy");
  define("UNRETWEET_REFERER", "");
  define("UNRETWEET_URL",     "https://twitter.com/i/tweet/unretweet");

  // static definition of success return code
  define("SUCCESS_CODE", "200");

  // static definition of command
  define("COMMAND", "curl -s -o /dev/null -w \"%{http_code}\" -X POST -A \"".USER_AGENT."\" -H \"content-type: application/x-www-form-urlencoded; charset=UTF-8\" -H \"x-requested-with: XMLHttpRequest\" -H \"cookie: guest_id=".GUEST_ID."\" -H \"cookie: _ga="._GA."\" -H \"cookie: eu_cn=1\" -H \"cookie: kdt=".KDT."\" -H \"cookie: remember_checked_on=1\" -H \"cookie: twid=\\\"u=".TWID."\\\"\" -H \"cookie: auth_token=".AUTH_TOKEN."\" -H \"cookie: _gat=1\" -H \"cookie: _twitter_sess="._TWITTER_SESS."\" -e \"https://twitter.com/".REFERER_PLACEHOLDER."\" -d \"_method=DELETE&authenticity_token=".AUTHENTICITY_TOKEN."&id=".ID_PLACEHOLDER."\" \"".URL_PLACEHOLDER."\"");

  // static definitions of special characters
  define("NF", ",");  // new field
  define("NL", "\n"); // new line
  define("SI", "\""); // string identifier

  // static definitions of CSV fields
  define("RETWEETED_STATUS_ID_FIELD", "retweeted_status_id");
  define("TWEET_ID_FIELD",            "tweet_id");

  // global variables to represent state
  $content   = null; // content of CSV file
  $cur       = null; // current character
  $entry     = null; // currently read CSV record
  $length    = null; // length of content
  $nex       = null; // next character (look-ahead)
  $position  = -1;   // current position within string
  $structure = null; // structure of CSV records

  function doDelete($id) {
    return (0 === strcasecmp(shell_exec(getDeleteCommand($id)), SUCCESS_CODE));
  }

  function doUnretweet($id) {
    return (0 === strcasecmp(shell_exec(getUnretweetCommand($id)), SUCCESS_CODE));
  }

  function getDeleteCommand($id) {
    return str_ireplace(URL_PLACEHOLDER,
                        DELETE_URL,
                        str_ireplace(ID_PLACEHOLDER,
                                     $id,
                                     str_ireplace(REFERER_PLACEHOLDER,
                                                  DELETE_REFERER,
                                                  COMMAND)));
  }

  function getUnretweetCommand($id) {
    return str_ireplace(URL_PLACEHOLDER,
                        UNRETWEET_URL,
                        str_ireplace(ID_PLACEHOLDER,
                                     $id,
                                     str_ireplace(REFERER_PLACEHOLDER,
                                                  UNRETWEET_REFERER,
                                                  COMMAND)));
  }

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
      // increase position
      $position++;

      // $position should be 0 at least now
      if (0 <= $position) {
        // set the current value correctly
        $cur = $content[$position];

        // when reading the last character, $nex must be null
        if ($length-1 > $position) {
          $nex = $content[$position+1];
        }

        // we could read a character
        $result = true;
      }
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
    $break     = false;
    $field     = "";
    $in_string = false;

    do {
      next_char();

      if (null !== $cur) {
        if ($in_string) {
          switch ($cur) {
            // a string identifier may either end the string or just be escaped by a second string identifier
            case SI: {
              switch ($nex) {
                // check if the character that follows finalizes the field or the record
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

            // a newline outside a string ends the record
            case NL: {
              // set entry field
              $entry[field_name(count($entry))] = $field;

              // start a new field
              $field = "";

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
      } else {
        // break when there is no next character
        $break = true;
      } 
    } while (!$break);

    // read fails when we end within a string
    return (!$in_string);
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

        // set length variable
        $length   = strlen($content);
        $position = -1;

        // read first record that contains field names
        if (next_record()) {
          // set $structure to read structure
          $structure = $entry;

          // iterate through all records
          while (next_record()) {
            // when "retweeted_status_id" field is not empty, it's a retweet
            if (0 < strlen($entry[RETWEETED_STATUS_ID_FIELD])) {
              // check if we're ready to work
              if ($started) {
                // undo the retweet
                if (doUnretweet($entry[RETWEETED_STATUS_ID_FIELD])) {
                  print("RETWEET: ".$entry[RETWEETED_STATUS_ID_FIELD]."\n");
                } else {
                  print(" FAILED: ".$entry[RETWEETED_STATUS_ID_FIELD]."\n");
                }
              } else {
                // check if we've reached the ID to resume
                $started = (0 === strcasecmp($start_id, $entry[RETWEETED_STATUS_ID_FIELD]));

                if ($started) {
                  print("RESUMED: ".$entry[RETWEETED_STATUS_ID_FIELD]."\n");
                } else {
                  print("IGNORED: ".$entry[RETWEETED_STATUS_ID_FIELD]."\n");
                }
              }
            } else {
              // check if we're ready to work
              if ($started) {
                // delete the tweet
                if (doDelete($entry[TWEET_ID_FIELD])) {
                  print("  TWEET: ".$entry[TWEET_ID_FIELD]."\n");
                } else {
                  print(" FAILED: ".$entry[TWEET_ID_FIELD]."\n");
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
