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

function redirect_deck_update($latest_id = 0) {
  $url = explode('?', $_SERVER['REQUEST_URI']);
  $url = $url[0] . "?deckupdate=" . $latest_id;
  echo "<a href=\"{$url}\">Continue</a>";
  echo "<script type=\"text/javascript\"> window.location = \"http://{$_SERVER['SERVER_NAME']}$url\"; </script>";
  exit(0);
}

if (isset($_GET['deckupdate'])) {
  $deckquery = do_query("SELECT id FROM decks WHERE id > " . $_GET['deckupdate']);
  $timestart = time();
  while ($deckid = $deckquery->fetch_array()) {
    flush();
    $deck = new Deck($deckid[0]);
    $deck->save();
    flush();
    if ((time() - $timestart) > 5) {
      echo "-> Updating decks, ID: {$deck->id}... <br />";
      redirect_deck_update($deck->id);
    }
  }
  echo "Done with deck updates...<br />";
  exit(0);
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

if (!isset($_GET['version'])) {
  $result = do_query("SELECT version FROM db_version LIMIT 1");
  $obj = $result->fetch_object();
  $version = $obj->version;
} else {
  $version = $_GET['version'];
}

$db->autocommit(FALSE);

if ($version < 2) {
  echo "Updating to version 2... <br />";
  # Version 2 Changes:
  #  - Add 'mtgo_confirmed', 'mtgo_challenge' field to players, and initialize them
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
  echo ".. DB now at version 2! <br />";
}

if ($version < 3) {
  echo "Updating to version 3... <br />";
  # Version 3 Changes:
  #  - Add "series_stewards" table with playername, series name.
  #  - Add "day" and "time" to "series" table to track when they start (eastern times)
  do_query("CREATE TABLE series_stewards (player varchar(40), series varchar(40), FOREIGN KEY (player) REFERENCES players(name), FOREIGN KEY (series) REFERENCES series(name))");
  do_query("ALTER TABLE series ADD COLUMN (day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), normalstart TIME)");
  do_query("UPDATE db_version SET version = 3");
  $db->commit();
  echo "... DB now at version 3! <br />";
}

if ($version < 4) {
  echo "Updating to version 4... <br />";
  # Version 4 changes:
  #  - Add "series_seasons" table for tracking seasons in a series, and "standard" rewards for each season.
  #  - Add "player_points" table for tracking "extra" player points.
  do_query("CREATE TABLE series_seasons (series varchar(40), season integer, first_pts integer, second_pts integer, semi_pts integer, quarter_pts integer, participation_pts integer, rounds_pts integer, decklist_pts integer, win_pts integer, loss_pts integer, bye_pts integer, FOREIGN KEY (series) REFERENCES series(name), PRIMARY KEY(series, season))");
  do_query("CREATE TABLE season_points (series varchar(40), season integer, event varchar(40), player varchar(40), adjustment integer, reason varchar(140), FOREIGN KEY (series) REFERENCES series(name), FOREIGN KEY (event) REFERENCES event(name), FOREIGN KEY (player) REFERENCES player(name))");

  do_query("UPDATE db_version SET version = 4");
  $db->commit();
  echo "... DB now at version 4! <br />";
}

if ($version < 5) {
  echo "Updating to version 5... <br />";

  # Version 5 changes:
  #  - Add "must_decklist" column for series_seasons.
  #  - Add "cutoff_ord" column for series_seasons.
  do_query("ALTER TABLE series_seasons ADD COLUMN must_decklist integer");
  do_query("ALTER TABLE series_seasons ADD COLUMN cutoff_ord integer");

  do_query("UPDATE db_version SET version = 5");
  $db->commit();
  echo "... DB now at version 5! <br />";
}

if ($version < 6) {
  echo "Updting to version 6... <br />";

  # Version 6 changes:
  #  - Add "format" column for series_seasons.
  #  - Add "master_link" column for series_seasons.
  do_query("ALTER TABLE series_seasons ADD COLUMN format varchar(40)");
  do_query("ALTER TABLE series_seasons ADD COLUMN master_link varchar(140)");
  do_query("UPDATE db_version SET version = 6");
  $db->commit();
  echo "... DB now at version 6! <br />";
}

if ($version < 7) {
  echo "Updating to version 7... <br />";

  do_query("UPDATE decks SET archetype = 'Unclassified' WHERE archetype = 'Rogue'");
  do_query("UPDATE archetypes SET name = 'Unclassified' WHERE name = 'Rogue'");
  do_query("ALTER TABLE events MODIFY COLUMN name VARCHAR(80)");
  do_query("UPDATE db_version SET version = 7");
  $db->commit();
  echo "... DB now at version 7! <br />";
}

if ($version < 8) {
  echo "Updating to version 8 (alter tables that reference event to have longer name too, make trophy image column larger).... <br />";

  do_query("ALTER TABLE entries MODIFY COLUMN event VARCHAR(80)");
  do_query("ALTER TABLE trophies MODIFY COLUMN event VARCHAR(80)");
  do_query("ALTER TABLE trophies MODIFY COLUMN image MEDIUMBLOB");
  do_query("ALTER TABLE subevents MODIFY COLUMN parent VARCHAR(80)");
  do_query("ALTER TABLE stewards MODIFY COLUMN event VARCHAR(80)");
  do_query("UPDATE db_version SET version = 8");
  $db->commit();
  echo "... DB now at version 8! <br />";
}

if ($version < 9) {
  echo "Updating to version 9 (add deck contents cache column for searching, series logo column larger).... <br />";
  do_query("ALTER TABLE decks ADD COLUMN (deck_contents_cache text)");
  do_query("ALTER TABLE series MODIFY COLUMN logo MEDIUMBLOB");
  do_query("UPDATE db_version SET version = 9");
  $db->commit();
  echo "... DB now at version 9! <br />";
  redirect_deck_update();
}

if ($version < 10) {
  echo "Updating to version 10 (add database stuff for pre-registration)... <br />";
  do_query("ALTER TABLE events ADD COLUMN (prereg_allowed INTEGER DEFAULT 0)");
  do_query("ALTER TABLE series ADD COLUMN (prereg_default INTEGER DEFAULT 0)");
  do_query("ALTER TABLE entries ADD COLUMN (registered_at DATETIME)");
  do_query("UPDATE db_version SET version = 10");
  $db->commit();
  echo "... DB now at version 10! <br />";
}

if ($version < 11) {
  // Match Pairing Updates
  // Reconstructed from schemas.
  echo "Updating to version 11 (add pairing system stuff, cards)... <br />";
  do_query("ALTER TABLE cards ADD COLUMN (isp TINYINT(1) DEFAULT '0')");
  do_query("ALTER TABLE cards ADD COLUMN (rarity VARCHAR(40) DEFAULT NULL)");

  do_query("ALTER TABLE events ADD COLUMN (active TINYINT(1) DEFAULT '0')");
  do_query("ALTER TABLE events ADD COLUMN (current_round TINYINT(3) NOT NULL)");
  do_query("ALTER TABLE events ADD COLUMN (player_reportable TINYINT(1) NOT NULL DEFAULT '0')");

  do_query("ALTER TABLE matches ADD COLUMN (playera_wins INT(11) NOT NULL DEFAULT '0')");
  do_query("ALTER TABLE matches ADD COLUMN (playera_losses INT(11) NOT NULL DEFAULT '0')");
  do_query("ALTER TABLE matches ADD COLUMN (playera_draws INT(11) NOT NULL DEFAULT '0')");
  do_query("ALTER TABLE matches ADD COLUMN (playerb_wins INT(11) NOT NULL DEFAULT '0')");
  do_query("ALTER TABLE matches ADD COLUMN (playerb_losses INT(11) NOT NULL DEFAULT '0')");
  do_query("ALTER TABLE matches ADD COLUMN (playerb_draws INT(11) NOT NULL DEFAULT '0')");
  do_query("ALTER TABLE matches ADD COLUMN (verification varchar(40) NOT NULL DEFAULT 'unverified')");

  do_query(<<<EOS
CREATE TABLE IF NOT EXISTS `standings` (
  `player` varchar(40) DEFAULT NULL,
  `event` varchar(40) DEFAULT NULL,
  `active` tinyint(3) DEFAULT '0',
  `matches_played` tinyint(3) DEFAULT '0',
  `games_won` tinyint(3) DEFAULT '0',
  `games_played` tinyint(3) DEFAULT '0',
  `byes` tinyint(3) DEFAULT '0',
  `OP_Match` decimal(3,3) DEFAULT '0.000',
  `PL_Game` decimal(3,3) DEFAULT '0.000',
  `OP_Game` decimal(3,3) DEFAULT '0.000',
  `score` tinyint(3) DEFAULT '0',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seed` tinyint(3) NOT NULL,
  `matched` tinyint(1) NOT NULL,
  `matches_won` tinyint(3) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `player` (`player`),
  KEY `event` (`event`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=266 ;
EOS;
  
  do_query("UPDATE db_version SET version = 11");
  $db->commit();
  echo "... DB now at version 11! <br />";
}

$db->autocommit(TRUE);
