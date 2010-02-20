<?php 

class Series {
  public $name;
  public $active;
  public $start_day;
  public $start_time;
  public $stewards; # has many :stewards, :through => series_stewards, :class_name => Player

  function __construct($name) { 
    if ($name == "") { 
      $this->name = ""; 
      $this->start_day = ""; 
      $this->start_time = "";
      $this->stewards = array();
      $this->new = true;
      return; 
    } 

    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT isactive, day, normalstart FROM series WHERE name = ?"); 
    $stmt or die($db->error);
    $stmt->bind_param("s", $name); 
    $stmt->execute();
    $stmt->bind_result($this->active, $this->start_day, $this->start_time); 
    if ($stmt->fetch() == NULL) { 
      throw new Exception('Series '. $name .' not found in DB');
    } 

    $stmt->close(); 

    $this->name = $name;

    // Stewards
    $stmt = $db->prepare("SELECT player FROM series_stewards WHERE series = ?");
    $stmt->bind_param("s", $this->name); 
    $stmt->execute(); 
    $stmt->bind_result($one_player); 
    $this->stewards = array();
    while ($stmt->fetch()) { 
      $this->stewards[] = $one_player;
    } 
    $stmt->close();
    
    $this->new = false;
  } 

  function save() { 
    $db = Database::getConnection();
    if ($this->new) { 
      $stmt = $db->prepare("INSERT INTO series(name, day, normalstart, isactive) values(?, ?, ?)");
      $stmt->bind_param("sssd", $this->name, $this->start_day, $this->start_time, $this->active); 
      $stmt->execute() or die($stmt->error);
      $stmt->close(); 
    } else { 
      $stmt = $db->prepare("UPDATE series SET day = ?, normalstart = ?, isactive = ? WHERE name = ?");
      $stmt or die($db->error); 
      $stmt->bind_param("ssds", $this->start_day, $this->start_time, $this->active, $this->name); 
      $stmt->execute() or die($stmt->error);
      $stmt->close(); 
    }
  } 

  function isSteward($name) {
    return in_array($name, $this->stewards);
  }

  function addSteward($name) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("INSERT INTO series_stewards(series, player) VALUES(?, ?)");
    $stmt->bind_param("ss", $this->name, $name); 
    $stmt->execute(); 
    $stmt->close(); 
  } 

  function removeSteward($name) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("DELETE FROM series_stewards WHERE series = ? AND player = ?");
    $stmt->bind_param("ss", $this->name, $name); 
    $stmt->execute(); 
    $stmt->close(); 
  } 

  function authCheck($playername) { 
    if ($this->isSteward($playername)) { 
      return true; 
    }
    $player = new Player($playername); 
    return $player->isSuper(); 
  } 

  function getEvents() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT name FROM events WHERE series = ?"); 
    $stmt->bind_param("s", $this->name);
    $stmt->execute(); 
    $stmt->bind_result($eventname); 

    $events = array(); 
    while ($stmt->fetch()) { 
      $events[] = $eventname; 
    } 
    $stmt->close(); 

    return $events;
  }

  function getRecentEvents($number = 10) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT name FROM events WHERE series = ? ORDER BY start DESC LIMIT ?"); 
    $stmt->bind_param("sd", $this->name, $number);
    $stmt->execute(); 
    $stmt->bind_result($eventname); 

    $eventnames = array();
    while ($stmt->fetch()) { 
      $eventnames[] = $eventname;
    } 
    $stmt->close(); 

    $events = array();
    foreach ($eventnames as $name) { 
      $events[] = new Event($name);
    } 

    return $events;
  } 

  public static function exists($name) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT name FROM series WHERE name = ?"); 
    $stmt->bind_param("s", $name); 
    $stmt->execute(); 
    $stmt->store_result();
    $series_exists = $stmt->num_rows > 0; 
    $stmt->close(); 
    return $series_exists;
  } 

  public static function allNames() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT name FROM series"); 
    $stmt->execute(); 
    $stmt->bind_result($onename);
    $names = array(); 
    while ($stmt->fetch()) { 
      $names[] = $onename; 
    } 
    $stmt->close(); 
    return $names;
  }

  public function setLogo($content, $type, $size) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("UPDATE series SET logo = ?, imgsize = ?, imgtype = ? WHERE name = ?"); 
    $stmt->bind_param("bdss", $null, $size, $type, $this->name); 
    $stmt->send_long_data(0, $content);
    $stmt->execute() or die($stmt->error); 
    $stmt->close(); 
  } 

  public function currentSeason() { 
    $seasonnum = 0;
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT season FROM events WHERE series = ? ORDER BY start DESC LIMIT 1"); 
    $stmt->bind_param("s", $this->name); 
    $stmt->execute();
    $stmt->bind_result($seasonnum);
    $stmt->fetch(); 
    $stmt->close();
    return $seasonnum;
  } 

  // TODO: THESE functions are UGLY. 
  public function getSeasonRules($season_number) { 
    $season_rules = array('first_pts' => 0, 'second_pts' => 0, 'semi_pts' => 0, 'quarter_pts' => 0, 'participation_pts' => 0, 'rounds_pts' => 0, 'decklist_pts' => 0, 'win_pts' => 0, 'loss_pts' => 0, 'bye_pts' => 0);
    
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT series, season, first_pts, second_pts, semi_pts, quarter_pts, participation_pts, rounds_pts, decklist_pts, win_pts, loss_pts, bye_pts FROM series_seasons WHERE series = ? AND season = ?"); 
    $stmt->bind_param("ss", $this->name, $season_number);
    $stmt->execute(); 
    $stmt->bind_result($seriesname, $season_number, $season_rules['first_pts'], $season_rules['second_pts'], $season_rules['semi_pts'], $season_rules['quarter_pts'], $season_rules['participation_pts'], $season_rules['rounds_pts'], $season_rules['decklist_pts'], $season_rules['win_pts'], $season_rules['loss_pts'], $season_rules['bye_pts']);
    $stmt->fetch(); 
    $stmt->close(); 
    return $season_rules;
  }

  public function setSeasonRules($season_number, $new_rules) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("INSERT INTO series_seasons(series, season, first_pts, second_pts, semi_pts, quarter_pts, participation_pts, rounds_pts, decklist_pts, win_pts, loss_pts, bye_pts) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE first_pts=?, second_pts=?, semi_pts=?, quarter_pts=?, participation_pts=?, rounds_pts=?, decklist_pts=?, win_pts=?, loss_pts=?, bye_pts=?");
    $stmt->bind_param("ssssssssssssssssssssss", $this->name, $season_number, $new_rules['first_pts'], $new_rules['second_pts'], $new_rules['semi_pts'], $new_rules['quarter_pts'], $new_rules['participation_pts'], $new_rules['rounds_pts'], $new_rules['decklist_pts'], $new_rules['win_pts'], $new_rules['loss_pts'], $new_rules['bye_pts'], $new_rules['first_pts'], $new_rules['second_pts'], $new_rules['semi_pts'], $new_rules['quarter_pts'], $new_rules['participation_pts'], $new_rules['rounds_pts'], $new_rules['decklist_pts'], $new_rules['win_pts'], $new_rules['loss_pts'], $new_rules['bye_pts']);
    $stmt->execute(); 
    $stmt->close();
    return $new_rules;
  } 

  // Each of these functions will return a array in the form 
  //  array('playername' => event_count, ..). 
  // For example, getParticipations(11) => array('jamuraa' => 4, ... ) would mean jamuraa participated in 4 events this season.
  private function getPlacePlayers($season_number, $place) {
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT entries.player, count(entries.medal) FROM events JOIN entries ON events.name = entries.event WHERE events.series = ? AND events.season = ? AND entries.medal = ? AND events.number != 128 GROUP BY entries.player"); 
    $stmt or die($db->error);
    $stmt->bind_param("sds", $this->name, $season_number, $place); 
    $stmt->execute(); 
    $stmt->bind_result($playername, $medals); 
    $result = array(); 
    while ($stmt->fetch()) { 
      $result[$playername] = $medals;
    }
    return $result;  
  } 
  
  private function getParticipations($season_number) {
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT entries.player, count(entries.player) FROM events JOIN entries ON events.name = entries.event WHERE events.series = ? AND events.season = ? AND events.number != 128 GROUP BY entries.player"); 
    $stmt->bind_param("sd", $this->name, $season_number); 
    $stmt->execute(); 
    $stmt->bind_result($playername, $medals); 
    $result = array(); 
    while ($stmt->fetch()) { 
      $result[$playername] = $medals;
    }
    return $result;
  }

  private function getRoundsPlayed($season_number) { 
    $db = Database::getConnection(); 
    // This is a bit complicated we have to find the number of the last round in the 
    // main event which the player played in, in each event in the season, and sum those together.
    //
    // For this purpose, we build the array: 
    // array('player' => array('event' => last_round))
    $player_event_array = array();
    
    // First, if they were playera:
    $stmt = $db->prepare("SELECT events.name, matches.playera, max(matches.round) FROM events JOIN subevents JOIN matches ON events.name = subevents.parent AND subevents.id = matches.subevent WHERE events.series = ? AND events.season = ? AND subevents.timing = 1 AND events.number != 128 GROUP BY events.name, matches.playera ORDER BY events.name, matches.round");
    $stmt->bind_param("sd", $this->name, $season_number); 
    $stmt->execute(); 
    $stmt->bind_result($event_name, $playername, $maxround);

    while ($stmt->fetch()) { 
      if (!isset($player_event_array[$playername])) { 
        $player_event_array[$playername] = array(); 
      }
      if (!isset($player_event_array[$playername][$event_name])) {
        $player_event_array[$playername][$event_name] = $maxround;
      } else {
        $player_event_array[$playername][$event_name] = max($maxround, $player_event_array[$playername][$event_name]);
      } 
    } 
    $stmt->close();
    // Then, if they were playerb: 
    $stmt = $db->prepare("SELECT events.name, matches.playerb, max(matches.round) FROM events JOIN subevents JOIN matches ON events.name = subevents.parent AND subevents.id = matches.subevent WHERE events.series = ? AND events.season = ? AND subevents.timing = 1 AND events.number != 128 GROUP BY events.name, matches.playerb ORDER BY events.name, matches.round");
    $stmt->bind_param("sd", $this->name, $season_number); 
    $stmt->execute(); 
    $stmt->bind_result($event_name, $playername, $maxround);

    while ($stmt->fetch()) { 
      if (!isset($player_event_array[$playername])) { 
        $player_event_array[$playername] = array(); 
      }
      if (!isset($player_event_array[$playername][$event_name])) {
        $player_event_array[$playername][$event_name] = $maxround;
      } else {
        $player_event_array[$playername][$event_name] = max($maxround, $player_event_array[$playername][$event_name]);
      } 
    } 
    $stmt->close(); 

    // Now we add up all the rounds that the player had in each event, and that is how many rounds they played in a season.
    $result = array(); 
    foreach ($player_event_array as $name => $event_array) {
      $result[$name] = 0;
      foreach ($event_array as $event_name => $maxround) { 
        $result[$name] = $result[$name] + $maxround;
      } 
    } 
    return $result; 
  } 


  private function getDecklistPosteds($season_number) { 
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT entries.player, count(entries.deck) c FROM events JOIN entries ON entries.event = events.name WHERE entries.deck IS NOT NULL AND events.number != 128 AND events.series = ? AND events.season = ? GROUP BY entries.player");
    $stmt->bind_param("sd", $this->name, $season_number); 
    $stmt->execute(); 
    $stmt->bind_result($playername, $deckcount);
    
    $result = array(); 
    while ($stmt->fetch()) { 
      $result[$playername] = $deckcount;
    }

    return $result;
  } 

  private function getRoundsWon($season_number) {
    $db = Database::getConnection(); 

    $result = array();
    // They could.. 
    // Win as playera
    $stmt = $db->prepare("SELECT matches.playera, count(matches.round) FROM events JOIN subevents JOIN matches ON events.name = subevents.parent AND subevents.id = matches.subevent WHERE subevents.timing = 1 AND events.number != 128 AND matches.result = 'A' AND events.series = ? AND events.season = ? GROUP BY matches.playera");
    $stmt->bind_param("sd", $this->name, $season_number);
    $stmt->execute(); 
    $stmt->bind_result($playername, $matcheswon); 
    while ($stmt->fetch()) { 
      if (!isset($result[$playername])) { 
        $result[$playername] = $matcheswon;
      } else { 
        $result[$playername] = $result[$playername] + $matcheswon;
      } 
    } 
    $stmt->close(); 

    // Or win as playerb
    $stmt = $db->prepare("SELECT matches.playerb, count(matches.round) FROM events JOIN subevents JOIN matches ON events.name = subevents.parent AND subevents.id = matches.subevent WHERE subevents.timing = 1 AND events.number != 128 AND matches.result = 'B' AND events.series = ? AND events.season = ? GROUP BY matches.playera");
    $stmt->bind_param("sd", $this->name, $season_number);
    $stmt->execute(); 
    $stmt->bind_result($playername, $matcheswon); 
    while ($stmt->fetch()) { 
      if (!isset($result[$playername])) { 
        $result[$playername] = $matcheswon;
      } else { 
        $result[$playername] = $result[$playername] + $matcheswon;
      } 
    } 
    $stmt->close(); 

    return $result;
  } 
  
  private function getRoundsLost($season_number) {
    $db = Database::getConnection(); 

    $result = array();
    // They could.. 
    // Lose as playera
    $stmt = $db->prepare("SELECT matches.playera, count(matches.round) FROM events JOIN subevents JOIN matches ON events.name = subevents.parent AND subevents.id = matches.subevent WHERE subevents.timing = 1 AND events.number != 128 AND matches.result = 'B' AND events.series = ? AND events.season = ? GROUP BY matches.playera");
    $stmt->bind_param("sd", $this->name, $season_number);
    $stmt->execute(); 
    $stmt->bind_result($playername, $matcheswon); 
    while ($stmt->fetch()) { 
      if (!isset($result[$playername])) { 
        $result[$playername] = $matcheswon;
      } else { 
        $result[$playername] = $result[$playername] + $matcheswon;
      } 
    } 
    $stmt->close(); 

    // Or lose as playerb
    $stmt = $db->prepare("SELECT matches.playerb, count(matches.round) FROM events JOIN subevents JOIN matches ON events.name = subevents.parent AND subevents.id = matches.subevent WHERE subevents.timing = 1 AND events.number != 128 AND matches.result = 'A' AND events.series = ? AND events.season = ? GROUP BY matches.playera");
    $stmt->bind_param("sd", $this->name, $season_number);
    $stmt->execute(); 
    $stmt->bind_result($playername, $matcheswon); 
    while ($stmt->fetch()) { 
      if (!isset($result[$playername])) { 
        $result[$playername] = $matcheswon;
      } else { 
        $result[$playername] = $result[$playername] + $matcheswon;
      } 
    } 
    $stmt->close(); 

    return $result;
  } 

  // The BYE rounds that we can DETECT are - 
  // $rounds_bye = $rounds_played - $rounds_won - $rounds_lost
  private function getRoundsBye($rounds_played, $rounds_won, $rounds_lost) { 
    $result = $rounds_played;
    foreach ($rounds_won as $playername => $rounds) { 
      $result[$playername] = $result[$playername] - $rounds; 
    } 
    foreach ($rounds_lost as $playername => $rounds) { 
      $result[$playername] = $result[$playername] - $rounds; 
    } 
    return $result;
  }

  private function multiply_and_add_points(&$results, $thispoints, $multiplier) { 
    foreach ($thispoints as $playername => $times) { 
      if (!isset($results[$playername])) { 
        $results[$playername] = $times * $multiplier;
      } else { 
        $results[$playername] = $results[$playername] + ($times * $multiplier);
      } 
    } 
  } 

  public function seasonPointsTable($season_number) { 
    $rules = $this->getSeasonRules($season_number);

    $total_pointarray = array();
    $firsts = $this->getPlacePlayers($season_number, '1st');
    $this->multiply_and_add_points($total_pointarray, $firsts, $rules['first_pts']);
    $seconds = $this->getPlacePlayers($season_number, '2nd');
    $this->multiply_and_add_points($total_pointarray, $seconds, $rules['second_pts']);
    $semis = $this->getPlacePlayers($season_number, 't4');
    $this->multiply_and_add_points($total_pointarray, $semis, $rules['semi_pts']);
    $quarters = $this->getPlacePlayers($season_number, 't8');
    $this->multiply_and_add_points($total_pointarray, $quarters, $rules['quarter_pts']);
    $decklists_posted = $this->getDecklistPosteds($season_number);
    $this->multiply_and_add_points($total_pointarray, $decklists_posted, $rules['decklist_pts']);
    $rounds_played = $this->getRoundsPlayed($season_number);
    $this->multiply_and_add_points($total_pointarray, $rounds_played, $rules['rounds_pts']);
    $rounds_won = $this->getRoundsWon($season_number);
    $this->multiply_and_add_points($total_pointarray, $rounds_won, $rules['win_pts']);
    $rounds_lost = $this->getRoundsLost($season_number); 
    $this->multiply_and_add_points($total_pointarray, $rounds_lost, $rules['loss_pts']);
    $rounds_bye = $this->getRoundsBye($rounds_played, $rounds_won, $rounds_lost);
    $this->multiply_and_add_points($total_pointarray, $rounds_bye, $rules['bye_pts']);
    $participations = $this->getParticipations($season_number);
    $this->multiply_and_add_points($total_pointarray, $participations, $rules['participation_pts']);

    // Include adjustments.
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT player, adjustment FROM season_points WHERE series = ? AND season = ?");
    $stmt or die($db->error);
    $stmt->bind_param("sd", $this->name, $season_number); 
    $stmt->execute(); 
    $stmt->bind_result($player, $adjustment); 
    while ($stmt->fetch()) { 
      $total_pointarray[$player] = $total_pointarray[$player] + $adjustment;
    } 

    return $total_pointarray; 
  } 
}
