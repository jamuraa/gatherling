<?php

class Match {
  public $id;
  
  public $subevent;
  public $round; 
  
  public $playera;
  public $playerb;

  public $result;

  // Inherited from subevent
  public $timing;
  public $type;
  public $rounds;

  // Inherited from event
  public $format;
  public $series;
  public $season;

  static function destroy($matchid) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("DELETE FROM matches WHERE id = ?"); 
    $stmt->bind_param("d", $matchid); 
    $stmt->execute(); 
    $rows = $stmt->affected_rows;
    $stmt->close(); 
    return $rows;
  } 

  function __construct($id) { 
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT m.subevent, m.round, m.playera, m.playerb, m.result, s.timing, s.type, s.rounds, e.format, e.series, e.season
      FROM matches m, subevents s, events e 
      WHERE m.id = ? AND m.subevent = s.id AND e.name = s.parent"); 
    $stmt->bind_param("d", $id);
    $stmt->execute();
    $stmt->bind_result($this->subevent, $this->round, $this->playera, $this->playerb, $this->result, $this->timing, $this->type, $this->rounds, $this->format, $this->series, $this->season);
    $stmt->fetch(); 
    $stmt->close();
    $this->id = $id;
  } 


  function getEvent() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT s.parent
      FROM subevents s, matches m 
      WHERE m.id = ? AND m.subevent = s.id"); 
    $stmt->bind_param("d", $this->id);
    $stmt->execute(); 
    $stmt->bind_result($eventname);
    $stmt->fetch(); 

    $stmt->close();
    return new Event($eventname);
  } 

  function playerWon($player) { 
    $playername = $player;
    if (is_object($player)) { 
      $playername = $player->name;
    }  
    if ((strcasecmp($this->playera, $playername) == 0) && ($this->result == 'A')) { 
      return true; 
    } 

    if ((strcasecmp($this->playerb, $playername) == 0) && ($this->result == 'B')) {
      return true;
    }

    return false;
  } 

  function playerLost($player) { 
    $playername = $player;
    if (is_object($player)) { 
      $playername = $player->name;
    }  
    if ((strcasecmp($this->playerb, $playername) == 0) && ($this->result == 'A')) { 
      return true; 
    } 

    if ((strcasecmp($this->playera, $playername) == 0) && ($this->result == 'B')) {
      return true;
    }
    
    return false;
  } 

  function getWinner() { 
    if ($this->playerWon($this->playera)) { 
      return $this->playera; 
    } 
    if ($this->playerWon($this->playerb)) { 
      return $this->playerb; 
    } 
    return NULL;
  } 

  function getLoser() { 
    if ($this->playerLost($this->playera)) { 
      return $this->playera; 
    } 
    if ($this->playerLost($this->playerb)) { 
      return $this->playerb;
    } 
    return NULL; 
  } 

  function otherPlayer($oneplayer) { 
    if (strcasecmp($oneplayer, $this->playera) == 0) { 
      return $this->playerb;
    } elseif (strcasecmp($oneplayer, $this->playerb) == 0) { 
      return $this->playera;
    } 
    return NULL;
  } 

  static function count() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT count(id) FROM matches");
    $stmt->execute(); 
    $stmt->bind_result($result);
    $stmt->fetch(); 
    $stmt->close(); 
    return $result;
  } 
} 

