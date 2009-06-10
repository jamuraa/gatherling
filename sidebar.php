<?php include_once 'lib.php' ?>
<div id="rightcolumn">
<div class="innertube" align=center><br>
<h4>UPCOMING EVENTS</h4>
<a href="http://forums.gleemax.com/forumdisplay.php?f=590" target="_blank">Player-Run Events at Gleemax</a><BR><br>
<?php futureTable();?>
<br>
<h4>RECENT WINNERS</h4>
<a href="/gatherling/eventreport.php">Gatherling Metagame Reports</a><BR><br>
<?php recentTable();?>
</div>
</div>

<?php
function futureTable() {
  $db = Database::getConnection();
  $result = $db->query("SELECT UNIX_TIMESTAMP(DATE_SUB(start, INTERVAL 30 MINUTE)) AS d, 
    format, series, name, threadurl FROM events
    WHERE start>NOW() ORDER BY start ASC LIMIT 5");
  $result or die($db->error);
  echo "<table align=\"center\" width=\"90%\">\n";
  while($row = $result->fetch_assoc()) {
    $dateStr = date('D j M', $row['d']);
    $timeStr = date("g:i A", $row['d']);
    $name = $row['name'];
    $series = $row['series'];
    $threadurl = $row['threadurl'];
    $format = $row['format'];
    $col2 = $name;
    if(strcmp($threadurl, "") != 0) {
      $col2 = "<a href=\"$threadurl\">" . $name . "</a>";}
    echo "<tr><td>$dateStr</td>\n";
    echo "<td>$col2<br>$format</td>\n";
    echo "<td>$timeStr</td><tr>\n";
  }
  echo "<tr><td colspan=\"3\" align=\"center\"><i>";
  echo "All times are EST.</td></tr>\n";
  echo "</table>";
  $result->close();
}

function recentTable() {
  $db = Database::getConnection();
  $result = $db->query("SELECT b.event, b.player, d.name 
    FROM entries b, decks d, events e 
    WHERE b.medal='1st' AND d.id=b.deck AND e.name=b.event
    ORDER BY e.start DESC LIMIT 3");
  $result or die($db->error);
  echo "<table align=\"center\" width=\"90%\">\n";
  while($row = $result->fetch_assoc()) {
    $query = "SELECT COUNT(*) AS c FROM trophies 
      WHERE event=\"{$row['event']}\"";
    $res2 = $db->query($query) or die($db->error);
    $row2 = $res2->fetch_assoc();
    if($row2['c'] > 0) {
      echo "<tr><td colspan=\"3\" align=\"center\">";
      echo "<a href=\"/gatherling/deck.php?mode=view&";
      echo "event={$row['event']}\">";
      echo "<img src=\"/gatherling/displayTrophy.php?";
      echo "event={$row['event']}\" style=\"border-width: 0px;\"></a>";
      echo "</td></tr>\n";
    }
    echo "<tr><td><b><a href=\"/gatherling/profile.php?player=";
    echo "{$row['player']}\">{$row['player']}</a></td>";
    echo "<td><i><a href=\"/gatherling/deck.php?";
    echo "mode=view&event={$row['event']}\">{$row['name']}</a></td>";
    echo "<td><a href=\"/gatherling/eventreport.php?event={$row['event']}\">{$row['event']}</a></td></tr>\n";
  }
  echo "</table>";
  $result->close();
}
?>
