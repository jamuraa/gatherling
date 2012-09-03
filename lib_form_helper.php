<?php

require_once 'lib.php';

function print_text_input($label, $name, $value = "") {
  echo "<tr><th>{$label}</th><td><input type=\"text\" name=\"{$name}\" value=\"{$value}\" /> </td></tr>";
}

function print_password_input($label, $name, $value = "") {
  echo "<tr><th>{$label}</th><td><input type=\"password\" name=\"{$name}\" value=\"{$value}\" /> </td></tr>";
}

function print_file_input($label, $name) {
  echo "<tr><th>{$label}</th><td>";
  echo "<input type=\"file\" name=\"{$name}\" /></td></tr>";
}

function print_submit($label, $name = "action") {
  echo "<tr><td colspan=\"2\" class=\"buttons\"><input type=\"submit\" name=\"{$name}\" value=\"{$label}\" /></td></tr>";
}

function print_select($name, $options = array(), $selected = NULL) {
  echo "<select name=\"{$name}\">";
  if (!is_assoc($options)) {
    $new_options = array();
    foreach ($options as $option) {
      $new_options[$option] = $option;
    }
  }
  foreach ($options as $option => $text) {
    $setxt = "";
    if (!is_null($selected) && $selected == $option) {
      $setxt = " selected";
    }
    echo "<option value=\"{$option}\"{$setxt}>{$text}</option>";
  }
  echo "</select>";
}

function print_select_input($label, $name, $options, $selected = NULL) {
  echo "<tr><th>{$label}</th><td>";
  print_select($name, $options, $selected);
  echo "</td></tr>";
}


