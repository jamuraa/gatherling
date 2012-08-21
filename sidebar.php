<?php include_once 'lib.php' ?>
<div class="box">
<h4>UPCOMING EVENTS</h4>
<a href="http://forums.gleemax.com/forumdisplay.php?f=590" target="_blank">Player-Run Events in the Magic Community</a><br /><br />
<?php futureTable();?>
</div>
<div class="box">
<h4>RECENT WINNERS</h4>
<?php recentTable();?>
</div>
<div class="box">
<h4>MORE RECENT WINNERS</h4>
<a href="./eventreport.php">Gatherling Metagame Reports</a><br /><br />
</div>
<?php
function futureTable() {
  $db = Database::getConnection();
  $result = $db->query("SELECT UNIX_TIMESTAMP(DATE_SUB(start, INTERVAL 30 MINUTE)) AS d,
    format, series, name, threadurl FROM events
    WHERE start > NOW() ORDER BY start ASC LIMIT 10");
  $result or die($db->error);
  while($row = $result->fetch_assoc()) {
    $dateStr = date('D j M', $row['d']);
    $timeStr = date("g:i A", $row['d']);
    $name = $row['name'];
    $series = $row['series'];
    $threadurl = $row['threadurl'];
    $format = $row['format'];
    $col2 = $name;
    echo "<table class=\"center\">\n";
    if(strcmp($threadurl, "") != 0) {
      $col2 = "<a href=\"$threadurl\">" . $name . "</a>";}
    echo "<tr><td width=60>$dateStr</td>\n";
    echo "<td width=100>$col2<br />$format</td>\n";
    echo "<td width=50>$timeStr</td></tr></table>\n";
  }
  echo "<table class=\"center\">\n";
  echo "<tr><td colspan=\"3\" align=\"center\"><i>All times are EST.</i></td></tr>\n";
  echo "</table>";
  $result->close();
}

function recentTable() {
  $db = Database::getConnection();
  $result = $db->query("SELECT b.event, b.player, d.name
    FROM entries b, decks d, events e
    WHERE b.medal='1st' AND d.id=b.deck AND e.name=b.event
    ORDER BY e.start DESC LIMIT 10");
  $result or die($db->error);
  while($row = $result->fetch_assoc()) {
    $query = "SELECT COUNT(*) AS c FROM trophies
      WHERE event=\"{$row['event']}\"";
    $res2 = $db->query($query) or die($db->error);
    $row2 = $res2->fetch_assoc();
    echo "<div class=\"newtrophies\">";
    echo "<table class=\"center\">\n";
    if($row2['c'] > 0) {
      echo "<tr><td colspan=\"3\" align=\"center\">";
      echo "<a class=\"borderless\" href=\"./eventreport.php?event={$row['event']}\">";
      echo "<img src=\"./displayTrophy.php?event={$row['event']}\" alt=\"Event Trophy\" style=\"border-width: 0px;\" />";
      echo "</a></td></tr>\n";
    }
    echo "<tr><td align=\"center\" width=\"160\"><b><a href=\"./profile.php?player={$row['player']}\">{$row['player']}</a></b></td>";
    echo "<td align=\"center\" width=\"160\"><i><a href=\"./deck.php?mode=view&event={$row['event']}\">{$row['name']}</a></i></td></tr>";
    echo "</table>";
    echo "</div>";
  }
  $result->close();
}
?>
