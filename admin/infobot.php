<?php

require_once('../lib.php');

if (strncmp($_SERVER['HTTP_USER_AGENT'], "infobot", 7) != 0) {
  die("You're not infobot!");
}

if ($_GET['passkey'] != $CONFIG['infobot_passkey']) {
  die("Wrong passkey");
}

# generate a user passkey for verification

$random_num = mt_rand();

$key = sha1($random_num);

$challenge = substr($key, 0, 5);

$player = Player::findByName($_GET['username']);

if (!$player) {
  echo "<UaReply>You're not registered on {$CONFIG['site_name']}!</UaReply>";
}

$player->setChallenge($challenge);

echo "<UaReply>Your verification code for {$CONFIG['site_name']} is $challenge</UaReply>";

