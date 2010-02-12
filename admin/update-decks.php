<?php

# Upgrades the database.  There are a couple of pretty crude checks for 
# versions 0 (no database) and 1 (no version table).  Hopefully it will
# work for you, but you can always just run the schema yourself.
#
# Use at your own risk!

require '../lib.php'; 

# Need to be logged in as admin before you can even try this. 

session_start();
$some_admin = Player::getSessionPlayer();

if (!$some_admin->isSuper()) { 
  header("Location: index.php");
  exit(0);
} 

$db = Database::getConnection(); 

function do_query($query) { 
  global $db;
  echo "Executing Query: $query <br />";
  $result = $db->query($query);
  if (!$result) { 
    echo "!!!! - Error: "; 
    echo $db->error;
    exit(0);
  } 
  return $result;
} 

$deckquery = do_query("SELECT id FROM decks WHERE deck_hash IS NULL");
while ($deckid = $deckquery->fetch_array()) { 
  $deck = new Deck($deckid[0]);
  $deck->calculateHashes();
  echo "-> Calculating deck hash for {$deck->id}... <br />";
  flush();
} 

