<?php session_start();
include 'lib.php';

print_header("PDCMagic.com | Gatherling | Basic Deck Search");
?> 
<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Basic Deck Search </div>

<?php content(); ?>

</div> 
</div>

<?php print_footer();?>

<?php // ------ Search Starts here ------
function content() {
  if(isset($_POST['mode'])) {
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT SUM(dc.qty) AS q, d.id, d.name, n.player, n.event, n.medal 
		  FROM decks d, entries n, deckcontents dc, events e  
      WHERE d.name LIKE ? AND n.deck=d.id 
      AND dc.deck=d.id AND dc.issideboard=0
      AND n.event=e.name
      GROUP BY dc.deck
      HAVING q>=60
      ORDER BY e.start DESC, n.medal");
    $decknamesearch = "%" . $_POST['deck'] . "%";
    $stmt->bind_param("s", $decknamesearch);
    $stmt->execute(); 
    $stmt->bind_result($qty, $id, $name, $player, $event, $medal);
    echo "<table align=\"center\" style=\"border-width: 0px;\" cellpadding=3>";
    while($stmt->fetch()) {
      echo "<tr><td><a href=\"deck.php?mode=view&id={$id}\">";
      echo "{$name}</a></td>";
      echo "<td><img src=\"/images/{$medal}.gif\"></td>\n";
      echo "<td>{$player}</td>";
      echo "<td>{$event}";
      echo "</td></tr>\n";
    }
    $stmt->close(); 
    echo "</table>";
  } else {
    echo "<table><tr><td><form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\">";
    echo "Deck name contains: ";
    echo "<input type=\"text\" name=\"deck\">";
    echo "<input type=\"submit\" name=\"mode\" value=\"Gimme some decks!\">";
    echo "</form></td></tr></table>";
  }
}
?>
