<?php session_start();
require_once 'lib.php';

$player = Player::getSessionPlayer();

if (!isset($_GET['event']) || !isset($_GET['action'])) {
  header("Location: player.php");
}

$event = new Event($_GET['event']);

if ($event->prereg_allowed != 1) {
  header("Location: player.php");
}

if ($_GET['action'] == "reg") {
  // part of the reg-decklist feature, the the header call to deck.php is the switch that turns it on. Not sure if the call is
  // correct exactly. It works for the super but not non-supers
  $event->addPlayer($player->name);
  header ("Location: deck.php?player={$player->name}&event={$event->name}&mode=register");
} elseif ($_GET['action'] == "unreg") {
  $event->removeEntry($player->name);
  header("Location: player.php");
}

