<?php

class Subevent { 
  public $parent; 
  public $rounds; 
  public $timing; 
  public $type; 
  public $id; 

  function __construct($id) { 
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT parent, rounds, timing, type 
      FROM subevents WHERE id = ?");
    $stmt->bind_param("d", $id); 
    $stmt->execute(); 
    $stmt->bind_result($this->parent, $this->rounds, $this->timing, $this->type); 
    if ($stmt->fetch()) { 
      $this->id = $id; 
    } else { 
      throw new Exception("Can't instantiate subevent with id $id");
    } 
  } 
} 

