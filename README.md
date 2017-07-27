# deltwitter

This script is a one-off approach to deleting all tweets and retweets ever done on twitter by a given user account. It uses the [TwitterOAuth](https://twitteroauth.com) library to access the official twitter API.

## Preparation
1. Log in to twitter.com and visit apps.twitter.com.
2. Click on "Create New App" and enter the requested, mandatory information, agree to the Twitter Developer Agreement and click on "Create your Twitter application".
3. Visit the "Keys and Access Tokens" tab and note the "Consumer Key" and "Consumer Secret" value for the configuration of this script.
4. Scroll down to the "Your Access Token" and click on "Create my access token". Note the "Access Token" and "Access Token Secret" value for the configuration of this script.
5. Manually configure the script by editing the source, using the tokens you just created.
6. Enter the folder in which you downloaded this script with a command shell and execute ```composer require abraham/twitteroauth``` to install the TwitterOAuth library.
7. Then you will have to request your twitter archive through your account settings page. It may take a while until you receive the corresponding download link.
8. The twitter archive contains a file called "tweets.csv" that you have to use as the first parameter for the script.
9. Now you can execute the script by calling it and providing the path to the tweets.csv file along. Depending on the number of tweets and retweets you have this process may take a while.
10. The tweet id of every deleted tweet and retweet is printed to the screen.
11. Should it be necessary to abort the process and resume later, you should save the last processed tweet id.
12. You can use a tweet id as an optional second parameter so that the deletion can proceed with the next tweet or retweet in the CSV file.

## Usage
```
php ./deltwitter.php "<path to tweets.csv file>" ("<tweet id to resume>")
```

## Things to note
* This script contains a super-duper hand-written top-down parser for CSV files that uses a single look-ahead character and which does NOT care to handle any corner cases except multi-line values and escaped quotation marks. If this CSV parser fails for you, you are totally free to fix it for your special cases. You have been warned.
* This script was written with the intention to be a lowest-effort approach to automating the deletion of tweets and retweets. If it breaks anything, it is up to you to fix it. There will be absolutely no support given to anyone using this script. You have been warned.
* It seems to be impossible to delete retweets of people that have set their accounts to protected after retweeting them, unless you follow them.
* The twitter API runs into timeouts from time to time leading to an exception in the TwitterOAuth library. Just restart the script with the last twitter id. The resume feature is fast enough.

## License
This application is released under the BSD license.
See the [LICENSE](LICENSE) file for further information.

## Copyright
Copyright (c) 2016, Kenneth Newwood

All rights reserved.
