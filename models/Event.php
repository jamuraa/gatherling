<?php 

class Event {
  public $name;
  
  public $season;
  public $number;
  public $format;
 
  public $start;
  public $kvalue;
  public $finalized;
  public $threadurl;
  public $reporturl;
  public $metaurl;

  // Class associations
  public $subevents = array(); // has many Subevents
  public $trophy; // has one Trophy
  public $series; // belongs to Series
  public $host; // has one Player - host
  public $cohost; // has one Player - cohost

  public $players = array(); // has many Players through entries
  public $decks = array(); // has many Decks through entries
  public $matches = array(); // has many Matches through subevents

  function __construct($name) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT format, host, cohost, series, season, number, start, kvalue, finalized, threadurl, metaurl, reporturl FROM events WHERE name = ?"); 
    $stmt->bind_param("s", $name); 
    $stmt->execute(); 
    $stmt->bind_result($this->format, $this->host, $this->cohost, $this->series, $this->season, $this->number, $this->start, $this->kvalue, $this->finalized, $this->threadurl, $this->metaurl, $this->reporturl); 
    if ($stmt->fetch() == NULL) { 
      throw new Exception('Event '. $name .' not found in DB');
    } 

    $stmt->close(); 
    $this->name = $name;
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

  function getTrophyImageLink() { 
    return "<a href=\"deck.php?mode=view&event={$this->name}\">\n"
          ."<img style=\"border-width: 0px;\" src=\"displayTrophy.php?event={$this->name}\" />\n"
          ."</a>\n";
  }


  function isHost($name) { 
    $ishost = strcasecmp($name, $this->host) == 0;
    $iscohost = strcasecmp($cname, $this->cohost) == 0;
    return $ishost || $iscohost;
  }  

  function isSteward($name) { 
    $db = Database::getConnection(); 
    $result = $db->query("SELECT s.player FROM stewards s
      WHERE s.event = {$this->name}");
    while ($res = $result->fetch_assoc()) { 
      if (strcasecmp($res['player'], $name) == 0) { 
        return true; 
      } 
    } 
    return false;
  } 

  function getPlayerCount() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT count(*) FROM entries WHERE event = ?");
    $stmt->bind_param("s", $this->name);
    $stmt->execute(); 
    $stmt->bind_result($count); 
    $stmt->fetch(); 
    $stmt->close(); 
    return $count;
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
}
