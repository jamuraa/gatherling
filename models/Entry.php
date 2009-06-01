<?php

class Entry {
  public $event; 
  public $player; 
  public $deck; 
  public $medal;

  function __construct($eventname, $playername) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT deck, medal FROM entries WHERE event = ? AND player = ?"); 
    $stmt or die($db->error);
    $stmt->bind_param("ss", $eventname, $playername); 
    $stmt->execute(); 
    $stmt->bind_result($deckid, $this->medal);
    if ($stmt->fetch() == NULL) { 
      throw new Exception('Entry for '. $playername .' in '. $eventname .' not found');
    } 

    $stmt->close(); 

    $this->event = new Event($eventname); 
    $this->player = new Player($playername);

    if ($deckid != NULL) { 
      $this->deck = new Deck($deckid);
    } else { 
      $this->deck = NULL; 
    } 
  } 

  function recordString() { 
    $matches = $this->getMatches();
    $wins = 0;
    $losses = 0;
    $draws = 0;
    foreach ($matches as $match) { 
      if ($match->playerWon($this->player)) { 
        $wins = $wins + 1;
      } else if ($match->playerLost($this->player)) { 
        $losses = $losses + 1;
      } else { 
        $draws = $draws + 1;
      } 
    } 

    if ($draws == 0) { 
      return $wins . "-" . $losses; 
    } else { 
      return $wins . "-" . $losses . "-" . $draws;
    } 
  } 

  function getMatches() { 
    return $this->player->getMatchesEvent($this->event->name);
  } 

  function canCreateDeck($username) { 
    if (strcasecmp($username, $this->player->name) == 0) { 
      return true; 
    } 
    $player = new Player($username);
    if ($player->isSuper()) { 
      return true; 
    } 
    return $this->event->isHost($username) || $this->event->isSteward($username);
  } 
} 


