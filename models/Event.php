<?php

class Event {
  public $name;

  public $season;
  public $number;
  public $format;

  public $start;
  public $kvalue;
  public $finalized;
  public $prereg_allowed;
  public $threadurl;
  public $reporturl;
  public $metaurl;

  // Class associations
  public $series; // belongs to Series
  public $host; // has one Player - host
  public $cohost; // has one Player - cohost

  // Subevents
  public $mainrounds;
  public $mainstruct;
  public $mainid;
  public $finalrounds;
  public $finalstruct;
  public $finalid;

  public $hastrophy;
  private $new;

  function __construct($name) {
    if ($name == "") {
      $this->name = "";
      $this->mainrounds = "";
      $this->mainstruct = "";
      $this->finalrounds = "";
      $this->finalstruct = "";
      $this->host = NULL;
      $this->cohost = NULL;
      $this->threadurl = NULL;
      $this->reporturl = NULL;
      $this->metaurl = NULL;
      $this->start = NULL;
      $this->finalized = 0;
      $this->prereg_allowed = 0;
      $this->hastrophy = 0;
      $this->new = true;
      return;
    }

    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT format, host, cohost, series, season, number, start, kvalue, finalized, prereg_allowed, threadurl, metaurl, reporturl FROM events WHERE name = ?");
    if (!$stmt) {
      die($db->error);
    }
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($this->format, $this->host, $this->cohost, $this->series, $this->season, $this->number, $this->start, $this->kvalue, $this->finalized, $this->prereg_allowed, $this->threadurl, $this->metaurl, $this->reporturl);
    if ($stmt->fetch() == NULL) {
      throw new Exception('Event '. $name .' not found in DB');
    }

    $stmt->close();

    $this->name = $name;

    // Main rounds
    $this->mainid = NULL; $this->mainrounds = ""; $this->mainstruct = "";
    $stmt = $db->prepare("SELECT id, rounds, type FROM subevents
      WHERE parent = ? AND timing = 1");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($this->mainid, $this->mainrounds, $this->mainstruct);
    $stmt->fetch();
    $stmt->close();

    // Final rounds
    $this->finalid = NULL; $this->finalrounds = ""; $this->finalstruct = "";
    $stmt = $db->prepare("SELECT id, rounds, type FROM subevents
      WHERE parent = ? AND timing = 2");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($this->finalid, $this->finalrounds, $this->finalstruct);
    $stmt->fetch();
    $stmt->close();

    // Trophy count
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM trophies WHERE event = ?");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($this->hastrophy);
    $stmt->fetch();
    $stmt->close();

    $this->new = false;
  }

  function save() {
    $db = Database::getConnection();
    if ($this->new) {
      $stmt = $db->prepare("INSERT INTO events(name, start, format,
        host, cohost, kvalue, number, season, series,
        threadurl, reporturl, metaurl, finalized, prereg_allowed)
        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");
      $stmt->bind_param("sssssdddssss", $this->name, $this->start, $this->format, $this->host, $this->cohost, $this->kvalue, $this->number, $this->season, $this->series, $this->threadurl, $this->reporturl, $this->metaurl);
      $stmt->execute() or die($stmt->error);
      $stmt->close();

      $this->newSubevent($this->mainrounds, 1, $this->mainstruct);
      $this->newSubevent($this->finalrounds, 2, $this->finalstruct);

    } else {
      $stmt = $db->prepare("UPDATE events SET
        start = ?, format = ?, host = ?, cohost = ?, kvalue = ?,
        number = ?, season = ?, series = ?, threadurl = ?, reporturl = ?,
        metaurl = ?, finalized = ?, prereg_allowed = ? WHERE name = ?");
      $stmt or die($db->error);
      $stmt->bind_param("ssssdddssssdds", $this->start, $this->format, $this->host, $this->cohost, $this->kvalue, $this->number, $this->season, $this->series, $this->threadurl, $this->reporturl, $this->metaurl, $this->finalized, $this->prereg_allowed, $this->name);
      $stmt->execute() or die($stmt->error);
      $stmt->close();

      if ($this->mainid == NULL) {
        $this->newSubevent($this->mainrounds, 1, $this->mainstruct);
      } else {
        $main = new Subevent($this->mainid);
        $main->rounds = $this->mainrounds;
        $main->type = $this->mainstruct;
        $main->save();
      }

      if ($this->finalid == NULL) {
        $this->newSubevent($this->finalrounds, 2, $this->finalstruct);
      } else {
        $final = new Subevent($this->finalid);
        $final->rounds = $this->finalrounds;
        $final->type = $this->finalstruct;
        $final->save();
      }
    }
  }

  private function newSubevent($rounds, $timing, $type) {
    $db = Database::getConnection();
    $stmt = $db->prepare("INSERT INTO subevents(parent, rounds, timing, type)
      VALUES(?, ?, ?, ?)");
    $stmt->bind_param("sdds", $this->name, $rounds, $timing, $type);
    $stmt->execute();
    $stmt->close();
  }

  function getPlaceDeck($placing = "1st") {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT n.deck from entries n, events e
      WHERE e.name = n.event AND n.medal = ? AND e.name = ?");
    $stmt->bind_param("ss", $placing, $this->name);
    $stmt->execute();
    $stmt->bind_result($deckid);
    $result = $stmt->fetch();
    $stmt->close();
    if ($result == NULL) {
      $deck = NULL;
    } else {
      $deck = new Deck($deckid);
    }

    return $deck;
  }

  function getPlacePlayer($placing = "1st") {
    $playername = db_query_single("SELECT n.player from entries n, events e
      WHERE e.name = n.event AND n.medal = ? AND e.name = ?", "ss", $placing, $this->name);
    return $playername;
  }

  function getDecks() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT deck FROM entries WHERE event = ? AND deck IS NOT NULL");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($deckid);

    $deckids = array();
    while ($stmt->fetch()) {
      $deckids[] = $deckid;
    }
    $stmt->close();

    $decks = array();
    foreach($deckids as $deckid) {
      $decks[] = new Deck($deckid);
    }
    return $decks;
  }

  function getFinalists() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT medal, player, deck FROM entries
      WHERE event = ? AND medal != 'dot' ORDER BY medal, player");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($medal, $player, $deck);

    $finalists = array();
    while ($stmt->fetch()) {
      $finalists[] = array('medal' => $medal,
                           'player' => $player,
                           'deck' => $deck);
    }
    $stmt->close();

    return $finalists;
  }

  function setFinalists($win, $sec, $t4, $t8) {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE entries SET medal = 'dot' WHERE event = ?");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare("UPDATE entries SET medal = ? WHERE event = ? AND player = ?");
    $medal = "1st";
    $stmt->bind_param("sss", $medal, $this->name, $win);
    $stmt->execute();
    $medal = "2nd";
    $stmt->bind_param("sss", $medal, $this->name, $sec);
    $stmt->execute();
    $medal = "t4";
    $stmt->bind_param("sss", $medal, $this->name, $t4[0]);
    $stmt->execute();
    $stmt->bind_param("sss", $medal, $this->name, $t4[1]);
    $stmt->execute();
    $medal = "t8";
    $stmt->bind_param("sss", $medal, $this->name, $t8[0]);
    $stmt->execute();
    $stmt->bind_param("sss", $medal, $this->name, $t8[1]);
    $stmt->execute();
    $stmt->bind_param("sss", $medal, $this->name, $t8[2]);
    $stmt->execute();
    $stmt->bind_param("sss", $medal, $this->name, $t8[3]);
    $stmt->execute();
    $stmt->close();
  }

  function getTrophyImageLink() {
    return "<a href=\"deck.php?mode=view&event={$this->name}\">\n"
          ."<img style=\"border-width: 0px;\" src=\"displayTrophy.php?event={$this->name}\" />\n"
          ."</a>\n";
  }


  function isHost($name) {
    $ishost = strcasecmp($name, $this->host) == 0;
    $iscohost = strcasecmp($name, $this->cohost) == 0;
    return $ishost || $iscohost;
  }

  function isSteward($name) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT s.player FROM stewards s
      WHERE s.event = ?");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($aname);
    while ($stmt->fetch()) {
      if (strcasecmp($aname, $name) == 0) {
        $stmt->close();
        return true;
      }
    }
    $stmt->close();
    return false;
  }

  function addSteward($name) {
    $db = Database::getConnection();
    $stmt = $db->prepare("INSERT INTO stewards(event, player) VALUES(?, ?)");
    $stmt->bind_param("ss", $this->name, $name);
    $stmt->execute();
    $stmt->close();
  }

  function authCheck($playername) {
    if ($this->isHost($playername) || $this->isSteward($playername)) {
      return true;
    }
    $player = new Player($playername);
    return $player->isSuper();
  }

  function getPlayerCount() {
    $db = @Database::getConnection();
    $stmt = $db->prepare("SELECT count(*) FROM entries WHERE event = ?");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
  }

  function getPlayers() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT player FROM entries WHERE event = ? ORDER BY player");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($playername);

    $players = array();
    while ($stmt->fetch()) {
      $players[] = $playername;
    }
    $stmt->close();

    return $players;
  }

  function hasRegistrant($playername) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT count(player) FROM entries WHERE event = ? AND player = ?");
    $stmt->bind_param("ss", $this->name, $playername);
    $stmt->execute();
    $stmt->bind_result($isPlaying);
    $stmt->fetch();
    $stmt->close();
    return ($isPlaying > 0);
  }

  function getSubevents() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id FROM subevents WHERE parent = ? ORDER BY timing");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($subeventid);

    $subids = array();
    while ($stmt->fetch()) {
      $subids[] = $subeventid;
    }
    $stmt->close();

    $subs = array();
    foreach ($subids as $subid) {
      $subs[] = new Subevent($subid);
    }

    return $subs;
  }

  function getEntries() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT player FROM entries
      WHERE event = ? ORDER BY medal, player");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($player);

    $players = array();
    while ($stmt->fetch()) {
      $players[] = $player;
    }
    $stmt->close();

    $entries = array();
    foreach ($players as $player) {
      $entries[] = new Entry($this->name, $player);
    }
    return $entries;
  }

  function removeEntry($playername) {
    $db = Database::getConnection();
    $stmt = $db->prepare("DELETE FROM entries WHERE event = ? AND player = ?");
    $stmt->bind_param("ss", $this->name, $playername);
    $stmt->execute();
    $removed = $stmt->affected_rows > 0;
    $stmt->close();
    return $removed;
  }

  function addPlayer($playername) {
    $playername = trim($playername);
    if (strcmp($playername, "") == 0) {
      return false;
    }
    $entry = Entry::findByEventAndPlayer($this->name, $playername);
    $added = false;
    if (is_null($entry)) {
      $player = Player::findOrCreateByName($playername);
      $db = Database::getConnection();
      $stmt = $db->prepare("INSERT INTO entries(event, player, registered_at) VALUES(?, ?, NOW())");
      $stmt->bind_param("ss", $this->name, $player->name);
      if (!$stmt->execute()) {
        print_r($stmt->error);
        return false;
      }
      $stmt->close();
      $added = true;
    }
    return $added;
  }

  function getMatches() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT m.id FROM matches m, subevents s, events e
      WHERE m.subevent = s.id AND s.parent = e.name AND e.name = ?
      ORDER BY s.timing, m.round");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($matchid);

    $mids = array();
    while ($stmt->fetch()) {
      $mids[] = $matchid;
    }
    $stmt->close();

    $matches = array();
    foreach ($mids as $mid) {
      $matches[] = new Match($mid);
    }

    return $matches;
  }

  function getRoundMatches($roundnum) {
    $db = Database::getConnection();
    if ($roundnum > $this->mainrounds) {
      $subevnum = 2;
      $roundnum = $roundnum - $this->mainrounds;
    } else {
      $subevnum = 1;
    }
    $stmt = $db->prepare("SELECT m.id FROM matches m, subevents s, events e
        WHERE m.subevent = s.id AND s.parent = e.name AND e.name = ? AND
        s.timing = ? AND m.round = ?");
    $stmt->bind_param("sdd", $this->name, $subevnum, $roundnum);
    $stmt->execute();
    $stmt->bind_result($matchid);

    $mids = array();
    while ($stmt->fetch()) {
      $mids[] = $matchid;
    }
    $stmt->close();

    foreach ($mids as $mid) {
      $matches[] = new Match($mid);
    }

    return $matches;
  }

  function addMatch($playera, $playerb, $round, $result) {
    $id = $this->mainid;
    if ($round > $this->mainrounds) {
      $id = $this->finalid;
      $round = $round - $this->mainrounds;
    }
    $db = Database::getConnection();
    $stmt = $db->prepare("INSERT INTO matches(playera, playerb, round, subevent, result) VALUES(?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdds", $playera, $playerb, $round, $id, $result);
    $stmt->execute();
    $stmt->close();
  }


  // Assigns trophies based on the finals matches which are entered.
  function assignTropiesFromMatches() {
    $t8 = array();
    $t4 = array();
    $sec = "";
    $win = "";
    $quarter_finals = $this->finalrounds >= 3;
    if ($quarter_finals) {
      $quart_round = $this->mainrounds + $this->finalrounds - 2;
      $matches = $this->getRoundMatches($quart_round);
      foreach ($matches as $match) {
        $t8[] = $match->getLoser();
      }
    }
    $semi_finals = $this->finalrounds >= 2;
    if ($semi_finals) {
      $semi_round = $this->mainrounds + $this->finalrounds - 1;
      $matches = $this->getRoundMatches($semi_round);
      foreach ($matches as $match) {
        $t4[] = $match->getLoser();
      }
    }

    $finalmatches = $this->getRoundMatches($this->mainrounds + $this->finalrounds);
    $finalmatch = $finalmatches[0];
    $sec = $finalmatch->getLoser();
    $win = $finalmatch->getWinner();

    $this->setFinalists($win, $sec, $t4, $t8);
  }

  public static function exists($name) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    $event_exists = $stmt->num_rows > 0;
    $stmt->close();
    return $event_exists;
  }

  public static function findMostRecentByHost($host_name) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events WHERE host = ? OR cohost = ? ORDER BY start DESC LIMIT 1");
    $stmt->bind_param("ss", $host_name, $host_name);
    $stmt->execute();
    $event_name = "";
    $stmt->bind_result($event_name);
    $event_exists = $stmt->fetch();
    $stmt->close();
    if ($event_exists) {
      return new Event($event_name);
    }
    return NULL;
  }

  public function findPrev() {
    if ($this->number == 0) {
      return NULL;
    }
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events WHERE series = ? AND season = ? AND number = ? LIMIT 1");
    $num = $this->number - 1;
    $stmt->bind_param("sdd", $this->series, $this->season, $num);
    $stmt->execute();
    $stmt->bind_result($event_name);
    $exists = $stmt->fetch();
    $stmt->close();
    if ($exists) {
      return new Event($event_name);
    }
    return NULL;
  }

  public function findNext() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events WHERE series = ? AND season = ? AND number = ? LIMIT 1");
    $num = $this->number + 1;
    $stmt->bind_param("sdd", $this->series, $this->season, $num);
    $stmt->execute();
    $stmt->bind_result($event_name);
    $exists = $stmt->fetch();
    $stmt->close();
    if ($exists) {
      return new Event($event_name);
    }
    return NULL;
  }

  public function makeLink($text) {
    return "<a href=\"event.php?name=" . urlencode($this->name) . "\">{$text}</a>";
  }

  public function linkTo() {
    return $this->makeLink($this->name);
  }

  public function linkReport() {
    return "<a href=\"eventreport.php?event=" . urlencode($this->name) . "\">{$this->name}</a>";
  }

  public static function count() {
    $db = @Database::getConnection();
    $stmt = $db->prepare("SELECT count(name) FROM events");
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    $stmt->close();
    return $result;
  }

  public static function getOldest() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events ORDER BY start LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($eventname);
    $stmt->fetch();
    $stmt->close();
    return new Event($eventname);
  }

  public static function getNewest() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events ORDER BY start DESC LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($eventname);
    $stmt->fetch();
    $stmt->close();
    return new Event($eventname);
  }

  public static function getNextPreRegister($num=4) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events WHERE prereg_allowed = 1 AND start > NOW() ORDER BY start LIMIT ?");
    $stmt->bind_param("d", $num);
    $stmt->execute();
    $stmt->bind_result($nextevent);
    $event_names = array();
    while ($stmt->fetch()) {
      $event_names[] = $nextevent;
    }
    $stmt->close();
    $events = array();
    foreach ($event_names as $eventname) {
      $events[] = new Event($eventname);
    }
    return $events;
  }

  public function getSeasonPointAdjustment($player) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT adjustment, reason FROM season_points WHERE event = ? AND player = ?");
    $stmt or die($db->error);
    $stmt->bind_param("ss", $this->name, $player);
    $stmt->execute();
    $stmt->bind_result($adjustment, $reason);
    $exists = $stmt->fetch() != NULL;
    $stmt->close();
    if ($exists) {
      return array('adjustment' => $adjustment, 'reason' => $reason);
    } else {
      return NULL;
    }
  }

  public function setSeasonPointAdjustment($player, $points, $reason) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT player FROM season_points WHERE event = ? AND player = ?");
    $stmt or die($db->error);
    $stmt->bind_param("ss", $this->name, $player);
    $stmt->execute();
    $exists = $stmt->fetch() != NULL;
    $stmt->close();
    if ($exists) {
      $stmt = $db->prepare("UPDATE season_points SET reason = ?, adjustment = ? WHERE event = ? AND player = ?");
      $stmt->bind_param("sdss", $reason, $points, $this->name, $player);
    } else {
      $stmt = $db->prepare("INSERT INTO season_points(series, season, event, player, adjustment, reason) values(?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sdssds", $this->series, $this->season, $this->name, $player, $points, $reason);
    }
    $stmt->execute();
    $stmt->close();
    return true;
  }
}
