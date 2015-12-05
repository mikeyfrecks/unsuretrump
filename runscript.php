<?php
/*

NOTE: I'm horrible at .php. 
Please don't judge me you cool guys. 



*/

//This is so errors won't kill the script
ini_set('display_errors', false);

//I'm using this library to talk to the Twitter API  http://github.com/j7mbo/twitter-api-php
require_once('twitterexchange.php');

/*This was some thing I was planning on using to detect where the request was coming from. Didn't end up using it. */
$client = 'webserver';
if(empty($_SERVER['REMOTE_ADDR']) == false) {
	//echo 'webserver';
	//die();
	$client = "webserver";
} else {
	$client = 'cli';
}
//This is the real connecting IP variable. 
$connectingip = (string)$_SERVER['REMOTE_ADDR'];

//Servers are stupid. I just get a .json file. 
$oldIDs = file_get_contents('ids.json');

$idArray = json_decode($oldIDs,true);
//var_dump($idArray);

/* This was a whole thing I was planning that I didn't end up using. It's still in there though...*/
$oldArray = $idArray['ids'];
$newIDs = array();

/* This is the real last tweet thing I'm using. */
$lastTweet = file_get_contents('lastid.txt');
//var_dump($lastTweet);

/** Set access tokens here - see: https://dev.twitter.com/apps/ **/
$settings = array(
    'oauth_access_token' => "YOUR TOKEN",
    'oauth_access_token_secret' => "YOUR SECRET",
    'consumer_key' => "USER KEY",
    'consumer_secret' => "USER SECRET"
);

// Now we're gonna get Trump's tweets.
$url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
$requestMethod = "GET";
/*
I'm getting the 50 most recent tweets since the last time the script ran. This would be so much harder if Twitter didn't have the since_id thing.
*/
$getfield = '?count=50&screen_name=realDonaldTrump&since_id='.$lastTweet;

$twitter = new TwitterAPIExchange($settings);
$response = $twitter->setGetfield($getfield)
                             ->buildOauth($url, $requestMethod)
                             ->performRequest();


$trumpTweets =  json_decode($response,true);
//This is the most recent trump tweet. 
$most_recent = $trumpTweets[0]['id_str'];
//If there's no tweets, most recent tweet is last tweet.
if(!($most_recent)) {
	$most_recent = $lastTweet;
}
//Reverse the tweets so we'll cycle older to newest.
$trumpTweets = array_reverse($trumpTweets);
//var_dump($trumpTweets);
$usuableTweets = 0;
foreach($trumpTweets as $tt) {

//Check to make sure it's not a reply or fav
$tweet = $tt['text'];
$tweet_id = $tt['id_str'];
//var_dump($tweet_id);
$tweet_exploded = explode(" ", $tweet);

if($tt['in_reply_to_user_id'] == NULL && strpos($tweet_exploded[0], '"@') === false && $tweet_exploded[0] !== 'RT' && !(in_array($tweet_id,$oldArray))) {
//THIS ONE IS REAL
//echo 'afasf';
$exclaimCount = 0;
$exclaimLink = 0;
$toTweetString = '';
	//LOOP THROUGH WORDS
	foreach($tweet_exploded as $te) {
		if(strpos($te,'!') !== false && strpos($te, 'http') == false) {
			$toTweetString .= str_replace("!","?",$te).' ';
			$exclaimCount++;
		} else {
			$toTweetString .= $te.' ';
		}
	}
	if($exclaimCount > 0) {
		$ripstring = str_replace("&amp;","&",$toTweetString);
		$ripstring = substr($ripstring,0,140);
		
		//$ripstring = rawurlencode($ripstring);
		//if($client == 'cli') {
			$twitterNew = new TwitterAPIExchange($settings);
			$postfields = array(
				'status' => $ripstring
			);
			if (strpos($connectingip,'the.only.allowed.ip') !== false) {
				$postedTweet = $twitterNew->buildOauth('https://api.twitter.com/1.1/statuses/update.json', 'POST')->setPostfields($postfields)->performRequest();
			} else {
				
			}
			
			file_put_contents('tweetattempts.html',date('m-d-Y h:iA').'-'.$usuableTweets.'<br/>'.$postedTweet.'<br/>'.$connectingip.'<br/><br/>',FILE_APPEND | LOCK_EX);
			array_unshift($oldArray,$tweet_id);
			array_push($newIDs,$tweet_id);
			
		//}
		echo $toTweetString.'<br/><br/>';
		$usuableTweets++;
		
		
	}

} else {


}

}
//MAKE THE NEW FILE

$info = 'Last Update: '.date('m-d-Y h:iA').'<br/> From: '.$client.'<br/> Number of Tweets: '.$usuableTweets.'<br/> IP: '.$_SERVER['REMOTE_ADDR'].'<br/>Since: '.$lastTweet.'<br/>Most Recent: '.$most_recent;
file_put_contents('lastUpdate.html',$info);
file_put_contents('iplogs.html', $_SERVER['REMOTE_ADDR'].'<br/>', FILE_APPEND | LOCK_EX);


//echo $twitterNew->buildOauth('https://api.twitter.com/1.1/statuses/update.json', 'POST')->setPostfields($postfields)->performRequest();
$newJson = array (
	'last_id' => $most_recent,
	'ids' => $oldArray
);
file_put_contents('lastid.txt',$lastTweet);
$newJson = json_encode($newJson);
$newJson = (string)$newJson;
file_put_contents('ids.json',$newJson);
  
echo 'Usuable Tweets: '.$usuableTweets;
?>