<?php

require '../lib.php';

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

$db->autocommit(FALSE);

$result = do_query("SELECT d.id FROM decks d WHERE d.id NOT IN (SELECT DISTINCT deck FROM deckcontents) AND d.name = \"\"");

while ($deckid = $result->fetch_array()) {
  do_query("UPDATE entries SET deck = NULL WHERE deck = {$deckid[0]}");
  do_query("DELETE FROM decks WHERE id = {$deckid[0]}");
}

$db->commit();
$db->autocommit(TRUE);
