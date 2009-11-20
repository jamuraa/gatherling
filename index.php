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
<center> <b> Welcome back <?php echo $player->name ?> </b></center>
<ul> 
<li> <a href="profile.php"> Check out your profile </a> </li>
<li> <a href="player.php?mode=alldecks"> Enter your own decklists </a> </li> 
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
<div class="uppertitle alpha omega grid_10"> Recent Gatherling News </div>
<div class="clear"></div>
<p>
  <b> 2009-11-12 </b> - 
  It's been a while since an update, but I have an update for you all.  Okay well it's mostly 
  bugfixes for event people.  The hope is to have weeklyish updates from now on with incremental
  improvements, and major updates every couple months.
  <ul> 
   <li> New deck layout which is more compact in the top. </li>
   <li> New deck layout now lists format that the deck was played in. </li>
   <li> Easier trophies assignment for hosts. </li> 
   <li> Some more backend stuff </li>
  </ul>
</p> 
</div> <!-- box gatherlingnews -->
</div> <!-- grid 10 pre 1 suff 1-->

<? print_footer(); ?> 

</div>  <!-- container -->
</body>
</html>
