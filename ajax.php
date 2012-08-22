<?php session_start();
require_once 'lib.php';

if (isset($_GET['deck'])) {
  $deckid = $_GET['deck'];
  $deck = new Deck($deckid);
  $result = array();
  $result["id"] = $deckid;
  if ($deck->id != 0) {
    $result["found"] = 1;
    $result["name"] = $deck->name;
    $result["archetype"] = $deck->archetype;
    $result["maindeck"] = $deck->maindeck_cards;
    $result["sideboard"] = $deck->sideboard_cards;
  } else {
    $result["found"] = 0;
  }
  json_headers();
  echo json_encode($result);
} elseif (isset($_GET['addplayer']) && isset($_GET['event'])) {
  $event = new Event($_GET['event']);
  if ($event->authCheck($_SESSION['username'])) {
    $result = array();
    $new = $_GET['addplayer'];
    if ($event->addPlayer($new)) {
      $player = new Player($new);
      $result["success"] = true;
      $result["player"] = $player->name;
      $result["verified"] = $player->verified;
    } else {
      $result["success"] = false;
    }
    json_headers();
    echo json_encode($result);
  }
} elseif (isset($_GET['delplayer']) && isset($_GET['event'])) {
  $event = new Event($_GET['event']);
  if ($event->authCheck($_SESSION['username'])) {
    $old = $_GET['delplayer'];
    $result = array();
    $result['success'] = $event->removeEntry($old);
    $result['player'] = $old;
    json_headers();
    echo json_encode($result);
  }
} elseif (isset($_GET['dropplayer']) && isset($_GET['event'])) {
  $event = new Event($_GET['event']);
  if ($event->authCheck($_SESSION['username'])) {
    $result = array();
    $playername = $_GET['dropplayer'];
    Standings::dropPlayer($event->name, $playername);
    $result['success'] = true;
    $result['player'] = $playername;
    $result['eventname'] = $event->name;
    json_headers();
    echo json_encode($result);
  }
} else {
  error_headers();
}

