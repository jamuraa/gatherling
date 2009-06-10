<?php 

class Database {

  function getConnection() { 
    static $instance; 

    if (!isset($instance)) { 
      $instance = new mysqli('hostname', 'username', 'password', 'database');
    } 

    return $instance;
  } 
} 
