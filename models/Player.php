<?php

class Player {
  public $name;
  public $password;
  public $host;
  public $super; 

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

    $hashpwd = hash('sha256', $_POST['password']);

    return strcmp($srvpass, $hashpwd) == 0;
  } 

  function __construct($name) { 
    $database = Database::getConnection();
    $stmt = $database->prepare("SELECT password, host, super FROM players WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($this->password, $this->host, $this->super);
    if ($stmt->fetch() == NULL) { 
      throw new Exception('Player '. $name .' is not found.');
    } 
    $this->name = $name;
    $stmt->close();
  }

  function isHost() { 
    return ($this->host == 1);
  } 

  function isSuper() { 
    return ($this->super == 1); 
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
      WHERE n.player = ? AND n.event = e.name 
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
          $filteredMatches[] = array(); 
        } 
      } 
      $matches = $filteredMatches;
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
    
    $rival = "none";
    $stmt->fetch(); 
    $stmt->close(); 

    return $rival;
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
    if ($stmt->num_rows > 0) { 
      $stmt->bind_result($eventname); 
      $stmt->fetch(); 
      return $eventname;
    } else { 
      return NULL;
    } 
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

    return $formats;
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

    return $seasons;
  } 
}

