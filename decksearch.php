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
  if(!empty($_GET['deck']) || !empty($_GET['card'])) {
    $db = Database::getConnection(); 
    $decknamesearch = "%" . $_GET['deck'] . "%";
    $cardsearch = $_GET['card'];
    // TODO: I need a better way of doing this
    if (empty($_GET['card']) && !empty($_GET['deck'])) {
      $stmt = $db->prepare("SELECT d.id, d.name, n.player, n.event, n.medal 
        FROM decks d, entries n, deckcontents dc, events e, cards c 
        WHERE d.name LIKE ? AND n.deck=d.id 
        AND dc.deck=d.id AND dc.issideboard=0
        AND n.event=e.name
        GROUP BY dc.deck
        ORDER BY e.start DESC, n.medal LIMIT 20");
      $stmt->bind_param("s", $decknamesearch);
    } else if (!empty($_GET['card']) && !empty($_GET['deck'])) { 
      $stmt = $db->prepare("SELECT d.id, d.name, n.player, n.event, n.medal 
        FROM decks d, entries n, deckcontents dc, events e, cards c 
        WHERE d.name LIKE ? AND n.deck=d.id 
        AND dc.deck=d.id AND dc.issideboard=0
        AND n.event=e.name
        AND dc.card=c.id AND c.name = ?
        GROUP BY dc.deck
        ORDER BY e.start DESC, n.medal LIMIT 20");
      $stmt->bind_param("ss", $decknamesearch, $cardsearch);
    } else if (!empty($_GET['card']) && empty($_GET['deck'])) {
      $stmt = $db->prepare("SELECT d.id, d.name, n.player, n.event, n.medal
        FROM decks d, entries n, deckcontents dc, events e, cards c
        WHERE n.deck=d.id
        AND dc.deck=d.id AND dc.issideboard=0
        AND n.event=e.name
        AND dc.card=c.id AND c.name = ?
        GROUP BY dc.deck
        ORDER BY e.start DESC, n.medal LIMIT 20");
      $stmt->bind_param("s", $cardsearch);
    }

    $stmt->execute(); 
    $stmt->store_result();
    $stmt->bind_result($id, $name, $player, $event, $medal);

    $search_desc = "";
    if (!empty($_GET['card'])) {
      $search_desc .= " with {$cardsearch} in them";
    } 
    if (!empty($_GET['deck'])) {
      if (!empty($search_desc)) { 
        $search_desc .= " AND "; 
      }
      $search_desc .= " with '{$_GET['deck']}' in the deck name"; 
    } 

    if ($stmt->num_rows() == 0) { 
      echo "<center>No decks {$search_desc}! Try again!</center>\n";
    } else {
      if ($stmt->num_rows() == 20) { 
        echo "<center>More than 20 decks {$search_desc}</center>\n";
      } else {
        echo "<center>{$stmt->num_rows()} decks {$search_desc}</center>\n";
      }
      echo "<table align=\"center\" style=\"border-width: 0px;\" cellpadding=3>";
      echo "<tr><th>Deck Name</th><th>Played by</th><th>Event</th> </tr>";
      while($stmt->fetch()) {
        echo "<tr><td><img src=\"/images/{$medal}.gif\">\n";
        echo "<a href=\"deck.php?mode=view&id={$id}\">";
        if (empty($name)) {
          $name = "** NO NAME **";
        } 
        echo "{$name}</a></td>";
        $aplay = new Player($player);
        echo "<td>{$aplay->linkTo()}</td>";
        echo "<td>{$event}";
        echo "</td></tr>\n";
      }
      echo "</table>";
    }
    $stmt->close(); 
  } else {
    echo "<form method=\"get\" action=\"{$_SERVER['REQUEST_URI']}\"><table class=\"form\">";
    echo "<tr><th>Deck name contains</th> <td>";
    echo "<input type=\"text\" name=\"deck\"></td></tr>";
    echo "<tr><th>Deck contains card</th><td>"; 
    echo "<input type=\"text\" name=\"card\"></td></tr>";
    echo "<tr><td colspan=2 class=\"buttons\">";
    echo "<input type=\"submit\" value=\"Gimme some decks!\"></td></tr>";
    echo "</table></form>";
    echo "<table><tr><th colspan=2><b>MOST PLAYED DECKS</b></th></tr>";
    echo "<tr><th>Deck Name</th><th>Played</th></tr>";
    $db = Database::getConnection(); 
    $stmt = $db->prepare("SELECT count(d.deck_hash) as cnt, d.name, d.id FROM decks d WHERE 5 < (SELECT count(*) from deckcontents where deck = d.id group by deck) group by d.deck_hash order by cnt desc limit 20");
    $stmt->execute(); 
    $stmt->bind_result($count, $name, $deckid); 
    while ($stmt->fetch()) { 
      echo "<tr><td><a href=\"deck.php?mode=view&id={$deckid}\">{$name}</a></td>";
      echo "<td>{$count} times</td></tr>";
    } 
    echo "</table>";
  }
}
?>
