<?php

require_once('../lib.php');

if (strncmp($_SERVER['HTTP_USER_AGENT'], "infobot", 7) != 0) {
  die("You're not infobot!");
} 

if (md5($_GET['passkey']) != "7fce3792472af52ad1489e786c382b19") {
  die("Wrong passkey");
}

# generate a user passkey for verification 

$random_num = mt_rand(); 

$key = sha1($random_num);

$challenge = substr($key, 0, 5);

$player = Player::findByName($_GET['username']);

if (!$player) { 
  echo "<UaReply>You're not registered on $SiteName!</UaReply>";
} 

$player->setChallenge($challenge); 

echo "<UaReply>Your verification code for $SiteName is $challenge</UaReply>"; 

