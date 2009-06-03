<?php

function __autoload($class_name) { 
  require_once 'models/' . $class_name . '.php'; 
} 

// Fix for MAGIC_QUOTES_GPC

if (version_compare(phpversion(), 6) === -1) {
  if (get_magic_quotes_gpc()) {
    function stripinputslashes(&$input) {
      if (is_array($input)) {
        foreach ($input as $key => $value) {
          $input[$key] = stripinputslashes($value);
        }
      }
      else {
        $input = stripslashes($input);
      }
      return true;
    }
    array_walk_recursive($_GET, 'stripinputslashes');
    array_walk_recursive($_POST, 'stripinputslashes');
    array_walk_recursive($_COOKIE, 'stripinputslashes');
  }
}


