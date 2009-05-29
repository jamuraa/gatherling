<?php
require_once('lib.php');
$db = dbcon();
if(isset($_GET['id'])) {$id = $_GET['id'];}
if(isset($_POST['id'])) {$id = $_POST['id'];}
$query = "SELECT dc.qty, c.name, d.name AS deckname, dc.issideboard 
	FROM deckcontents dc, cards c, decks d
	WHERE dc.deck=$id AND c.id=dc.card AND d.id=dc.deck
	ORDER BY dc.issideboard ASC, c.name";
$result = mysql_query($query, $db) or die(mysql_error());

$encsideboard = false;
$content = "";
while($row = mysql_fetch_assoc($result)) {
	if($row['issideboard'] == 1 && !$encsideboard) {
		$encsideboard = true;
		$content .= "\nSideboard\n";
	}
    $content .= ($row['qty'] . " " . $row['name'] . "\n");
	$deckname = $row['deckname'];
}
mysql_free_result($result);
mysql_close($db);

$filename = preg_replace("/ /", "_", $deckname) . ".txt";
header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=$filename");
echo $content;
exit;
?>

