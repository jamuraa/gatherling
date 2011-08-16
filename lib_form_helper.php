<?php

function print_text_input($label, $name, $value = "") {
  echo "<tr><th>{$label}</th><td><input type=\"text\" name=\"{$name}\" value=\"{$value}\" /> </td></tr>";
}

function print_submit($label, $name = "action") {
  echo "<tr><td colspan=\"2\" class=\"buttons\"><input type=\"submit\" name=\"{$name}\" value=\"{$label}\" /></td></tr>";
}

