<?php
include_once 'lib.php';
$host = 0;
$super = 0;
if(isset($_SESSION['username'])) {
	$db = dbcon();
	$query = "SELECT host, super FROM players 
		WHERE name=\"{$_SESSION['username']}\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_assoc($result);
	$host = $row['host'];
	$super = $row['super'];
	mysql_free_result($result);
	mysql_close($db);
}
?>

<div class="indentmenu2">
<ul>
<li><a href="index.php">Home</a></li>
<li><a href="profile.php">Profile</a></li>
<li><a href="player.php">Player CP</a></li>
<li><a href="eventreport.php">Metagame</a></li>
<li><a href="decksearch.php">Decks</a></li>

<?php if($host || $super) { ?>
<li><a href="event.php">Host CP</a></li>
<?php } ?>

<?php if($super) { ?>
<li><a href="index.php">Admin CP*</a></li>
<?php } ?>

<li><a href="index.php">FAQ*</a></li>
<li><a href="index.php">Report Error*</a></li>

<?php if(!isset($_SESSION['username'])) { ?>
<li><a href="login.php">Login</a></li>
<?php } else { ?>
<li><a href="logout.php">Logout [<?php print $_SESSION['username'];?>]</a></li>
<?php } ?>

</ul>
<br style="clear: left" />
</div>
