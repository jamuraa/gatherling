<div id="rightcolumn">
<div class="innertube" align=center>
<br><h4>GATHERLING NAVIGATION</h4><br>
<?php if(isset($_SESSION['username'])) { ?>

You are logged in as: <?php print $_SESSION['username'];?><br>
<a href="logout.php">Log Out</a><br><br>
<a href="event.php">Events</a><br><br>
<a href="format.php">Formats</a><br><br>
<a href="ratings.php">Ratings</a><br><br>
<a href="player.php">Player</a><br><br>

<?php } else { ?>

<a href="login.php">Log In</a><br><br>

<?php } ?>
</div>
</div>
