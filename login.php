<?php include 'lib.php';?>
<?php $in = testLogin();?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Login</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Login</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>Gatherling Login</h1></td></tr>
<tr><td bgcolor=white><br>

<?php content($in);?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3>Updated by <b>WoCoNation</b> on 2007-12-10</td></tr></table></div>
<br><br></div></div>
<?php include '../footer.ssi';?>

<?php
function content($in) {
	if(!$in) {
		loginForm();
	}
}

function loginForm() {
	if(isset($_POST['mode'])) {loginFailed();}
	echo "<form action=\"login.php\" method=\"post\">\n";
	echo "<table align=\"center\" style=\"border-width: 0px\" cellpadding=\"3\">\n";
	echo "<tr><td><b>MTGO Username</td>\n";
	echo "<td><input type=\"text\" name=\"username\" value=\"\"></td></tr>\n";
	echo "<tr><td><b>Gatherling Password</td>\n";
	echo "<td><input type=\"password\" name=\"password\" value=\"\">\n";
	echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td colspan=\"2\" align=\"center\">\n";
	echo "<input type=\"submit\" name=\"mode\" value=\"Log In\">\n";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td colspan=\"2\" align=\"center\">\n";
	echo "Please <a href=\"register.php\">Click Here</a> if you need to ";
	echo "register.\n";
	echo "</table>\n";
	echo "</form>\n";
}

function loginFailed() {
	echo "<center>Incorrect username or password. Please try again.\n";
	echo "</center><br>";
}

function testLogin() {
	$success = 0;
  if(isset($_POST['username']) && isset($_POST['password'])) {
    $auth = Player::checkPassword($_POST['username'], $_POST['password']);
    if ($auth) { 
      session_start();
      header("Cache-control: private");
      $_SESSION['username'] = $_POST['username'];
      header("location: player.php");
      $success = 1;
    }
  }
	return $success;
}
?>
