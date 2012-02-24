<?php 

class Database {

  static function getConnection() { 
    static $instance;

    if (!isset($instance)) {
      global $CONFIG; 
      $instance = new mysqli($CONFIG['db_hostname'], $CONFIG['db_username'],
                             $CONFIG['db_password'], $CONFIG['db_database']);
    }

    return $instance;
  }

  static function getPDOConnection() {
    static $pdo_instance;

    if (!isset($pdo_instance)) {
      global $CONFIG;
      $pdo_instance = new PDO('mysql:hostname=' . $CONFIG['db_hostname'] . ';dbname=' . $CONFIG['db_database'],
                              $CONFIG['db_username'], $CONFIG['db_password']);
    }

    return $pdo_instance;
  }
}
