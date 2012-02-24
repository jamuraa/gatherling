<?php include 'lib.php';
session_start();
print_header("Home");
?>
<div class="grid_10 prefix_1 suffix_1">
<div id="gatherling_main" class="box">
<div class="alpha omega grid_10 uppertitle">Gatherling</div>
<div class="clear"></div>
<p>Welcome to Gatherling!  This is an application where you can keep track of
your decks in order to see what you played last tourney, last month, or even
last year.  You can keep track of all of your decks which are played in 
<?php echo $CONFIG['site_name']; ?>
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
<li> There are <?php echo Player::activeCount() ?> active players in gatherling. (<?php echo Player::verifiedCount() ?> verified) </li>
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
<?php if ($player->isHost()) { ?>
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
  <b> 2011-10-10 </b> -
  If you thought that the last time between updates was long, I have news for you.  At any rate, I've been given some time to get some stuff done again and I have been able to fix some bugs and provide some new functionality for everyone.
  <ul>
    <li> You can now pre-register for events if the event host allows it.  More features to be added soon related to this. </li>
    <li> Deck search is re-enabled now, so you can find popular decks and decks with specific cards. </li>
    <li> Event hosts will find some fancy UI improvements in the back end. </li>
    <li> You can't make a deck without a name anymore. </li>
    <li> A bunch of backend bugfixes and improvements. </li>
   </ul>
 </p>
 <p>
  <b> 2010-07-30 </b> -
  Wow, long time with no update.  Backend improvements mostly, and bugfixes for everyone.
  <ul>
    <li> Super new players now can actually do stuff before they have a match. </li>
    <li> Standard events show up with a format correctly now on the events page. </li>
    <li> Events page is standard throughout the site pointing to gatherling now. </li>
    <li> Should be many less issues with players and capitalization of names. </li>
    <li> "Rogue" as a decktype has been renamed to "Unclassified" to more accurately reflect reality. </li>
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
