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

  function authCheck($playername) { 
    if ($this->isSteward($playername)) { 
      return true; 
    }
    $player = new Player($playername); 
    return $player->isSuper(); 
  } 

  function getEvents() { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT name FROM events WHERE series = ? ORDER BY timing"); 
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
}
