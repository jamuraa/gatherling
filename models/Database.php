<?php 

class Database {

  function getConnection() { 
    static $instance;

    if (!isset($instance)) { 
      global $CONFIG; 
      $instance = new mysqli($CONFIG['db_hostname'], $CONFIG['db_username'],
                             $CONFIG['db_password'], $CONFIG['db_database']);
    } 

    return $instance;
  } 
} 
