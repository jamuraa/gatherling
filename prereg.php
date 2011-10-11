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
  $event->addPlayer($player->name);
} elseif ($_GET['action'] == "unreg") {
  $event->removeEntry($player->name);
}

header("Location: player.php");

