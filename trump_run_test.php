<?php

chdir(dirname(__FILE__));
//UNCOMMENT IN PRODUCTION
//if( php_sapi_name() !== 'cli' ){die();}
date_default_timezone_set('UTC');
ini_set('display_errors', false);

require_once('twitterexchange.php');

$last_tweet = file_get_contents('last_id.txt');

require_once('credentials.php');

$url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
$requestMethod = "GET";
$getfield = '?exclude_replies=true&include_rts=false&trim_user=true&screen_name=realDonaldTrump&since_id='.$lastTweet;

$twitter = new TwitterAPIExchange($credentials);

//Test various responses
$tweets  =  json_decode($response,true);

if(!$tweets) {
  file_put_contents('lastid.txt',$last_tweet);
  die();
}

$last_tweet = $tweets[0]['id_str'];

$tweets = array_reverse($tweets);

//Loop Through $tweets
foreach ($tweets as $t) :

  $text = $t['text'];
  $id = $t['id_str'];
  $text_exploded = explode(' ',$text);

  //CHECK IF REPLY
  if($t['in_reply_to_user_id'] || strpos($text_exploded[0], '"@') !== false) {
    continue;
  }
  //Check That it's not a RETWEET
  if($text_exploded[0] === 'RT') {
    continue;
  }

  $exclaim_count = 0 ;
  $tweet_string = "";

  foreach($text_exploded as $te):

    if(strpos($te, "!") !== false && strpos($te, 'http') === false) {
      $exclaim_count++;
      $tweet_string .= str_replace('!', '?', $te).' ';
    } else {
      $tweet_string .= $te.' ';
    }

  endforeach;

  if($exclaim_count < 1) {
    continue;
  }
  $post_fields = array(
    'status' => str_replace("&amp;","&",$tweet_string);
  );
  $postedTweet = $twitterNew->buildOauth('https://api.twitter.com/1.1/statuses/update.json', 'POST')->setPostfields($postfields)->performRequest();
  if($postedTweet = 'errored in some way') {
    file_put_contents('lastid.txt',$id);
    //MAIL EMAIL TO ME
    die();
  }



endforeach;



 ?>
