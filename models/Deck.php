<?php

class Deck {
  public $id; 
  public $name;
  public $archetype; 
  public $notes;
  
  public $sideboard_cards = array(); // Has many sideboard_cards through deckcontents (issideboard = 1)
  public $maindeck_cards = array(); // Has many maindeck_cards through deckcontente (issideboard = 0)

  public $cardcount = 0;

  public $errors = array();

  public $playername; // Belongs to player through entries
  public $eventname; // Belongs to event thorugh entries

  public $medal; // has a medal

  public $new; // is new

  function __construct($id) { 
    if ($id == 0) { 
      $this->id = 0;
      $this->new = true;
      return;
    } 
    $database = Database::getConnection(); 
    $stmt = $database->prepare("SELECT name, archetype, notes, deck_hash, sideboard_hash, whole_hash
      FROM decks d
      WHERE id = ?");
    $stmt->bind_param("d", $id);
    $stmt->execute();
    $stmt->bind_result($this->name, $this->archetype, $this->notes, $this->deck_hash, $this->sideboard_hash, $this->whole_hash);

    if ($stmt->fetch() == NULL) { 
      $this->id = 0;
      $this->new = true;
      return;
    }

    $this->new = false;
    $this->id = $id; 

    $stmt->close();
    // Retrieve cards.
    $stmt = $database->prepare("SELECT c.name, dc.qty, dc.issideboard
      FROM cards c, deckcontents dc, decks d
      WHERE d.id = dc.deck AND c.id = dc.card AND d.id = ?"); 
    $stmt->bind_param("d", $id);
    $stmt->execute(); 
    $stmt->bind_result($cardname, $cardqty, $isside);

    $this->cardcount = 0;
    while ($stmt->fetch()) {
      if ($isside == 0) {
        $this->maindeck_cards[$cardname] = $cardqty;
        $this->cardcount += $cardqty;
      } else {
        $this->sideboard_cards[$cardname] = $cardqty;
      }
    }

    $stmt->close();

    // Retrieve player
    $stmt = $database->prepare("SELECT p.name 
      FROM players p, entries e, decks d
      WHERE p.name = e.player AND d.id = e.deck AND d.id = ?");
    $stmt->bind_param("d", $id);
    $stmt->execute();
    $stmt->bind_result($this->playername);
    $stmt->fetch();

    $stmt->close();

    // Retrieve event 
    $stmt = $database->prepare("SELECT e.name
      FROM events e, entries n, decks d
      WHERE d.id = ? and d.id = n.deck AND n.event = e.name"); 
    $stmt->bind_param("d", $id);
    $stmt->execute(); 
    $stmt->bind_result($this->eventname);
    $stmt->fetch();
    $stmt->close();

    // Retrieve medal 
    $stmt = $database->prepare("SELECT n.medal
      FROM entries n WHERE n.deck = ?");
    $stmt->bind_param("d", $id);
    $stmt->execute();
    $stmt->bind_result($this->medal);
    $stmt->fetch();
    $stmt->close();
    if ($this->medal == NULL) { $this->medal = "dot"; }
  }

  static function getArchetypes() {
    $db = Database::getConnection();
    $result = $db->query("SELECT name FROM archetypes WHERE priority > 0
      ORDER BY priority DESC, name");
    $ret = array();
    while ($arch = $result->fetch_assoc()) {
      $ret[] = $arch['name'];
    }
    $result->close();
    return $ret;
  }

  function getEntry() {
    return new Entry($this->eventname, $this->playername);
  } 

  function recordString() {
    if ($this->playername == NULL) { return "?-?"; }
    return $this->getEntry()->recordString();
  } 

  function getColorImages() {
    $count = $this->getColorCounts();
    $str = ""; 
    foreach ($count as $color => $n) { 
      if ($n > 0) { 
        $str = $str . "<img src=\"/images/mana$color.gif\" />";
      } 
    }  
    return $str;
  } 

  function getColorCounts() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT sum(isw*d.qty) AS w, sum(isg*d.qty) AS g,
      sum(isu*d.qty) AS u, sum(isr*d.qty) AS r, sum(isb*d.qty) AS b
      FROM cards c, deckcontents d 
      WHERE d.deck = ? AND c.id = d.card AND d.issideboard != 1"); 
    $stmt->bind_param("d", $this->id);
    $stmt->execute(); 
    $count = array();
    $stmt->bind_result($count["w"], $count["g"], $count["u"], $count["r"], $count["b"]);
    $stmt->fetch();
    
    $stmt->close();
    return $count;
  }

  function getCastingCosts() { 
    $db = Database::getConnection(); 
    $result = $db->query("SELECT convertedcost AS cc, sum(qty) as s
      FROM cards c, deckcontents d 
      WHERE d.deck = {$this->id} AND c.id = d.card AND d.issideboard = 0
      GROUP BY c.convertedcost HAVING cc > 0"); 

    $convertedcosts = array(); 
    while ($res = $result->fetch_assoc()) { 
      $convertedcosts[$res['cc']] = $res['s']; 
    } 

    return $convertedcosts; 
  } 

  function getEvent() { 
    return new Event($this->eventname); 
  } 
  
  function getCardCount() { 
    $count = 0; 
    foreach ($this->maindeck_cards as $card => $qty) { 
      $count = $count + $qty; 
    }
    return $count; 
  }  

  function getCreatureCards() { 
    $db = Database::getConnection(); 
    $result = $db->query("SELECT dc.qty, c.name
      FROM deckcontents dc, cards c 
      WHERE c.id = dc.card AND dc.deck = {$this->id} 
      AND c.type LIKE '%Creature%' 
      AND dc.issideboard = 0 
      ORDER BY dc.qty DESC, c.name"); 

    $cards = array(); 
    while ($res = $result->fetch_assoc()) { 
      $cards[$res['name']] = $res['qty'];
    } 

    return $cards;
  } 

  function getLandCards() { 
    $db = Database::getConnection(); 
    $result = $db->query("SELECT dc.qty, c.name
      FROM deckcontents dc, cards c 
      WHERE c.id = dc.card AND dc.deck = {$this->id} 
      AND c.type LIKE '%Land%' 
      AND dc.issideboard = 0 
      ORDER BY dc.qty DESC, c.name"); 

    $cards = array(); 
    while ($res = $result->fetch_assoc()) { 
      $cards[$res['name']] = $res['qty'];
    } 

    return $cards;
  } 

  function getOtherCards() { 
    $db = Database::getConnection(); 
    $result = $db->query("SELECT dc.qty, c.name
      FROM deckcontents dc, cards c 
      WHERE c.id = dc.card AND dc.deck = {$this->id} 
      AND c.type NOT LIKE '%Creature%' AND c.type NOT LIKE '%Land%'
      AND dc.issideboard = 0 
      ORDER BY dc.qty DESC, c.name"); 

    $cards = array(); 
    while ($res = $result->fetch_assoc()) { 
      $cards[$res['name']] = $res['qty'];
    } 

    return $cards;
  } 

  function getMatches() { 
    if ($this->playername == NULL) { return array(); }
    return $this->getEntry()->getMatches();
  } 

  function getPlayer() { 
    return new Player($this->playername); 
  } 

  function canEdit($username) { 
    if (strcasecmp($username, $this->playername) == 0) { 
      return true; 
    } 
    $player = new Player($username);
    if ($player->isSuper()) { 
      return true; 
    } 
    $event = $this->getEvent(); 
    return $event->isHost($username) || $event->isSteward($username);
  } 

  private function getCard($cardname) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT id, name FROM cards WHERE name = ?");
    $stmt->bind_param("s", $cardname); 
    $stmt->execute(); 
    $cardar = array();
    $stmt->bind_result($cardar['id'], $cardar['name']); 
    if (is_null($stmt->fetch())) { 
      $cardar = NULL; 
    } 
    $stmt->close(); 

    return $cardar;
  }

  function validate() {
    // Name must exist
    if ($this->name == NULL || $this->name == "") {
      $this->errors[] = "Name cannot be blank";
    }
    if ($this->archetype != "Unclassified" && !in_array($this->archetype, Deck::getArchetypes())) {
      $this->errors[] = "Archetype needs to be in the approved list";
    }
    if (count($this->errors) > 0) {
      return false;
    }
    return true;
  }

  function save() { 
    $db = Database::getConnection(); 
    $db->autocommit(FALSE);

    if (!$this->validate()) {
      return false;
    }

    if ($this->id == 0) { 
      // New record.  Set up the decks entry and the Entry.
      $stmt = $db->prepare("INSERT INTO decks (archetype, name, notes) 
        values(?, ?, ?)");
      $stmt->bind_param("sss", $this->archetype, $this->name, $this->notes); 
      $stmt->execute();
      $this->id = $stmt->insert_id;

      $stmt = $db->prepare("UPDATE entries SET deck = {$this->id} WHERE player = ? AND event = ?");
      $stmt->bind_param("ss", $this->playername, $this->eventname);
      $stmt->execute(); 
      if ($stmt->affected_rows != 1) { 
        $db->rollback(); 
        $db->autocommit(TRUE);
        throw new Exception("Can't find entry for {$this->playername} in {$this->eventname}");
      } 
    } else { 
      $stmt = $db->prepare("UPDATE decks SET archetype = ?, name = ?,
        notes = ? WHERE id = ?"); 
      if (!$stmt) { 
        echo $db->error;
      } 
      $stmt->bind_param("sssd", $this->archetype, $this->name, $this->notes, $this->id); 
      if (!$stmt->execute()) { 
        $db->rollback(); 
        $db->autocommit(TRUE);
        throw new Exception('Can\'t update deck '. $this->id); 
      }
    }

    $succ = $db->query("DELETE FROM deckcontents WHERE deck = {$this->id}");

    if (!$succ) {
      $db->rollback(); 
      $db->autocommit(TRUE);
      throw new Exception("Can't update deck contents {$this->id}"); 
    }

    $newmaindeck = array();
    foreach ($this->maindeck_cards as $card => $amt) {
      $card = stripslashes($card);
      $cardar = $this->getCard($card);
      if (is_null($cardar)) {
        if (!isset($this->unparsed_cards[$card])) {
          $this->unparsed_cards[$card] = 0;
        }
        $this->unparsed_cards[$card] += $amt;
        continue;
      }
      $stmt = $db->prepare("INSERT INTO deckcontents (deck, card, issideboard, qty) values(?, ?, 0, ?)");
      $stmt->bind_param("ddd", $this->id, $cardar['id'], $amt);
      $stmt->execute();
      $newmaindeck[$cardar['name']] = $amt;
    }

    $this->maindeck_cards = $newmaindeck;

    $newsideboard = array();
    foreach ($this->sideboard_cards as $card => $amt) {
      $card = stripslashes($card);
      $cardar = $this->getCard($card);
      if (is_null($cardar)) {
        if (!isset($this->unparsed_side[$card])) {
          $this->unparsed_side[$card] = 0;
        } 
        $this->unparsed_side[$card] += $amt;
        continue; 
      }
      $stmt = $db->prepare("INSERT INTO deckcontents (deck, card, issideboard, qty) values(?, ?, 1, ?)"); 
      $stmt->bind_param("ddd", $this->id, $cardar['id'], $amt); 
      $stmt->execute();
      $newsideboard[$cardar['name']] = $amt;
    }

    $this->sideboard_cards = $newsideboard;

    $this->deck_contents_cache = implode('|', array_merge(array_keys($this->maindeck_cards),
                                                          array_keys($this->sideboard_cards)));

    $stmt = $db->prepare("UPDATE decks set deck_contents_cache = ? WHERE id = ?");

    $stmt->bind_param("sd", $this->deck_contents_cache, $this->id);
    $stmt->execute();

    $db->commit();
    $db->autocommit(TRUE);
    $this->calculateHashes();
    return true;
  }

  function findIdenticalDecks() { 
    if (!isset($this->identicalDecks)) {
      $db = Database::getConnection();
      $stmt = $db->prepare("SELECT d.id FROM decks d, entries n, events e WHERE deck_hash = ? AND id != ? AND n.deck = d.id AND e.name = n.event ORDER BY e.start DESC");
      $stmt->bind_param("sd", $this->deck_hash, $this->id);
      $same_ids = array();
      $this_id = 0;
      $stmt->execute();
      $stmt->bind_result($this_id);
      while ($stmt->fetch()) { 
        $same_ids[] = $this_id;
      } 
      $stmt->close(); 
  
      $decks = array();

      foreach ($same_ids as $other_deck_id) { 
        $possibledeck = new Deck($other_deck_id); 
        if (isset($possibledeck->playername)) { 
          $decks[] = $possibledeck; 
        } 
      } 
      $this->identical_decks = $decks;
    }
    return $this->identical_decks;
  }

  function calculateHashes() {
    # Deck HASHES are an easy way to compare two decks for EQUALITY.
    # They are computed as follows:
    #  A string is built with the following format:
    #   "(amt)(Cardname)(amt)(Cardname)..."
    #  The cardnames are unique per Magic: The Gathering
    #  The cardnames are lexographically sorted!
    #  The amounts are NOT PADDED: 1 => 1, 10 => 10, 100 => 100
    #  There is NO SPACE BETWEEN THE amount and the cardname, or between cards
    #  Make this string for the main deck and the sideboard. 
    #  Concatenate these strings: maindeckStr + "<sb>" + sideboardStr
    #  Make a SHA-1 hash of this string for the whole_hash
    #  Make a SHA-1 hash of the maindeckStr for the maindeck_hash
    #  Make a SHA-1 hash of the sideboardStr for the sideboard_hash
    $cards = array_keys($this->maindeck_cards);
    sort($cards, SORT_STRING);
    $maindeckStr = "";
    foreach ($cards as $cardname) { 
      $maindeckStr .= $this->maindeck_cards[$cardname] . $cardname;
    }
    $this->deck_hash = sha1($maindeckStr);
    $sideboardStr = "";
    $cards = array_keys($this->sideboard_cards);
    sort($cards, SORT_STRING);
    foreach ($cards as $cardname) { 
      $sideboardStr .= $this->sideboard_cards[$cardname] . $cardname;
    }
    $this->sideboard_hash = sha1($sideboardStr);
    $this->whole_hash = sha1($maindeckStr . "<sb>" . $sideboardStr); 
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE decks SET sideboard_hash = ?, deck_hash = ?, whole_hash = ? where id = ?");
    $stmt->bind_param("sssd", $this->sideboard_hash, $this->deck_hash, $this->whole_hash, $this->id);
    $stmt->execute();
    $stmt->close();
  }

  static function uniqueCount() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT count(deck_hash) FROM decks GROUP BY deck_hash");
    $stmt->execute(); 
    $stmt->store_result();
    $uniquecount = $stmt->num_rows;
    $stmt->close(); 
    return $uniquecount; 
  }

  function linkTo() {
    if ($this->new) {
      return "Deck not found";
    } else {
      if (empty($this->name)) { $this->name = "** NO NAME **"; }
      return "<a href=\"deck.php?mode=view&id={$this->id}\">{$this->name}</a>";
    }
  }

}

