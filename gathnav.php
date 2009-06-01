<?php

require_once 'lib.php';

$player = Player::getSessionPlayer();

$host = false;
$super = false;

if ($player != NULL) {
  $host = $player->isHost();
  $super = $player->isSuper();
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

<?php if($player == NULL) { ?>
<li><a href="login.php">Login</a></li>
<?php } else { ?>
<li><a href="logout.php">Logout [<?php print $player->name; ?>]</a></li>
<?php } ?>

</ul>
<br style="clear: left" />
</div>
