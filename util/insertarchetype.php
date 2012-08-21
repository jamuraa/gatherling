<?php
require_once('../lib.php');
$db = dbcon();

$arch = "Gnarlly Beats";
$filename = "archetypes/$arch.txt";
$file = fopen($filename, "r");
$contents = fread($file, filesize($filename));
$lines = explode("", $contents);
$query = "DELETE FROM typeinfo WHERE decktype=\"$arch\"";
mysql_query($query) or die(mysql_error());
for($i = 0; $i < sizeof($lines); $i++) {
  $tok = explode("\t", $lines[$i]);
  $name = $tok[0]; $str = chop($tok[1]);

  $query = "INSERT INTO typeinfo(decktype, strength, card)
    SELECT \"$arch\", \"$str\", id FROM cards WHERE name=\"$name\"";
  mysql_query($query, $db) or die(mysql_error());
  if(mysql_affected_rows() == 0) {
    printf("%s<br />", $lines[$i]);
  }
}

