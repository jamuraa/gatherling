<?php 

class Database {

  function getConnection() { 
    static $instance; 

    if (!isset($instance)) { 
      $instance = new mysqli('localhost', 'pdcmagic', 'pdcm4g1crul3s', 'pdcmagic_gath');
    } 

    return $instance;
  } 
} 
