<?php include 'lib.php';?>
<?php session_start();?>
<?php header("location: player.php"); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Home</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php'?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Home</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>Gatherling Home</h1></td></tr>
<tr><td bgcolor=white><br>

<?php content();?>

<br><br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3><?php version_tagline(); ?></h3>
</td></tr></table></div>
<br /><br /></div></div>
<?php include '../footer.ssi';?>

<?php function content() { ?>

<center style="font-size: 20px; color: orange;">--&gt;
<a href="player.php?mode=alldecks" style="font-size: 20px; color: red;">
CLICK HERE TO ENTER YOUR DECKLISTS
</a>&lt;--<br><br></center>
Welcome to the Gatherling home page. While I'm not sure what this page will
have on it in the future, I'm going to use it to log my changes for now. If
you want to know what I've been working on recently, you can look here. If
you want to submit a bug, please send an email to
<i>jamuraa (at) pdcmagic (dot) com</i> or PM jamuraa via the forums.<br>
<br>
<b>2008-01-04:</b><br>
- Sorry it's been so long to get an actual update in. There are probably a 
lot of small changes that I can't remember at the moment.<br>
- In the hosts' event view, the links to create a deck are a different color
than the actual deck links.<br>
- In the (see all) decks page, red stars have been used to indicate incomplete
and placeholder decks.<br>
<br>
<b>2007-12-27:</b><br>
- Fixed a bug submitted by bluedragon123 and jaknife where all matches would
be listed as draws for certain events. This was caused by php being
case-sensitive where MySQL is not.<br>
- Created this page.<br>
- Updated profile.php to include a search feature at the bottom. Also, 
profile.php will display your user profile if you are logged in but have not
specified a user.<br>
- Updated the navbar at the top to only display options appropriate to a user's
level. Note that the starred (*) items do not link anywhere at this time.<br>
- Removed the navigation at the side, as it should no longer be needed. I'll
fill the space with something interesting in the future.</br>
- Implemented a bunch of stuff stuff on the player.php page, including recent
matches, ratings, and ratings history. I still need to implement full match
listing with filters, general stats and better formatting.<br>

<?php } ?>
