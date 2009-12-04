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
  <table align="center" style="border-width: 0px" cellpadding="3">
    <tr>
      <td><b>MTGO Username</b></td>
      <td><input type="text" name="username" value="" /></td>
    </tr>
    <tr>
      <td><b>Gatherling Password</b></td>
      <td><input type="password" name="password" value="" /></td>
    </tr>
    <tr> 
      <td colspan="2">
        <center>
        <input type="submit" name="mode" value="Log In" /> <br />
        <a href="register.php">Need to register?</a>
        </center>
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
  <b> 2009-12-04 </b> -
  I took a week off because of the thanksgiving holiday, but here is the next iteration!  Next week I expect to take off as well, but the week after we should have another iteration.
  <ul> 
    <li> Deck search by card name! You can search by card name, deck name, and any combination.  Card names must be exact, deck names are still a partial string search</li>
    <li> Hosts will see a couple new links on the landing page for convenience </li> 
    <li> Fixed a bug which would cause ugly error messages when dealing with decks and getting logged out </li>
    <li> Ratings page now displays the date of the last event ratings were calculated for, in addition to the event name </li>
  </ul>
</p>
<p>
  <b> 2009-11-19 </b> - 
  Welcome to the second update in the new release cycle.  This week we have just a couple of things 
  which are visible to the average player, but one of them has been waiting a while.
  <ul> 
   <li> Metagame reports will now tell you the number of decks and the percentage of decks reported. </li>
   <li> Players can now ignore decks that they can't remember or recover.  Click the checkbox next to the deck on the page with all of your decks.
        If you end up with zero unignored decks, the large annoying reminder will go away on the main Player CP.</li>
   <li> New gatherling landing page layout with login form for faster login! </li> 
   <li> A couple things for hosts. </li>
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
