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
} else { 
  error_headers();
} 

