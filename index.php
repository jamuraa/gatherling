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
</div>
<div class="grid_5 omega">
<? $player = Player::getSessionPlayer(); ?>
<? if ($player != NULL): ?>
<b> Welcome back <?php echo $player->name ?> </b>
<ul> 
<li> <a href="profile.php">Check out your profile</a> </li>
<li> <a href="player.php?mode=alldecks">Enter your own decklists</a> </li> 
<? $event = Event::findMostRecentByHost($player->name);
if (!is_null($event)) { ?>
<li> <a href="event.php?name=<? echo $event->name ?>">Manage <? echo $event->name ?></a> </li>
<? } ?> 
<? if ($player->host) { ?>
<li> <a href="event.php">Host Control Panel</a> </li>
<? } ?> 
</ul>
<? else: ?> 
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
<? endif; ?>
</div> <!-- grid_5 omega (login/links) -->

<div class="clear"></div>
</div> <!-- gatherlingmain box -->

<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Latest Gatherling News </div>
<div class="clear"></div>
<p> 
  <b> 2010-01-14 </b> - 
  Updates are back!  We have a bunch of little things this week, but again, bigger stuff up ahead.
  <ul> 
    <li> Deck search now is limited to 20 decks for performance reasons </li>
    <li> Users can change their own password now, from the Player CP </li> 
    <li> Forms look slightly nicer now </li> 
    <li> Other random bugs that were fixed </li>
  </ul>
</p>
<p> 
  <b> 2009-12-04 </b> -
  I took a week off because of the thanksgiving holiday, but here is the next iteration!  Next week I expect to take off as well, but the week after we should have another iteration.
  <ul> 
    <li> Deck search by card name! You can search by card name, deck name, and any combination.  Card names must be exact, deck names are still a partial string search</li>
    <li> Hosts will see a couple new links on the landing page for convenience </li> 
    <li> Fixed a bug which would cause ugly error messages when dealing with decks and getting logged out </li>
    <li> Ratings page now displays the date of the last event ratings were calculated for, in addition to the event name </li>
  </ul>
</p>
<br /> 
<div class="clear"></div>
</div> <!-- box gatherlingnews -->
</div> <!-- grid 10 pre 1 suff 1-->

<? print_footer(); ?> 

</div>  <!-- container -->
</body>
</html>
