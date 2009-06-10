<?php session_start(); ?> 
<?php include 'lib.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Basic Deck Search</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Basic Deck Search</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>Basic Deck Search</h1></td>
</tr><tr><td bgcolor=white><br>

<?php content(); ?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3><?php version_tagline(); ?></h3>
</td></tr></table></div>
<br><br></div></div>
<?php include '../footer.ssi';?>


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
    echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\">";
    echo "Enter a deck name. You may use % as a wildcard.<br><br>";
    echo "<input type=\"text\" name=\"deck\">";
    echo "<input type=\"submit\" name=\"mode\" value=\"Gimme some decks!\">";
    echo "</form>";
  }
}
?>
