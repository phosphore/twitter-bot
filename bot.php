<?php

require __DIR__ . '/vendor/autoload.php';

try {
	/* change the path for your json file */
  $path="/you/need/to/change/this/path/bot.json"; 
   if (!file_exists($path)) {
    throw new Exception('Missing config file');
  } 
  
  $config = file_get_contents($path);
  $config = json_decode($config);
} catch (Exception $ex) {
  die('Error loading configuration: '.$ex);
}

try {
  $twitter = new Twitter($config->twitter->consumerKey, $config->twitter->consumerSecret, $config->twitter->accessToken, $config->twitter->accessTokenSecret);
  
  if (!$twitter->authenticate()) {
    throw new Exception('Invalid Twitter info');
  }
} catch (Exception $ex) {
  die('Invalid Twitter auth');
}
/* we read the last id from the file */
$myfile = fopen("last_id.txt", "r") or die("Unable to open file!");
$id = fgets($myfile); /* got it */
fclose($myfile);

/* Note: this implementation sucks. I tried using since_id for the greatest efficiency, but i couldn't get it working at first,
 * and i wanted something working before the weekend (iirc it always returned me tweets after and before the since_id specified).
 * Here i chose to limit the count to 20 because the bot is not yet very active.
 * Take a look here 'Working with Timelines': https://dev.twitter.com/rest/public/timelines */
 
$statuses = $twitter->request('statuses/mentions_timeline', 'GET', array('count' => 20, 'since_id' => (int)$id));

if (!($statuses[0]->id === $id)) /* No new request? */
	{ /* write the new id on file */
		file_put_contents('last_id.txt', $statuses[0]->id);
		/* run through the tweets */
		foreach ($statuses as $status) {
			if (!($status->id === $id) && ($status->id > $id)) { /* we skip that one we already replied to */
					$message = $config->formats->replies[mt_rand(0, count($config->formats->replies) - 1)]; /* we select a random message, you can play here in many ways */
					$message = "@".$status->user->screen_name." ".$message; /* we must mention the user for a reply */
					try {
						/* In case you want to reply with a picture */
						/* $idfetcher = $twitter->mediaupload('media/upload', 'POST', array('media' => $imbasedata)); 
						 * $finalresult = $twitter->request('statuses/update', 'POST', array('status' => $message, 'in_reply_to_status_id' => $status->id_str, 'media_ids' => $idfetcher->media_id)); */
						$finalresult = $twitter->request('statuses/update', 'POST', array('status' => $message, 'in_reply_to_status_id' => $status->id_str));
					} catch (TwitterException $ex) {
						  die('Twitter is not happy '.$ex);
					}
			}
		}
		
		
	} else echo "No new mentions";

