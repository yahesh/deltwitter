# deltwitter

This script is a one-off approach to deleting all tweets and retweets ever done on twitter by a given user account. It uses the [TwitterOAuth](https://twitteroauth.com) library to access the official Twitter API.

## Preparation
1. Log in to [twitter.com](https://twitter.com/) and visit [developer.twitter.com/en/apps](https://developer.twitter.com/en/apps).
2. Click on `Create New App` and enter the requested, mandatory information, agree to the Twitter Developer Agreement and click on `Create your Twitter application`.
3. Visit the `Keys and tokens` tab and note the `API key` and `API secret key` values in the `Consumer API keys` section for the configuration of this script.
4. Scroll down to the `Access token & access token secret` section and click on `Create`. Note the `Access token` and `Access token secret` values for the configuration of this script.
5. Enter the folder in which you downloaded this script with a command shell and execute ```composer require abraham/twitteroauth``` to install the TwitterOAuth library.
6. Copy `config.php.default` to `config.php` and configure the script.
7. Then you will have to request your Twitter archive through your account settings page. It may take a while until you receive the corresponding download link.
8. The Twitter archive contains a file called `data/tweet.js` that you have to use as the first parameter for the script.
9. Now you can execute the script by calling it and providing the path to the tweet.js file along. Depending on the number of tweets and retweets you have this process may take a while.
10. The tweet id of every deleted tweet and retweet is printed to the screen.
11. Should it be necessary to abort the process and resume later, you should save the last processed tweet id.
12. You can use a tweet id as an optional second parameter so that the deletion can proceed with the next tweet or retweet in the JS file.

## Usage
```
./deltwitter.php "<path to tweet.js file>" ("<tweet id to resume>")
```

If you want to know which IDs are contained in the file and which creation date they have then provide a non-numeric tweet ID as a second parameter.

## Things to note
* This script was written with the intention to be a lowest-effort approach to automating the deletion of tweets and retweets. If it breaks anything, it is up to you to fix it. There will be absolutely no support given to anyone using this script. You have been warned.
* It seems to be impossible to delete retweets of people that have set their accounts to protected after retweeting them, unless you follow them.
* The Twitter API runs into timeouts from time to time leading to an exception in the TwitterOAuth library. Just restart the script with the last tweet id. The resume feature is fast enough.

## License
This application is released under the MIT license.
See the [LICENSE](LICENSE) file for further information.

## Copyright
Copyright (c) 2016-2020, Yahe

All rights reserved.
