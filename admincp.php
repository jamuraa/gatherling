<?php
session_start();
include 'lib.php';
include 'lib_form_helper.php';

$hasError = false;
$errormsg = "";

if (!Player::isLoggedIn() || !Player::getSessionPlayer()->isSuper()) {
  redirect("Location: index.php");
}

print_header("Admin Control Panel");
?>

<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Admin Control Panel </div>
<center>
<?php do_page(); ?>
</center>
<div class="clear"></div>
</div></div>

<?php print_footer(); ?>

<?php 

function do_page() {
  handleActions();
  printError();
  printAddCardSet();
  printChangePasswordForm();
}

function printError() {
  global $hasError;
  global $errormsg;
  if ($hasError) {
    echo "<div class=\"error\">{$errormsg}</div>";
  }
}

function printAddCardSet() {
  echo "<form action=\"util/insertcardset.php\" method=\"post\" enctype=\"multipart/form-data\">";
  echo "<h3><center>Install New Cardset</center></h3>";
  echo "<table class=\"form\" style=\"border-width: 0px\" align=\"center\">";
  print_text_input("Cardset Name", "cardsetname");
  print_text_input("Release Date", "releasedate");
  print_select_input("Set Type", "settype", array("Core", "Block", "Extra"));
  print_file_input("Cardset Text Spoiler", "cardsetfile");
  print_submit("Install New Cardset");
  echo "</table></form>";
}

function printChangePasswordForm() {
  echo "<form action=\"admincp.php\" method=\"post\">";
  echo "<h3><center>Change User Password</center></h3>";
  echo "<table class=\"form\" style=\"border-width: 0px\" align=\"center\">";
  print_text_input("Username", "username");
  print_text_input("New Password", "new_password");
  print_submit("Change Password");
  echo "</table> </form>";
}

function handleActions() {
  global $hasError;
  global $errormsg;
  if (!isset($_POST['action'])) {
    return;
  }
  if ($_POST['action'] == "Change Password") {
    $player = new Player($_POST['username']);
    $player->setPassword($_POST['new_password']);
    $result = "Password changed for user {$player->name} to {$_POST['new_password']}";
  }

  if (isset($result)) {
    echo "<div class=\"notice\">{$result}</div>";
  }
}

