<?php include 'lib.php';
session_start();
print_header("PauperKrew.com | Gatherling | Home");
?>
<div class="grid_10 prefix_1 suffix_1"> 
<div id="gatherling_main" class="box"> 
<div class="alpha omega grid_10 uppertitle">Gatherling</div> 
<div class="clear"></div>
<p>
Welcome to Gatherling!  With Gatherling you can keep track of 
your decks in order to see what you played last tournament, last month, or even 
last year.  You can keep track of all of your decks played here at the PauperKrew.com tournaments, and Gatherling will keep a record of how they do.
</p>
<p>
<div class="alpha grid_5"> 
<b>Some good starting points:</b>
<ul> 
<li> <a href="eventreport.php"> See a list of recent events </a> </li> 
<li> <a href="decksearch.php"> Search for decks with a certain card </a> </li> 
</ul> 
<p>
<b>Gatherling Statistics for PauperKrew.com:</b> 
<ul> 
<li> There are <?php echo Deck::uniqueCount() ?> unique decks. </li>
<li> We have recorded <?php echo Match::count() ?> matches from <?php echo Event::count() ?> events.</li> 
<li> There are <?php echo Player::activeCount() ?> active players in gatherling. (<?php echo Player::verifiedCount() ?> verified) </li>
</ul>
</p>
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

<div id="gatherling_news" class="box">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 17th, 2012 - Graphics Update! </div>
<div class="clear"></div>
    <ul>
     <li> First ever tournament got under way today: PK Standard 1.01!!!! </li>
     <li> Fixed deprecated split function in Event Report. Fist time I saw the event report run, so didn't know that bug existed. </li>
     <li> Created and installed missing combination mana symbols. Again didn't realize these were needed until I saw the event report run! I just made the ones that were needed for the tournament so far. Will need to make more to complete the set later. </li>
     <li> Fixed bug on event report that displaed all the regular mana symbols. </li>
     <li> Restylized event report to match the rest of the site. For now on all stylization code will be put in the theme. Stylization will not be hard coded any longer in preperation for installable themes! </li>
    </ul>    
<div class="clear"></div>
</div> <!-- box gatherlingnews -->

<div id="gatherling_news" class="box">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 16th, 2012 - Graphics Update! </div>
<div class="clear"></div>
    <ul>
     <li> Complete redesign of Gatherling's imageset. I reused some of the images after the conversion but converted to the new and better PNG format. The originals were .GIF (Yuck). But I think you will really like the new mana symbols. The number set is temporary, I just used what I had in .PNG format. </li>
     <li> Moved the imageset to a directory within Gatherling instead of using external links to the images. This will make Gatherling much more portable. </li>
     <li> Updated all the Gatherling modules to use the new PNG imageset and the new imageset directory. </li>
     <li> Welcome new member coolcat1678! </li>
    </ul>    
<div class="clear"></div>
</div> <!-- box gatherlingnews -->

<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 15th, 2012 - Bug fix! </div>
<div class="clear"></div>
    <ul>
     <li> Fixed the deprecated function error in the host cp. Now all of the control panels are 100% functional! There is still a player cp bug that effects new players. Trotsky is currently working on the fix for that. </li>
     <li> Fixed the deprecated function error in the deck uploading script. So now you won't get errors any more when entering your decklists. </li>
     <li> That is all the bugs that I know about in Gatherling. If anyone sees any other bugs, please let me know. </li>
     <li> Minor tweaks to the style sheets. </li>
     <li> Uploaded new header for the Chandra theme. Work will now begin on the Jace theme for Gatherling. </li>
     <li> Welcome new members Enjambment, griiisu, and plateddragon! </li>
     <li> New Card Set! Magic 2012 is now in Gatherling. We can now start PK Standard 1.01, so please enter your decklists! </li>
    </ul>    
<div class="clear"></div>
</div> <!-- box gatherlingnews -->

<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 14th, 2012 - New Card Set Added! </div>
<div class="clear"></div>
    <ul>
     <li> New Phyrexia is now in Gatherling. </li>
     <li> I think we are going to have to write some special code to deal with phyrexian mana symbols. Gatherling is currently not equiped to do it. Going to add a new field 'isp' for phyrexian mana. That way we can display phyrexian mana symbols in the analysis if we choose. </li>
     <li> Welcome new members jcrodd7776, sadisteck, The Phenom! </li>
     <li> Began re-stylizing Gatherling to blend in more with the Pauper Krew.com Website. </li>
     <li> Re-wrote Gatherling News section so each article would have it's own box. </li>
    </ul>    
<div class="clear"></div>
</div> <!-- box gatherlingnews -->

<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 12th, 2012 - New Card Set Added! </div>
<div class="clear"></div>
    <ul>
     <li> Mirrodin Besieged is now in Gatherling. </li>
     <li> Added Style sheets. These are the style sheets used by PDCMagic. I am currently designing Pauper Krew Originals. </li>
     <li> Changed all the page headings to PauperKrew.com </li>
     <li> Fixed the Trophy and Series image upload bugs! </li>
     <li> Uploaded Registration, PK Standard, and a temporary PK Classic Logo's! </li>
   </ul>
<div class="clear"></div>  
</div> <!-- box gatherlingnews -->

<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 10th, 2012 - New Card Set Added! </div>
<div class="clear"></div>
    <ul>
     <li> Scars of Mirrodin is now in Gatherling. </li>
     <li> Welcome New members Trotsky, and Hoju_ca! </li>
     <li> Trotsky joins the Pauper Krew Gatherling development team.</li>
   </ul>
<div class="clear"></div>
</div> <!-- box gatherlingnews -->
 
<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 6th, 2012 - First Cardset Installed! </div>
<div class="clear"></div>
    <ul>
     <li> Solved the deck editor bug! </li>
     <li> Created the first deck! Was really thinking I would just find another bug, but no it works! Even the analysis is working right! </li>
     <li> The last major hurtle now is just to get the card sets inserted into the database. I believe all the functionality bugs are solved. </li>
     <li> The last bugs I know of prevents us from loading Series Banners into the database, and the new player cp bug. They won't prevent us from using Gatherling. </li>
     <li> Final Gatherling graphics update! (I think). Should have all the graphics uploaded now that Gatherling looks for. </li>
     <li> Got Innistrad installed! </li>
     <li> Dark Ascension is now installed! </li>
   </ul>
<div class="clear"></div>   
</div> <!-- box gatherlingnews -->
 
<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 5th, 2012 - Bug Update! </div>
<div class="clear"></div>
    <ul>
     <li> Solved a bug in the changed password function. So now we can reset a user's password if they forget it. </li>
     <li> Finally got SSH access to the website. I couldn't get the cardlistinsert program to work though. :( </li>
     <li> Manually populated a few cards into the Innistrad card set though so I could try out the deck edit functions. </li>
     <li> Found out that there is a bug in the deck editor function that will not let us create new decks. blah! </li>
     <li> Well we are down to 2 known bugs in the PauperKrew.com Gatherling that is preventing us from using it. Almost there! </li>
     <li> Created a new Series, PK Standard - for Pauper Standard. Looking for a host for this series if anyone is interested. </li>
     <li> I imagine that the way most of our series will get hosted is just at random times when people are board. </li>
    </ul>
<div class="clear"></div>
</div> <!-- box gatherlingnews -->
 
<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 3nd, 2012 - Graphics Update! </div>
<div class="clear"></div>
    <ul>
     <li> Gatherling graphics update brings Mana Symbols, verified user check, and  1st, 2nd, Top 4, Top 8 bullets.</li>
     <li> Two new players have registered. Welcome Gunha and MrJolly! </li>
    </ul>
<div class="clear"></div> 
</div> <!-- box gatherlingnews -->
 
<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: February 2nd, 2012 - Check out our new Gatherling Banner!</div>
<div class="clear"></div>
   <ul>
    <li> Gatherling database has been populated with initialization data. </li>
    <li> First series is created, PK Classic! </li>
    <li> Fixed the host cp bug, and the static database bugs.</li>
    <li> Created a registration event to enble player cp. (Known bug, will be fixing soon)</li>
    <li> First event created, PK Classic 1.01 Scheduled for February 11th, 2012! Currently need a host, anyone interested?</li>
    <li> First players joined Gatherling! Welcome dougbiss, ParoXitiC, manhandle, and pelao28! </li>
   </ul>
<div class="clear"></div>
</div> <!-- box gatherlingnews -->
 
<div class="box" id="gatherling_news">
<div class="uppertitle alpha omega grid_10"> Gatherling News: January 30th, 2012 - Welcome to PauperKrew.com's Gatherling! </div>
<div class="clear"></div>
  <ul>
    <li> Gatherling has been installed on the PauperKrew.com server. </li>
    <li> Gatherling Database is created </li>
    <li> SuperUser registered so that database can be updated and populated.</li>
   </ul>
<div class="clear"></div>
</div> <!-- box gatherlingnews -->

</div> <!-- grid 10 pre 1 suff 1-->
<?php print_footer(); ?> 
</div>  <!-- container -->
</body>
</html>