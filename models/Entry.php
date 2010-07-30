<?php

class Entry {
  public $event;
  public $player;
  public $deck;
  public $medal;

  static function findByEventAndPlayer($eventname, $playername) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT deck, medal FROM entries WHERE event = ? AND player = ?");
    $stmt->bind_param("ss", $eventname, $playername);
    $stmt->store_result();
    $found = false;
    if ($stmt->num_rows > 0) {
      $found = true;
    }
    $stmt->close();

    if ($found) {
      return new Entry($eventname, $playername);
    } else {
      return NULL;
    }
  }

  function __construct($eventname, $playername) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT deck, medal, ignored FROM entries WHERE event = ? AND player = ?");
    $stmt or die($db->error);
    $stmt->bind_param("ss", $eventname, $playername);
    $stmt->execute();
    $this->ignored = 0;
    $stmt->bind_result($deckid, $this->medal, $this->ignored);
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
    if (($this->event->finalized == 0) && (strcasecmp($username, $this->player->name) == 0)) {
      return true;
    }
    $player = new Player($username);
    if ($player->isSuper()) {
      return true;
    }
    return $this->event->isHost($username) || $this->event->isSteward($username);
  }

  function setIgnored($new_ignored) {
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE entries SET ignored = ? WHERE player = ? and event = ?");
    $playername = $this->player->name;
    $eventname = $this->event->name;
    $stmt->bind_param("iss", $new_ignored, $playername, $eventname);
    $stmt->execute();
    $stmt->close();
  }
}


