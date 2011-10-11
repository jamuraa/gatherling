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
    $decknamesearch = "%" . $db->escape_string($_GET['deck']) . "%";
    $cardsearch_wild = "%" . $db->escape_string($_GET['card']) . "%";
    // TODO: I need a better way of doing this
    if (empty($_GET['card']) && !empty($_GET['deck'])) {
      $stmt = $db->prepare("SELECT id FROM decks WHERE name LIKE ? LIMIT 20");
      $stmt->bind_param("s", $decknamesearch);
    } else if (!empty($_GET['card']) && !empty($_GET['deck'])) { 
      $stmt = $db->prepare("SELECT id FROM decks WHERE name LIKE ? AND deck_contents_cache LIKE ? LIMIT 20");
      $stmt->bind_param("ss", $decknamesearch, $cardsearch);
    } else if (!empty($_GET['card']) && empty($_GET['deck'])) {
      $stmt = $db->prepare("SELECT id FROM decks WHERE deck_contents_cache LIKE ? LIMIT 20");
      $stmt->bind_param("s", $cardsearch_wild);
    }

    $stmt->execute(); 
    $stmt->store_result();
    $stmt->bind_result($id);

    $search_desc = "";
    if (!empty($_GET['card'])) {
      $search_desc .= " with {$_GET['card']} in them";
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
      $deck_ids = array();
      while($stmt->fetch()) {
        $deck_ids[] = $id;
      }
      $stmt->close(); 
      echo "<table align=\"center\" style=\"border-width: 0px;\" cellpadding=3>";
      echo "<tr><th>Deck Name</th><th>Played by</th><th>Event</th> </tr>";
      foreach ($deck_ids as $deck_id) {
        $deck = new Deck($deck_id);
        echo "<tr><td><img src=\"/images/{$deck->medal}.gif\">\n";
        echo $deck->linkTo();
        echo "</td>";
        if ($deck->playername != NULL) {
          $aplay = new Player($deck->playername);
          echo "<td>{$aplay->linkTo()}</td>";
        } else {
          echo "<td>???</td>";
        }
        if ($deck->eventname != NULL) {
          echo "<td>{$deck->eventname}</td>";
        } else {
          echo "<td>???</td>";
        }
        echo "</tr>\n";
      }
      echo "</table>";
    }
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
    $stmt = $db->prepare("SELECT count(d.deck_hash) as cnt, d.name, d.id FROM decks d, entries n where n.deck = d.id AND 5 < (SELECT count(*) from deckcontents where deck = d.id group by deck) group by d.deck_hash order by cnt desc limit 20");
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
