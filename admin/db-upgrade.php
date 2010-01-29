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

# Check for version 0.  (no players table) 

if (!$db->query("SELECT name FROM players LIMIT 1")) { 
  # Version 0.  Enter the whole schema.
  echo "DETECTED NO DATABASE.  Currently can't handle null database. Exiting. <br />";
  exit(0);
} else if (!$db->query("SELECT version FROM db_version LIMIT 1")) {
  # Version 1.  Add our version table.
  echo "Detected VERSION 1 DATABASE. Marking as such.. <br />";
  $db->query("CREATE TABLE db_version (version integer);");
  $db->query("INSERT INTO db_version(version) values(1)");
  echo ".. DB now at version 1!<br />"; 
} 

$result = do_query("SELECT version FROM db_version LIMIT 1");
$obj = $result->fetch_object();
$version = $obj->version;

if ($version < 2) { 
  echo "Updating to version 2... <br />";
  # Version 2 Changes: 
  #  - Add 'mtgo_confirmed', 'mtgo_challenge' field to players, and initialize them
  $db->autocommit(FALSE);
  do_query("ALTER TABLE players ADD COLUMN (mtgo_confirmed tinyint(1), mtgo_challenge varchar(5))"); 
  do_query("UPDATE players SET mtgo_confirmed = 0");
  do_query("UPDATE players SET mtgo_challenge = NULL");
  #  - Add 'deck_hash', 'sideboard_hash' and 'whole_hash' to decks, and initialize them
  do_query("ALTER TABLE decks ADD COLUMN (deck_hash varchar(40), sideboard_hash varchar(40), whole_hash varchar(40))");
  $deckquery = do_query("SELECT id FROM decks");
  while ($deckid = $deckquery->fetch_array()) { 
    $deck = new Deck($deckid[0]);
    $deck->calculateHashes();
    echo "-> Calculating deck hash for {$deck->id}... <br />";
    flush();
  } 

  #  - Add 'notes' to entries and copy the current notes in the decks 
  do_query("ALTER TABLE entries ADD COLUMN (notes text)"); 
  do_query("UPDATE entries e, decks d SET e.notes = d.notes WHERE e.deck = d.id");

  #  - and of course, set the version number to 2. 
  do_query("UPDATE db_version SET version = 2");
  $db->commit();
  $db->autocommit(TRUE);
  echo ".. DB now at version 2! <br />";
}

