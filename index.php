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
<p>Some good starting points:
<ul> 
<li> <a href="player.php?mode=alldecks"> Enter your own decklists </a> </li> 
<li> <a href="eventreport.php"> See a list of recent events </a> </li> 
<li> <a href="decksearch.php"> Search for decks with a certain card </a> </li> 
<li> <a href="profile.php"> Check out your profile </a> </li>
<ul> 
</p>

</div> <!-- gatherlingmain box -->

<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Recent Gatherling News </div>
<div class="clear"></div>
<p>
  <b> 2009-09-14 </b> - 
  Welcome to the new intro page!  It will have news.  This update includes: 
  <ul> 
   <li> Deck comments have returned!  Fill out the comment form when entering your deck. </li> 
   <li> New layout to match the rest of the new site. </li> 
   <li> Easier event entry for recurring events for hosts. </li> 
   <li> Other admin features (super! and secret!) </li>
  </ul>
</p> 
</div> <!-- box gatherlingnews -->
</div> <!-- grid 10 pre 1 suff 1-->

<? print_footer(); ?> 

</div>  <!-- container -->
</body>
</html>
