<?php
require_once('../lib.php');
$db = dbcon();
header("Content-type: text/plain");

$query = "SELECT name FROM events WHERE start < NOW() ORDER BY series, season DESC, number DESC, name";
$result = mysql_query($query, $db) or die(mysql_error());

$n = 0;
while($row = mysql_fetch_assoc($result)) {
  printf("[trophy]%s[/trophy]", $row['name']);
  $n++;
  if($n%3 == 0) {echo "\n";}
}
?>
