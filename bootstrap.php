<?php

function __autoload($class_name) { 
  require_once 'models/' . $class_name . '.php'; 
} 

