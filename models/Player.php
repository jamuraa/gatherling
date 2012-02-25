<?php

class Player {
  public $name;
  public $password;
  public $host;
  public $super;


  static function isLoggedIn() {
    return isset($_SESSION['username']);
  }

  static function loginName() {
    return $_SESSION['username'];
  }

  static function getSessionPlayer() {
    if (!isset($_SESSION['username'])) {
      return NULL;
    }

    return new Player($_SESSION['username']);
  }

  static function checkPassword($username, $password) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT password FROM players WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($srvpass);
    if (!$stmt->fetch()) {
      return false;
    }
    $stmt->close();

    $hashpwd = hash('sha256', $password);

    return strcmp($srvpass, $hashpwd) == 0;
  }

  static function findByName($playername) {
    $database = Database::getConnection();
    $stmt = $database->prepare("SELECT name FROM players WHERE name = ?");
    $stmt->bind_param("s", $playername);
    $stmt->execute();
    $stmt->bind_result($resname);
    $good = false;
    if ($stmt->fetch()) {
      $good = true;
    }
    $stmt->close();

    if ($good) {
      return new Player($playername);
    } else {
      return NULL;
    }
  }

  static function createByName($playername) {
    $db = Database::getConnection();
    $stmt = $db->prepare("INSERT INTO players(name) VALUES(?)");
    $stmt->bind_param("s", $playername);
    $stmt->execute();
    $stmt->close();
    return Player::findByName($playername);
  }

  static function findOrCreateByName($playername) {
    $found = Player::findByName($playername);
    if (is_null($found)) {
      return Player::createByName($playername);
    }
   return $found;
  }

  function __construct($name) {
    $database = Database::getConnection();
    $stmt = $database->prepare("SELECT name, password, host, super, mtgo_confirmed FROM players WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($this->name, $this->password, $this->host, $this->super, $this->verified);
    if ($stmt->fetch() == NULL) {
      throw new Exception('Player '. $name .' is not found.');
    }
    $stmt->close();
  }

  function save() {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE players SET password = ?, host = ?, super = ? WHERE name = ?");
    $stmt->bind_param("sdds", $this->password, $this->host, $this->super, $this->name);
    $stmt->execute();
    $stmt->close();
  }

  function isHost() {
    return ($this->super == 1) || (count($this->stewardsSeries()) > 0) || ($this->getHostedEventsCount() > 0);
  }

  function isSteward() {
    return ($this->super == 1) || (count($this->stewardsSeries()) > 0);
  }

  function getHostedEvents() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM events WHERE host = ? OR cohost = ?");
    $stmt->bind_param("ss", $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($evname);

    $evnames = array();
    while ($stmt->fetch()) {
      $evnames[] = $evname;
    }
    $stmt->close();

    $evs = array();
    foreach ($evnames as $evname) {
      $evs[] = new Event($evname);
    }
    return $evs;
  }

  function getHostedEventsCount() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT count(name) FROM events WHERE host = ? OR cohost = ?");
    $stmt->bind_param("ss", $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($evcount);

    $stmt->fetch();
    $stmt->close();

    return $evcount;
  }

  function isSuper() {
    return ($this->super == 1);
  }

  function getLastEventPlayed() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.name FROM entries n, events e
      WHERE n.player = ? AND e.name = n.event ORDER BY UNIX_TIMESTAMP(e.start) DESC");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $lastevname = NULL;
    $stmt->bind_result($lastevname);
    $stmt->fetch();
    $stmt->close();

    if ($lastevname != NULL) {
      return new Event($lastevname);
    } else {
      return NULL;
    }
  }

  function getMatchesEvent($eventname) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT m.id
      FROM matches m, subevents s
      WHERE m.subevent = s.id AND s.parent = ?
      AND (m.playera = ? OR m.playerb = ?)
      ORDER BY s.timing, m.round");
    $stmt->bind_param("sss", $eventname, $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($matchid);

    $matchids = array();
    while ($stmt->fetch()) {
      $matchids[] = $matchid;
    }
    $stmt->close();

    $matches = array();
    foreach ($matchids as $matchid) {
      $matches[] = new Match($matchid);
    }

    return $matches;
  }

  function getDeckEvent($eventname) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT n.deck
      FROM entries n
      WHERE n.event = ? AND n.player = ?");
    $stmt->bind_param("ss", $eventname, $this->name);
    $stmt->execute();
    $stmt->bind_result($deckid);
    $stmt->fetch();

    $stmt->close();
    if ($deckid == NULL) {
      return NULL;
    }
    return new Deck($deckid);
  }

  function getRecordEvent($eventname) {
    $entry = new Entry($eventname, $this->name);
    return $entry->recordString();
  }

  function getAllDecks() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT n.deck
      FROM entries n, events e
      WHERE n.player = ? AND n.deck IS NOT NULL AND n.event = e.name
      ORDER BY e.start DESC");
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

  function getRecentDecks($number = 5) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT n.deck FROM entries n, events e
      WHERE n.player = ? AND n.event = e.name AND n.deck IS NOT NULL
      ORDER BY e.start DESC LIMIT $number");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($deckid);

    $deckids = array();
    while ($stmt->fetch()) {
      $deckids[] = $deckid;
    }
    $stmt->close();

    $decks = array();
    foreach ($deckids as $deckid) {
      $decks[] = new Deck($deckid);
    }
    return $decks;
  }

  function getRecentMatches($number = 6) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT m.id
      FROM matches m, events e, subevents s
      WHERE (m.playera = ? OR m.playerb = ?) AND m.subevent = s.id
       AND s.parent = e.name
      ORDER BY e.start DESC, s.timing DESC, m.round DESC LIMIT $number");
    $stmt->bind_param("ss", $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($matchid);

    $matchids = array();
    while ($stmt->fetch()) {
      $matchids[] = $matchid;
    }
    $stmt->close();

    $matches = array();
    foreach ($matchids as $matchid) {
      $matches[] = new Match($matchid);
    }

    return $matches;
  }

  function getAllMatches() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT m.id
      FROM matches m, events e, subevents s
      WHERE (m.playera = ? OR m.playerb = ?) AND m.subevent = s.id
       AND s.parent = e.name
      ORDER BY e.start ASC, s.timing ASC, m.round ASC");
    $stmt->bind_param("ss", $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($matchid);

    $matchids = array();
    while ($stmt->fetch()) {
      $matchids[] = $matchid;
    }
    $stmt->close();

    $matches = array();
    foreach ($matchids as $matchid) {
      $matches[] = new Match($matchid);
    }

    return $matches;
  }

  function getFilteredMatches($format = "%", $series = "%", $season = "%", $opponent = "%") {
    $matches = $this->getAllMatches();
    if ($format != "%") {
      $filteredMatches = array();
      foreach ($matches as $match) {
        if (strcasecmp($match->format, $format) == 0) {
          $filteredMatches[] = $match;
        }
      }
      $matches = $filteredMatches;
    }

    if ($series != "%") {
      $filteredMatches = array();
      foreach ($matches as $match) {
        if (strcasecmp($match->series, $series) == 0) {
          $filteredMatches[] = $match;
        }
      }
      $matches = $filteredMatches;
    }

    if ($season != "%") {
      $filteredMatches = array();
      foreach ($matches as $match) {
        if (strcasecmp($match->season, $season) == 0) {
          $filteredMatches[] = $match;
        }
      }
      $matches = $filteredMatches;
    }

    if ($opponent != "%") {
      $filteredMatches = array();
      foreach ($matches as $match) {
        if (strcasecmp($match->otherPlayer($this->name), $opponent) == 0) {
          $filteredMatches[] = $match;
        }
      }
      $matches = $filteredMatches;
    }

    return $matches;
  }

  function getMatchesByDeckName($deckname) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT m.id FROM matches m, entries n, decks d, events e, subevents s
      WHERE d.name = ? AND n.player = ? AND n.deck = d.id
       AND n.event = e.name AND s.parent = e.name AND m.subevent = s.id
       AND (m.playera = ? OR m.playerb = ?)");
    $stmt->bind_param("ssss", $deckname, $this->name, $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($mid);

    $mids = array();
    while ($stmt->fetch()) {
      $mids[] = $mid;
    }
    $stmt->close();

    $matches = array();
    foreach ($mids as $mid) {
      $matches[] = new Match($mid);
    }
    return $matches;
  }

  function getOpponents() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT q.p as opp, COUNT(q.p) AS cnt
      FROM (SELECT playera AS p FROM matches WHERE playerb = ?
            UNION ALL
            SELECT playerb AS p FROM matches WHERE playera = ?) AS q
      GROUP BY opp ORDER BY cnt DESC");
    $stmt->bind_param("ss", $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($opp, $cnt);

    $opponents = array();

    while ($stmt->fetch()) {
      $opponents[] = array('opp' => $opp, 'cnt' => $cnt);
    }

    return $opponents;
  }

  function getNoDeckEntries() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT event FROM entries n, events e
      WHERE n.player = ? AND n.deck IS NULL AND n.event = e.name
      ORDER BY e.start DESC");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($eventname);

    $eventnames = array();
    while ($stmt->fetch()) {
      $eventnames[] = $eventname;
    }
    $stmt->close();

    $entries = array();
    foreach ($eventnames as $eventname) {
      $entries[] = new Entry($eventname, $this->name);
    }

    return $entries;
  }

  function getUnenteredCount() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT count(event) FROM entries n, events e
      WHERE n.player = ? AND n.deck IS NULL AND n.event = e.name
      AND n.ignored = false");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($noentrycount);
    $stmt->fetch();
    $stmt->close();

    return $noentrycount;
  }

  function getRating($format = "Composite", $date = "3000-01-01 00:00:00") {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT rating FROM ratings WHERE player = ?
      AND updated < ? AND format = ?
      ORDER BY updated DESC LIMIT 1");
    $stmt->bind_param("sss", $this->name, $date, $format);
    $stmt->execute();
    $stmt->bind_result($rating);

    if ($stmt->fetch() == NULL) {
      $rating = 1600;
    }

    $stmt->close();
    return $rating;
  }

  function getRatingRecord($format = "Composite", $date = "3000-01-01 00:00:00") {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT wins, losses FROM ratings
      WHERE player = ? and updated < ? AND format = ?
      ORDER BY updated DESC LIMIT 1");
    $stmt->bind_param("sss", $this->name, $date, $format);
    $stmt->execute();
    $wins = 0;
    $losses = 0;
    $stmt->bind_result($wins, $losses);
    $stmt->fetch();
    $stmt->close();

    return $wins . "-" . $losses;
  }

  function getMaxRating($format = "Composite") {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT MAX(rating) AS max
      FROM ratings r
      WHERE r.player = ? and r.format = '$format'
      AND r.wins + r.losses >= 20");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $max = NULL;
    $stmt->bind_result($max);
    $stmt->fetch();
    $stmt->close();
    return $max;
  }

  function getMinRating($format = "Composite") {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT MIN(rating) AS min
      FROM ratings r
      WHERE r.player = ? and r.format = '$format'
      AND r.wins + r.losses >= 20");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $min = NULL;
    $stmt->bind_result($min);
    $stmt->fetch();
    $stmt->close();
    return $min;
  }


  function getRecord() {
    $matches = $this->getAllMatches();

    $wins = 0;
    $losses = 0;
    $draws = 0;

    foreach ($matches as $match) {
      if ($match->playerWon($this->name)) {
        $wins = $wins + 1;
      } elseif ($match->playerLost($this->name)) {
        $losses = $losses + 1;
      } else {
        $draws = 1;
      }
    }

    if ($draws == 0) {
      return $wins . "-" . $losses;
    } else {
      return $wins . "-" . $losses . "-" . $draws;
    }
  }

  function getRecordVs($opponent) {
    $matches = $this->getAllMatches();

    $wins = 0;
    $losses = 0;
    $draws = 0;

    foreach ($matches as $match) {
      $otherplayer = $match->otherPlayer($this->name);
      if (strcasecmp($otherplayer, $opponent) == 0) {
        if ($match->playerWon($this->name)) {
          $wins = $wins + 1;
        } elseif ($match->playerLost($this->name)) {
          $losses = $losses + 1;
        } else {
          $draws = $draws + 1;
        }
      }
    }

    if ($draws == 0) {
      return $wins . "-" . $losses;
    } else {
      return $wins . "-" . $losses . "-" . $draws;
    }
  }

  function getStreak($type = "W") {
    $matches = $this->getAllMatches();

    $arr = array();
    foreach ($matches as $match) {
      $thisres = 'D';
      if ($match->playerWon($this->name)) {
        $thisres = 'W';
      } elseif ($match->playerLost($this->name)) {
        $thisres = 'L';
      }

      $arr[] = $thisres;
    }

    $max = 0;
    $streak = 0;
    for ($ndx = 0; $ndx < sizeof($arr); $ndx++) {
      if ($arr[$ndx] == $type) {$streak++;}
      else {$streak = 0;}
      if ($streak > $max) {$max = $streak;}
    }
    return $max;
  }

  function getRival() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT q.p AS opp, count(q.p) n FROM
       (SELECT playera AS p FROM matches WHERE playerb = ?
        UNION ALL
        SELECT playerb as p FROM matches WHERE playera = ?) AS q
        GROUP BY q.p ORDER BY n DESC LIMIT 1");
    $stmt->bind_param("ss", $this->name, $this->name);
    $stmt->execute();
    $stmt->bind_result($rival, $numtimes);

    $rivalname = null;
    $stmt->fetch();
    $stmt->close();

    if ($rivalname != null) {
      return new Player($rivalname);
    } else {
      return null;
    }
  }

  function getFavoriteNonLand() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT c.name, sum(t.qty) AS qty
      FROM cards c, deckcontents t, entries n
      WHERE n.player = ? AND t.deck = n.deck AND t.issideboard = 0
       AND t.card = c.id AND c.type NOT LIKE '%Land%'
       GROUP BY c.name ORDER BY qty DESC, c.name LIMIT 1");
    if (!$stmt) { die($db->error); }
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $cardname = "none";
    $qty = "0";
    $stmt->bind_result($cardname, $qty);
    $stmt->fetch();

    $stmt->close();

    return "$cardname ($qty)";
  }

  function getFavoriteLand() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT c.name, sum(t.qty) AS qty
      FROM cards c, deckcontents t, entries n
      WHERE n.player = ? AND t.deck = n.deck AND t.issideboard = 0
       AND t.card = c.id AND c.type LIKE 'Basic%'
      GROUP BY c.name ORDER BY qty DESC, c.name LIMIT 1");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $cardname = "none";
    $qty = "0";
    $stmt->bind_result($cardname, $qty);
    $stmt->fetch();

    $stmt->close();

    return "$cardname ($qty)";
  }

  function getMedalCount($type = "all") {
    $db = Database::getConnection();
    if ($type == "all") {
      $stmt = $db->prepare("SELECT count(*) as c FROM entries
        WHERE player = ? AND medal != 'dot'");
      $stmt->bind_param("s", $this->name);
    } else {
      $stmt = $db->prepare("SELECT count(*) as c FROM entries
        WHERE player = ? AND medal = ?");
      $stmt->bind_param("ss", $this->name, $type);
    }
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
  }

  function getLastEventWithTrophy() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.name
      FROM events e, entries n, trophies t
      WHERE n.event = e.name AND n.player = ?
       AND n.medal = \"1st\" and t.event = e.name AND t.image IS NOT NULL
       ORDER BY e.start DESC LIMIT 1");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->store_result();

    $res = NULL;
    if ($stmt->num_rows > 0) {
      $stmt->bind_result($eventname);
      $stmt->fetch();
      $res = $eventname;
    }
    $stmt->close();
    return $res;
  }

  function getEventsWithTrophies() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.name
      FROM events e, entries n, trophies t
      WHERE n.event = e.name AND n.player = ?
       AND n.medal = \"1st\" and t.event = e.name AND t.image IS NOT NULL
       ORDER BY e.start DESC");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($eventname);
    $stmt->store_result();

    $events = array();
    while ($stmt->fetch()) {
      $events[] = $eventname;
    }
    $stmt->close();

    return $events;
  }

  function getFormatsPlayed() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.format FROM entries n, events e, formats f
      WHERE n.player = ? AND e.name = n.event AND e.format = f.name
      GROUP BY e.format ORDER BY f.priority DESC, f.name");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($format);

    $formats = array();
    while ($stmt->fetch()) {
      $formats[] = $format;
    }
    $stmt->close();

    return $formats;
  }

  function getFormatsPlayedStats() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.format, count(n.event) AS cnt
      FROM entries n, events e
      WHERE n.player = ? AND e.name = n.event
      GROUP BY e.format ORDER BY cnt DESC");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($format, $count);

    $formats = array();
    while ($stmt->fetch()) {
      $formats[] = array('format' => $format, 'cnt' => $count);
    }
    $stmt->close();

    return $formats;
  }

  function getMedalStats() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT count(n.event) AS cnt, n.medal
      FROM entries n WHERE n.player = ? AND n.medal != 'dot'
      GROUP BY n.medal ORDER BY n.medal");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($cnt, $medal);

    $medals = array("1st" => 0, "2nd" => 0, "t4" => 0, "t8" => 0);
    while ($stmt->fetch()) {
      $medals[$medal] = $cnt;
    }
    $stmt->close();

    return $medals;
  }

  function getBestDeckStats() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT d.name, count(n.player) AS cnt,
      max(d.id) AS id, sum(n.medal='t8') AS t8, sum(n.medal='t4') AS t4,
      sum(n.medal='2nd') AS 2nd, sum(n.medal='1st') AS 1st,
      sum(n.medal='t8')+2*sum(n.medal='t4')
                       +4*sum(n.medal='2nd')+8*sum(n.medal='1st') AS score
      FROM decks d, entries n
      WHERE d.id = n.deck AND n.player = ?
      GROUP BY d.name
      ORDER BY score DESC, 1st DESC, 2nd DESC, t4 DESC, t8 DESC, cnt ASC");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($name, $cnt, $id, $t8, $t4, $secnd, $first, $score);

    $res = array();
    while ($stmt->fetch()) {
      $res[] = array('name' => $name,
                     'cnt'  => $cnt,
                     'id'   => $id,
                     't8'   => $t8,
                     't4'   => $t4,
                     '2nd'  => $secnd,
                     '1st'  => $first,
                     'score'=> $score);
    }
    $stmt->close();

    return $res;
  }

  function getSeriesPlayedStats() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.series, count(n.event) AS cnt
      FROM events e, entries n
      WHERE n.player = ? AND n.event = e.name
      GROUP BY e.series ORDER BY cnt DESC");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($series, $count);

    $res = array();
    while ($stmt->fetch()) {
      $res[] = array('series' => $series, 'cnt' => $count);
    }
    $stmt->close();

    return $res;
  }

  function getSeriesPlayed() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.series FROM entries n, events e, series s
      WHERE n.player = ? AND e.name = n.event AND e.series = s.name
      GROUP BY e.series ORDER BY s.isactive DESC, s.name");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($series);

    $result = array();

    while ($stmt->fetch()) {
      $result[] = $series;
    }

    $stmt->close();

    return $result;
  }

  function getSeasonsPlayed() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT e.season FROM entries n, events e
      WHERE n.player = ? AND e.name = n.event
      GROUP BY e.season ORDER BY e.season ASC");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($season);

    $seasons = array();
    while ($stmt->fetch()) {
      $seasons[] = $season;
    }

    $stmt->close();
    return $seasons;
  }

  function setIgnoreEvent($eventname, $ignored) {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE entries SET ignored = ? WHERE event = ? AND player = ?");
    $stmt->bind_param("iss", $ignored, $eventname, $this->name);
    $stmt->execute();
    $stmt->close();
  }

  function setPassword($new_password) {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE players SET password = ? WHERE name = ?");
    $hash_pass = hash('sha256', $new_password);
    $stmt->bind_param("ss", $hash_pass, $this->name);
    $stmt->execute();
    $stmt->close();
  }

  function setVerified($toset) {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE players SET mtgo_confirmed = ? WHERE name = ?");
    $setint = $toset ? 1 : 0;
    $stmt->bind_param("is", $setint, $this->name);
    $stmt->execute();
    $stmt->close();
  }

  function setChallenge($new_challenge) {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE players SET mtgo_challenge = ? WHERE name = ?");
    $stmt->bind_param("ss", $new_challenge, $this->name);
    $stmt->execute();
    $stmt->close();
  }

  function checkChallenge($challenge) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name, mtgo_challenge FROM players WHERE name = ?");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $stmt->bind_result($verifyplayer, $db_challenge);
    $stmt->fetch();
    $stmt->close();
    if ((strcasecmp($verifyplayer,$this->name) == 0) && (strcasecmp($db_challenge,$challenge) == 0)) {
      return true;
    } else {
      return false;
    }
  }

  public function stewardsSeries() {
    if ($this->isSuper()) {
      return Series::allNames();
    }
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT series FROM series_stewards WHERE player = ?");
    $stmt->bind_param("s", $this->name);
    $stmt->execute();
    $series = array();
    $stmt->bind_result($seriesname);
    while ($stmt->fetch()) {
      $series[] = $seriesname;
    }
    $stmt->close();
    return $series;
  }

  public function linkTo() {
    $result = "<a href=\"profile.php?player={$this->name}\">$this->name";
    if ($this->verified == 1) {
      $result .= image_tag("verified.png", array("width" => "12", "height" => "12"));
    }
    $result .= "</a>";

    return $result;
  }

  public static function activeCount() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT count(name) FROM players where password is not null");
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    $stmt->close();
    return $result;
  }

  public static function verifiedCount() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT count(name) FROM players where mtgo_confirmed = 1");
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    $stmt->close();
    return $result;
  }
}

