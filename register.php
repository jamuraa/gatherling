<?php
include 'lib.php';
require_once 'lib_form_helper.php';

print_header("Register");
?>

<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Register for Gatherling </div>

<?php content(); ?>

</div> </div>

<?php print_footer(); ?>

<?php
function content() {
  if(!isset($_POST['pw1'])) {regForm();}
  else {
    $code = doRegister();
    if($code == 0) {
      echo "<center>Registration was successful. You may now ";
      echo "<a href=\"login.php\">Log In</a>.</center>";
    } elseif ($code == -1) {
      echo "Passwords don't match. Please go back and try again.\n";
    } elseif ($code == -2) {
      echo "The specified username was not found in the database ";
      echo "Please contact jamuraa on the forums if you feel this is ";
      echo "an error.\n";
    } elseif ($code == -3) {
      echo "A password has already been created for this account.\n";}
  }
}

function regForm() {
  echo "<form action=\"register.php\" method=\"post\">\n";
  echo "<table class=\"form\" style=\"border-width: 0px\">\n";
  print_text_input("MTGO Username", "username");
  print_password_input("Password", "pw1");
  print_password_input("Confirm Password", "pw2");
  print_submit("Register Account", "mode");
  echo "</table></form>";
}

function doRegister() {
  $code = 0;
  if (strcmp($_POST['pw1'], $_POST['pw2']) != 0) {
    $code = -1;
  }
  $player = Player::findOrCreateByName($_POST['username']);
  if (!is_null($player->password)) {
    $code = -3;
  }
  if ($code == 0) {
    $player->password = hash('sha256', $_POST['pw1']);
    $player->super = Player::activeCount() == 0;
    $player->save();
  }
  return $code;
}

?>
