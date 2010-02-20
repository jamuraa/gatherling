<?php include 'lib.php';
session_start();
print_header("PDCMagic.com | Gatherling | Home");
?>
<div class="grid_10 prefix_1 suffix_1"> 
<div id="gatherling_main" class="box"> 
<div class="alpha omega grid_10 uppertitle">Gatherling</div> 
<div class="clear"></div>
<p>Welcome to Gatherling!  This is an application where you can keep track of 
your decks in order to see what you played last tourney, last month, or even 
last year.  You can keep track of all of your decks which are played in PDCMagic.com
tournaments here, and your ratings for Pauper Magic will also be calculated.</p>
<p>
<div class="alpha grid_5"> 
<b>Some good starting points:</b>
<ul> 
<li> <a href="eventreport.php"> See a list of recent events </a> </li> 
<li> <a href="decksearch.php"> Search for decks with a certain card </a> </li> 
<ul> 
<b>Random statistics about Gatherling:</b> 
<ul> 
<li> There are <?php echo Deck::uniqueCount() ?> unique decks. </li>
<li> We have recorded <?php echo Match::count() ?> matches from <?php echo Event::count() ?> events.</li> 
<li> There are <?php echo Player::activeCount() ?> active players in gatherling. </li>
</ul>
</div>
<div class="grid_5 omega">
<?php $player = Player::getSessionPlayer(); ?>
<?php if ($player != NULL): ?>
<b> Welcome back <?php echo $player->name ?> </b>
<ul> 
<li> <a href="profile.php">Check out your profile</a> </li>
<li> <a href="player.php?mode=alldecks">Enter your own decklists</a> </li> 
<?php $event = Event::findMostRecentByHost($player->name);
if (!is_null($event)) { ?>
<li> <a href="event.php?name=<?php echo $event->name ?>">Manage <?php echo $event->name ?></a> </li>
<?php } ?> 
<?php if ($player->host) { ?>
<li> <a href="event.php">Host Control Panel</a> </li>
<?php } ?> 
</ul>
<?php else: ?> 
<center> <b> Login to Gatherling </b> </center>
<form action="login.php" method="post">
  <table class="form" align="center" style="border-width: 0px" cellpadding="3">
    <tr>
      <th>MTGO Username</th>
      <td><input type="text" name="username" value="" /></td>
    </tr>
    <tr>
      <th>Gatherling Password</td>
      <td><input type="password" name="password" value="" /></td>
    </tr>
    <tr> 
      <td colspan="2" class="buttons">
        <input type="submit" name="mode" value="Log In" /> <br />
        <a href="register.php">Need to register?</a>
      </td>
    </tr>
  </table>
</form> 
<?php endif; ?>
</div> <!-- grid_5 omega (login/links) -->

<div class="clear"></div>
</div> <!-- gatherlingmain box -->

<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Latest Gatherling News </div>
<div class="clear"></div>
 <p> 
  <b> 2010-02-19 </b> - 
  This week we have some bugfixes for validation, which means that more of you will be able to validate with MTGO!  Also, I've added the tracking of season points to the system, so event organizers can track the season points within gatherling!  It was actually quite a big change.  It's the big 2.0.0, but there's not too much for the player (non-organizer).
  <ul> 
    <li> Fixed bug with validation not working if the capitalization was different (it doesn't matter on MTGO and doesn't matter here). </li> 
    <li> Added Series Reports, where you can see the running points of any of the series.  Don't be surprised if the points are all 0 for the first few days/weeks, orgnaizers still need to put in what people get points for. </li> 
    <li> The <a href="ratings.php">ratings page</a> now has checkmarks for validated users just like everywhere else. </li>
    <li> Added some little statistics about how many players and decks we have (just above this news!) </li>
  </ul>
 </p>
 <p> 
  <b> 2010-02-12 </b> - 
  Mostly bugfixes this week, and improvements to management interfaces.  We're getting close to v2.0.0, just because we're running out of numbers.  Thinking of adding some nifty-ness to the system next week just because it seems like I should.   I can't imagine it will be anything life-changing though.  Version numbers are meaningless in the end.
  <ul> 
    <li> Fixed bug with metagame report where it would not generate if a winner hadn't been declared. </li> 
    <li> Fixed bug where deck identities weren't being calculated (making duplicate lists not show up) </li> 
  </ul>
 </p>
<br /> 
<div class="clear"></div>
</div> <!-- box gatherlingnews -->
</div> <!-- grid 10 pre 1 suff 1-->

<?php print_footer(); ?> 

</div>  <!-- container -->
</body>
</html>
