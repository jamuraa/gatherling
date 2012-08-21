<?php session_start();
require_once 'lib.php';
$player = Player::getSessionPlayer();

print_header("Player Control Panel");
?>
<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Player Control Panel </div>
<?php
if ($player == NULL) {
  echo "<center> You must <a href=\"login.php\">log in</a> to use your player control panel.</center>\n";
} else {
  // Handle actions
  if (isset($_POST['action'])) {
    if ($_POST['action'] == 'setIgnores') {
      setPlayerIgnores();
    } else if ($_POST['action'] == 'changePassword') {
      $success = false;
      if ($_POST['newPassword2'] == $_POST['newPassword']) {
        if (strlen($_POST['newPassword']) >= 6) {
          $authenticated = Player::checkPassword($_POST['username'], $_POST['oldPassword']);
          if ($authenticated) {
            $player = new Player($_POST['username']);
            $player->setPassword($_POST['newPassword']);
            $result = "Password changed.";
            $success = true;
          } else {
            $result = "Password *not* changed, your old password was incorrect!";
          }
        } else {
          $result = "Password *not* changed, your new password needs to be longer!";
        }
      } else {
        $result = "Password *not* changed, your new passwords did not match!";
      }
    } else if ($_POST['action'] == 'verifyAccount') {
      $success = false;
      if ($player->checkChallenge($_POST['challenge'])) {
        $player->setVerified(true);
        $result = "Successfully verified your account with MTGO.";
        $success = true;
      } else {
        $result = "Your challenge is wrong.  Get a new one by sending the message 'ua pdcmagic' to infobot on MTGO!";
      }
    } else if ($_POST['action'] == 'finalize_result') {
      // write results to matches table
      Match::saveReport($_POST['report'], $_POST['match_id'], $_POST['player']);
    } else if ($_POST['action'] == 'drop') {
      // drop player from event
      echo "Trying to drop";
      Standings::dropPlayer($_POST['event'], $_POST['player']);
    }
  }
  // Handle modes
  $dispmode = 'playercp';
  if (isset($_GET['mode'])) {
    $dispmode = $_GET['mode'];
  }
  if (isset($_POST['mode'])) {
    $dispmode = $_POST['mode'];
  }
  //* redo this later as switch rather than elseifs, for now just kludge on
  if ($dispmode == 'alldecks') {
    print_allContainer();
  } elseif ($dispmode == 'allratings') {
    if(!isset($_GET['format'])) {$_GET['format'] = "Composite";}
    print_ratingsTable($_SESSION['username']);
    echo "<br /><br />";
    print_ratingHistoryForm($_GET['format']);
    echo "<br />";
    print_ratingsHistory($_GET['format']);
  } elseif ($dispmode == 'allmatches') {
    print_allMatchForm($player);
    print_matchTable($player);
  } elseif ($dispmode == 'Filter Matches') {
    print_allMatchForm($player);
    print_matchTable($player);
  } elseif ($dispmode == 'changepass') {
    print_changePassForm($player, $result);
  } elseif ($dispmode == 'submit_result') {
    print_submit_resultForm($_GET['player'], $_GET['match_id']);
  } elseif ($dispmode == 'verify_result') {
    print_verify_resultForm($_POST['report'], $_POST['match_id'],$_POST['player']);
  } elseif ($dispmode == 'standings') {
    Standings::printEventStandings($_GET['event'],$_SESSION['username']);
  } elseif ($dispmode == 'verifymtgo') {
    // print_verifyMtgoForm($player, $result);
    print_manualverifyMtgoForm();
  } elseif ($dispmode == 'drop_form') {
    print_dropConfirm($_GET['event'], $player);
  } else {
    print_mainPlayerCP($player);
  }
}
?>
</div> <!-- gatherling_main box -->
</div> <!-- grid 10 suff 1 pre 1 -->

<?php print_footer(); ?>

<?php
//Drop confirmation form

//"player.php?action=drop&event={$event->name}
function print_dropConfirm($event_name, $player) {
  echo <<<EOD
<center><h3>Drop Form</h3>
<center style="color: red; font-weight: bold;">
Are you sure you want to drop? This cannot be undone. </center>
Please be sure to submit a result for any active matches before you drop.\n
<table class="form">
<tr><th>
<td>
<form action="player.php" method="post">
  <input name="action" type="hidden" value="drop" />
  <input name="event" type="hidden" value="{$event_name}" />
  <input name="player" type="hidden" value="{$player->name}" />
  <input name="submit" type="submit" value="Drop from Event" />
</form>
  <form action="player.php" method="get">
    <input name="submit" type="submit" value="Cancel" />
  </form>
  </td> </tr>
  <tr> <td colspan="2" class="buttons">
  </td> </tr> </table>
EOD;
}

//* form to report results
//{$player->name}
function print_submit_resultForm($player, $match_id) {
  echo "<center><h3>Report Game Results</h3>
  Enter results for this match</center>\n";
  echo "<center style=\"color: red; font-weight: bold;\">****</center>\n";
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<input name=\"mode\" type=\"hidden\" value=\"verify_result\" />\n";
  echo "<input name=\"match_id\" type=\"hidden\" value=\"{$match_id}\" />\n";
  echo "<input name=\"player\" type=\"hidden\" value=\"{$player}\" />\n";
  echo "<table class=\"form\">";
  echo "<tr><th><input type='radio' name='report' value='W20' /> I won the match 2-0<br /></th>\n";
  echo "<td></td> </tr>\n";
  echo "<tr><th><input type='radio' name='report' value='W21' />I won the match 2-1</th>\n";
  echo "<td></td> </tr>\n";
  echo "<tr><th><input type='radio' name='report' value='L20' />I lost the match 0-2</th>\n";
  echo "<td> </td> </tr>\n";
  echo "<tr><th><input type='radio' name='report' value='L21' />I lost the match 1-2</th>\n";
  echo "<td> </td> </tr>\n";
  echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
  echo "<input name=\"submit\" type=\"submit\" value=\"Submit Match Report\" />\n";
  echo "</td> </tr> </table> \n";
  echo "</form>\n";
  echo "<div class=\"clear\"> </div>\n";
}

//* form to confirm submission
function print_verify_resultForm($report, $match_id, $player) {
  echo "<center><h3><br>Confirm Game Results</p></h3> </center>\n";
  echo "<center style=\"color: red; font-weight: bold;\">Please confirm your entry.</center></p>\n";
  echo "<center><h4>";
  switch ($report){
    case "W20":
      echo "I won the match 2-0";
      break;
    case "W21":
      echo "I won the match 2-1";
      break;
    case "L20":
      echo "I lost the match 0-2";
      break;
    case "L21":
      echo "I lost the match 1-2";
      break;
  }
  echo "</center></h4></p>";
  echo "<center style=\"color: red; font-weight: bold;\">*</center>\n";

  echo "<table class=\"form\">";
  echo "<tr><th>";
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<input name=\"action\" type=\"hidden\" value=\"finalize_result\" />\n";
  echo "<input name=\"match_id\" type=\"hidden\" value=\"{$match_id}\" />\n";
  echo "<input name=\"report\" type=\"hidden\" value=\"{$report}\" />\n";
  echo "<input name=\"player\" type=\"hidden\" value=\"{$player}\" />\n";
  echo "<input name=\"submit\" type=\"submit\" value=\"Verify Match Report\" />\n";
  echo "</form>\n";
  echo "</th>\n";
  echo "<td> ";
  echo "<form action=\"player.php\" method=\"get\">\n";
  echo "<input name=\"match_id\" type=\"hidden\" value=\"{$match_id}\" />\n";
  echo "<input name=\"mode\" type=\"hidden\" value=\"submit_result\" />\n";
  echo "<input name=\"submit\" type=\"submit\" value=\"Go Back and Correct\" />\n";
  echo "</form>\n";
  echo "</td> </tr> \n";
  echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";

  echo "</td> </tr> </table> \n";
  echo "</form>\n";
  echo "<div class=\"clear\"> </div>\n";
}

function print_changePassForm($player, $result) {
  echo "<center><h3>Changing your password</h3>
    New passwords are required to be at least 6 characters long.</center>\n";
  echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<input name=\"action\" type=\"hidden\" value=\"changePassword\" />\n";
  echo "<input name=\"mode\" type=\"hidden\" value=\"changepass\" />\n";
  echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
  echo "<table class=\"form\">";
  echo "<tr><th>Current Password</th>\n";
  echo "<td> <input name=\"oldPassword\" type=\"password\" /></td> </tr> \n";
  echo "<tr><th>New Password</th>\n";
  echo "<td> <input name=\"newPassword\" type=\"password\" /></td> </tr> \n";
  echo "<tr><th>Repeat New Password</th>\n";
  echo "<td> <input name=\"newPassword2\" type=\"password\" /></td> </tr> \n";
  echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
  echo "<input name=\"submit\" type=\"submit\" value=\"Change Password\" />\n";
  echo "</td> </tr> </table> \n";
  echo "</form>\n";
  echo "<div class=\"clear\"> </div>\n";
}

function print_verifyMtgoForm($player, $result) {
  echo "<center><h3>Verifying your MTGO account</h3>
    Verify your MTGO account by following these simple steps:<br />
    1. Chat 'ua pdcmagic' to infobot to get a verification code <br />
    2. Enter the verification code here to be verified <br />
    \n";
  echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
  if ($player->verified == 1) {
    echo "<center>You are already verified!</center>\n";
    echo "<a href=\"player.php\">Go back to the Player CP</a>\n";
  } else {
    echo "<form action=\"player.php\" method=\"post\">\n";
    echo "<input name=\"action\" type=\"hidden\" value=\"verifyAccount\" />\n";
    echo "<input name=\"mode\" type=\"hidden\" value=\"verifymtgo\" />\n";
    echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
    echo "<table class=\"form\">";
    echo "<tr><th>Verification Code</th>\n";
    echo "<td> <input name=\"challenge\" type=\"text\" /></td> </tr> \n";
    echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
    echo "<input name=\"submit\" type=\"submit\" value=\"Verify Account\" />\n";
    echo "</td> </tr> </table> \n";
    echo "</form>\n";
  }
  echo "<div class=\"clear\"> </div>\n";
}

function setPlayerIgnores() {
  global $player;
  $noDeckEntries = $player->getNoDeckEntries();
  foreach ($noDeckEntries as $entry) {
    if (isset($_POST['ignore'][$entry->event->name])) {
      $entry->setIgnored(1);
    } else {
      $entry->setIgnored(0);
    }
  }
}

function print_mainPlayerCP($player) {
  $upper = strtoupper($_SESSION['username']);
  echo "<div class=\"alpha grid_5\">\n";
  echo "<div id=\"gatherling_lefthalf\">\n";
  print_conditionalAllDecks();
  print_recentDeckTable();
  print_preRegistration();
  print_ActiveEvents();  //* new
  print_recentMatchTable();
  print_currentMatchTable();  //* new
  echo "</div></div>\n";
  echo "<div class=\"omega grid_5\">\n";
  echo "<div id=\"gatherling_righthalf\">\n";
  print_ratingsTableSmall();
  print_statsTable();
  echo "<b>ACTIONS</b><br />\n";
  echo "<ul>\n";
  echo "<li><a href=\"player.php?mode=changepass\">Change your password</a></li>\n";
  if ($player->verified == 0) {
    echo "<li><a href=\"player.php?mode=verifymtgo\">Verify your MTGO account</a></li>\n";
  }
  echo "</ul>\n";
  echo "</div></div>\n";
  echo "<div class=\"clear\"></div>\n";
}

function print_allContainer() {
  $rstar = "<font color=\"#FF0000\">*</font>";
  echo "<p> Decks marked with a $rstar have less than 60 cards listed. <br />\n";
  echo " Decks marked with $rstar$rstar have less than 6 cards listed, and were created as placeholder decks.</p>\n";
  echo "<div class=\"alpha grid_6\">\n";
  echo "<div id=\"gatherling_lefthalf\">\n";
  print_allDeckTable();
  echo "</div> </div> \n";
  echo "<div class=\"omega grid_4\">\n";
  echo "<div id=\"gatherling_righthalf\">\n";
  print_noDeckTable();
  echo "</div> </div> \n";
  echo "<div class=\"clear\"> </div> ";
}

function print_recentDeckTable() {
  global $player;

  echo "<table>\n";
  echo "<tr><td colspan=2><b>RECENT DECKS</td>\n";
  echo "<td colspan=2 align=\"right\">";
  echo "<a href=\"player.php?mode=alldecks\">";
  echo "(see all)</a></td>\n";

  $event = $player->getLastEventPlayed();
  if (is_null($event)) {
    echo "<tr><td>No Decks Found!</td>\n";
  } else {
    $entry = new Entry($event->name, $player->name);
    if ($entry->deck) {
      $decks = $player->getRecentDecks(6);
    } else {
      $decks = $player->getRecentDecks(5);
    }

    if (!$entry->deck) {
      $cell1 = medalImgStr($entry->medal);
      $cell4 = $entry->recordString();
      echo "<tr><td>$cell1</td>\n";
      echo "<td align=\"left\">" . $entry->createDeckLink() . "</td>";
      echo "<td><a href=\"{$event->threadurl}\">{$event->name}</a></td>\n";
      echo "<td align=\"right\">$cell4</td></tr>\n";
    }
    foreach ($decks as $deck) {
      $cell1 = medalImgStr($deck->medal);
      $cell4 = $deck->recordString();
      echo "<tr><td>$cell1</td>\n";
      echo "<td>" . $deck->linkTo() . "</td>\n";
      echo "<td><a href=\"{$deck->getEvent()->threadurl}\">{$deck->eventname}</a></td>\n";
      echo "<td align=\"right\">$cell4</td></tr>\n";
    }
  }
  echo "</table>\n";
}

function print_preRegistration() {
  global $player;
  $events = Event::getNextPreRegister();
  echo "<table><tr><td colspan=\"3\"><b>PREREGISTER FOR EVENTS</b></td></tr>";
  if (count($events) == 0) {
    echo "<tr><td colspan=\"3\"> No Upcoming Events! </td> </tr>";
  }
  foreach ($events as $event) {
    echo "<tr><td>{$event->name}</td>";
    echo "<td>" . distance_of_time_in_words(time(), strtotime($event->start)) . "</td>";
    if ($event->hasRegistrant($player->name)) {
      echo "<td>Registered <a href=\"prereg.php?action=unreg&event={$event->name}\">(Unreg)</a></td>";
    } else {
      echo "<td><a href=\"prereg.php?action=reg&event={$event->name}\">Register</a></td>";
    }
    echo "</tr>";
  }
  echo "</table>";
}

//* Modified above function to display active events and a link to current standings
// Undecided about showing all active events, or only those the player is enrolled in.
function print_ActiveEvents() {
  global $player;
  $events = Event::getActiveEvents();
  echo "<table><tr><td colspan=\"3\"><b>ACTIVE EVENTS</b></td></tr>";
  if (count($events) == 0) {
    echo "<tr><td colspan=\"3\"> No events are currently active. </td> </tr>";
  }
  foreach ($events as $event) {
    echo "<tr><td>{$event->name}</td>";
    echo "<td><a href=\"player.php?mode=standings&event={$event->name}\">Current Standings</a></td>";
    if (Standings::playerActive($event->name, $player->name)){
      echo "<td><a href=\"player.php?mode=drop_form&event={$event->name}\">Drop From Event</a></td>";
    }
    echo "</tr>";
  }
  echo "</table>";
}


function print_noDeckTable() {
  global $player;
  $entriesnodecks = $player->getNoDeckEntries();

  echo "<form action=\"player.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"action\" value=\"setIgnores\" />";
  echo "<table style=\"border-width: 0px;\" width=275>\n";
  echo "<tr><td colspan=4 style=\"font-size: 14px; color: red;\">";
  echo "<b>UNENTERED DECKS</td></tr>\n";
  foreach ($entriesnodecks as $entry) {
    $imgcell = medalImgStr($entry->medal);
    echo "<tr><td>$imgcell</td>\n";
    echo "<td align=\"left\">" . $entry->createDeckLink() . "</td>";
    echo "<td align=\"right\"><a href=\"{$entry->event->threadurl}\">{$entry->event->name}</a></td>\n";
    echo "<td><input type=\"checkbox\" name=\"ignore[{$entry->event->name}]\" value=\"yes\" ";
    if ($entry->ignored) {
      echo " checked";
    }
    echo " /> </td> ";
    echo "</tr>\n";
  }
  echo "</table>\n";
  echo "<input type=\"hidden\" name=\"mode\" value=\"alldecks\" />";
  echo "<center><input type=\"submit\" value=\"Set Ignored / Unremembered Decks\" /></center>";
  echo "</form>";
}

function print_allDeckTable() {
  global $player;
  $decks = $player->getAllDecks();
  $rstar = "<font color=\"#FF0000\">*</font>";
  $upPlayer = strtoupper($player->name);

  echo "<table style=\"border-width: 0px;\" width=275>\n";
  echo "<tr><td colspan=3><b>$upPlayer'S DECKS</td></tr>\n";
  foreach ($decks as $deck) {
    $imgcell = medalImgStr($deck->medal);
    echo "<td width=20>$imgcell</td>\n";
    echo "<td>" . $deck->linkTo();
    $cards = $deck->getCardCount();
    if($cards < 60) {print $rstar;}
    if($cards < 6)  {print $rstar;}
    echo "</td>\n";
    $event = $deck->getEvent();
    echo "<td align=\"right\"><a href=\"{$event->threadurl}\">{$event->name}</a></td>\n";
    echo "</td></tr>\n";
  }
  echo "</table>\n";
}

function print_recentMatchTable() {
  global $player;
  $matches = $player->getRecentMatches();

  echo "<table style=\"border-width: 0px\" width=300>\n";
  echo "<tr><td colspan=3><b>RECENT MATCHES</td><td align=\"right\">\n";
  echo "<a href=\"player.php?mode=allmatches\">(see all)</a></td></tr>\n";
  foreach ($matches as $match) {
    $res = "Draw";
    if ($match->playerWon($player->name)) {
      $res = "Win";
    }
    if ($match->playerLost($player->name)) {
      $res = "Loss";
    }
    if ($match->playera == $match->playerb) {
      $res = "BYE";
    }
    $opp = $match->playera;
    if (strcasecmp($player->name, $opp) == 0) {
      $opp = $match->playerb;
    }
    echo "<tr><td><b>$res</b><b>{$match->getPlayerWins($player->name)}</b><b> - </b><b>{$match->getPlayerLosses($player->name)}</b></td>";
    echo "<td>vs.</td>\n";
    $oppplayer = new Player($opp);
    echo "<td>" . $oppplayer->linkTo() . "</td></tr>\n";
  }
  echo "</table>\n";
}

//copied above function and altered to show matches in progress
function print_currentMatchTable() {
  global $player;

  $matches = $player->getCurrentMatches();

  echo "<table style=\"border-width: 0px\" width=300>\n";
  echo "<tr><td colspan=4><b>ACTIVE MATCHES</td><td align=\"right\">\n";
  echo "</td></tr>\n";
  foreach ($matches as $match) {
    $event = new Event($match->getEventNamebyMatchid());
    $opp = $match->playera;
    $player_number="b";
    if (strcasecmp($player->name, $opp) == 0) {
      $opp = $match->playerb;
      $player_number="a";
    }


    if ($match->result != "BYE") {
      $oppplayer = new Player($opp);
      echo "<tr><td></td>";
      echo "<td>vs.</td>\n";
      echo "<td>" . $oppplayer->linkTo() ."</td><td>";
      if ($match->verification == "unverified"){
        if ($player_number=="b" AND ($match->playerb_wins + $match->playerb_losses) > 0){
          echo "(Report Submitted)";
        } else if ($player_number=="a" AND ($match->playera_wins + $match->playera_losses) > 0){
          echo "(Report Submitted)";
        } else {
          if ($match->player_reportable_check() == True){
            echo "<a href=\"player.php?mode=submit_result&match_id=".$match->id."&player=".$player_number ."\">(Report Result)</a>";
          } else {
            echo "Please report results in the report channel for this event";
          }
        }
      } else if ($match->verification == "failed") {
        echo "<font style=\"color: red; font-weight: bold;\">Verification Failed  </style><a href=\"player.php?mode=submit_result&match_id=".$match->id."&player=".$player_number ."\">(Correct Result)</a>";
      } else if ($match->result == "BYE") {
      } else { // Only verified left
        echo "(Report Submitted)";
      }
      echo "</td></tr>\n";

    } else { // result is a bye
      if ($match->round == $event->current_round){
        echo "<tr><td>";
        echo $event->name." Round: ".$event->current_round." ";
        echo "</td>";
        echo "<td>You have a BYE for the current round.</td>\n";
        echo "</tr>\n";
      }
    }

  }
  echo "</table>\n";
}

function print_matchTable($player, $limit=0) {
  if (!isset($_POST['format'])) {
    $_POST['format'] = "%";
  }
  if (!isset($_POST['series'])) {
    $_POST['series'] = "%";
  }
  if (!isset($_POST['season'])) {
    $_POST['season'] = "%";
  }
  if (!isset($_POST['opp'])) {
    $_POST['opp'] = "%";
  }

  $matches = $player->getFilteredMatches($_POST['format'], $_POST['series'], $_POST['season'], $_POST['opp']);

  echo "<table class=\"scoreboard\">";
  echo "<tr class=\"top\"><th>Event</th><th>Round</th><th>Opponent</th><th>Deck</th><th>Rating</th><th>Result</th></tr>";
  $oldname = "";
  $rowcolor = "even";
  $Count = 1;
  foreach ($matches as $match) {
    $rnd = $match->round;
    if ($match->timing == 2 && $match->type == "Single Elimination") {
      $rnd = "T" . pow(2, $match->rounds + 1 - $match->round);
    }

    $opp = $match->otherPlayer($player->name);
    $res = "D";
    if ($match->playerWon($player->name)) {
      $res = "W";
    }
    if ($match->playerLost($player->name)) {
      $res = "L";
    }
    $opponent = new Player($opp);

    $event = $match->getEvent();
    $oppRating = $opponent->getRating("Composite", $event->start);
    $oppDeck = $opponent->getDeckEvent($event->name);
    $deckStr = "No Deck Found";

    if (!is_null($oppDeck)) {
      $deckStr = $oppDeck->linkTo();
    }

    if ($oldname != $event->name) {
      if ($Count % 2 != 0) {
        $rowcolor = "odd";
        $Count++;
      } else {
        $rowcolor = "even";
        $Count++;
      }
      echo "<tr class=\"{$rowcolor}\"><td>{$event->name}</td>";
    } else {
      echo "<tr class=\"{$rowcolor}\"><td></td>\n";
    }
    $oldname = $event->name;
    echo "<td>$rnd</td>\n";
    echo "<td>" . $opponent->linkTo() . "</td>\n";
    echo "<td>$deckStr</td>\n";
    echo "<td>$oppRating</td>\n";
    echo "<td>$res {$match->getPlayerWins($player->name)} - {$match->getPlayerLosses($player->name)} </td>";
    echo "</tr>\n";
  }
  echo "</table>";
}

function print_ratingsTableSmall() {
  global $player;
  $composite = $player->getRating("Composite");
  $standard = $player->getRating("Standard");
  $extended = $player->getRating("Extended");
  $classic = $player->getRating("Classic");
  $other = $player->getRating("Other Formats");

  echo "<table style=\"border-width: 0px;\" width=300>";
  echo "<tr><td colspan=1><b>MY RATINGS</td>\n";
  echo "<td colspan=1 align=\"right\">";
  echo "<a href=\"player.php?mode=allratings\">(see all)</a></td></tr>\n";
  echo "<tr><td>Composite</td><td align=\"right\">$composite</td></tr>\n";
  echo "<tr><td>Standard</td><td align=\"right\">$standard</td></tr>\n";
  echo "<tr><td>Extended</td><td align=\"right\">$extended</td></tr>\n";
  echo "<tr><td>Classic</td><td align=\"right\">$classic</td></tr>\n";
  echo "<tr><td>Other Formats</td><td align=\"right\">$other</td></tr>\n";
  echo "</table>";
}

function print_ratingsTable() {
  echo "<table style=\"border-width: 0px;\" width=400 align=\"center\">\n";
  echo "<tr><td><b>Format</td>\n";
  echo "<td align=\"center\"><b>Rating</td>\n";
  echo "<td align=\"center\"><b>Record</td>\n";
  echo "<td align=\"center\"><b>Low</td>\n";
  echo "<td align=\"center\"><b>High</td></tr>\n";
  print_ratingLine("Composite");
  print_ratingLine("Standard");
  print_ratingLine("Extended");
  print_ratingLine("Classic");
  print_ratingLine("Other Formats");
  echo "</table>\n";
}

function print_ratingLine($format) {
  global $player;
  $rating = $player->getRating($format);
  $record = $player->getRatingRecord($format);
  $max = $player->getMaxRating($format);
  $min = $player->getMinRating($format);

  echo "<tr><td>$format</td>\n";
  echo "<td align=\"center\">$rating</td>\n";
  echo "<td align=\"center\">$record</td>\n";
  if(isset($min)) {
    echo "<td align=\"center\">$min</td>\n";
    echo "<td align=\"center\">$max</td>\n";
  }
  else {
    echo "<td colspan=2 align=\"center\">";
    echo "<i>Less than 20 matches played</td>\n";
  }
  echo "</tr>\n";
}

function print_ratingsHistory($format) {
  global $player;
  $db = Database::getConnection();
  $stmt = $db->prepare("SELECT e.name, r.rating, n.medal, n.deck AS id
    FROM events e, entries n, ratings r
    WHERE r.format= ? AND r.player = ?
    AND e.start=r.updated AND n.player=r.player AND n.event=e.name
    ORDER BY e.start DESC");
  $stmt->bind_param("ss", $format, $player->name);
  $stmt->execute();
  $stmt->bind_result($eventname, $rating, $medal, $deckid);

  $stmt->store_result();

  echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
  echo "<tr><td align=\"center\"><b>Pre-Event</td>\n";
  echo "<td><b>Event</td>\n";
  echo "<td><b>Deck</td>\n";
  echo "<td align=\"center\"><b>Record</td>\n";
  echo "<td align=\"center\"><b>Medal</td>\n";
  echo "<td align=\"center\"><b>Post-Event</td></tr>\n";

  if($stmt->num_rows > 0) {
    $stmt->fetch();
    $preveventname = $eventname;
    $prevrating = $rating;
    while($stmt->fetch()) {
      $entry = new Entry($preveventname, $player->name);
      $wl = $entry->recordString();
      $img = medalImgStr($entry->medal);

      echo "<tr><td align=\"center\">{$rating}</td>\n";
      echo "<td>{$preveventname}</td>\n";
      echo "<td>" . $entry->deck->linkTo() . "</td>\n";
      echo "<td align=\"center\">$wl</td>\n";
      echo "<td align=\"center\">$img</td>";
      echo "<td align=\"center\">{$prevrating}</td></tr>";
      $prevrating = $rating;
      $preveventname = $eventname;
    }

    $entry = new Entry($preveventname, $player->name);
    $wl = $entry->recordString();
    $img = medalImgStr($entry->medal);
    echo "<tr><td align=\"center\">1600</td>\n";
    echo "<td>{$preveventname}</td>\n";
    echo "<td>" . $entry->deck->linkTo() . "</td>\n";
    echo "<td align=\"center\">$wl</td>\n";
    echo "<td align=\"center\">$img</td>";
    echo "<td align=\"center\">{$prevrating}</td></tr>";
  } else {
    echo "<tr><td colspan=6 align=\"center\"><i>";
    echo "You have not played any $format events.</td></tr>\n";
  }
  echo "</table>\n";
}

function print_ratingHistoryForm($format) {
  $formats = array("Composite", "Standard", "Extended", "Classic",
    "Other Formats");
  echo "<center>\n";
  echo "<form action=\"player.php\" method=\"get\">\n";
  echo "Show history for&nbsp;";
  echo "<select name=\"format\">\n";
  for($i = 0; $i < sizeof($formats); $i++) {
    $sel = ($formats[$i] == $format) ? "selected" : "";
    echo "<option value=\"{$formats[$i]}\" $sel>{$formats[$i]}</option>\n";
  }
  echo "</select><br /><br />\n";
  echo "<input type=\"submit\" name=\"button\" value=\"Show History\">\n";
  echo "<input type=\"hidden\" name=\"mode\" value=\"allratings\">\n";
  echo "</form></center>\n";
}

function print_allMatchForm($player) {
  if (!isset($_POST['format'])) {
    $_POST['format'] = "%";
  }
  if (!isset($_POST['series'])) {
    $_POST['series'] = "%";
  }
  if (!isset($_POST['season'])) {
    $_POST['season'] = "%";
  }
  if (!isset($_POST['opp'])) {
    $_POST['opp'] = "%";
  }
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
  echo "<tr><td align=\"center\" colspan=2><b>Filters</td></tr>\n";
  echo "<tr><td>&nbsp;</td>\n";
  echo "<tr><td>Format&nbsp</td><td>";
  formatDropMenuP($player, $_POST['format']);
  echo "</td></tr>\n";
  echo "<tr><td>Series&nbsp;</td><td>";
  seriesDropMenuP($player, $_POST['series']);
  echo "</td></tr>\n";
  echo "<tr><td>Season&nbsp;</td><td>";
  seasonDropMenuP($player, $_POST['season']);
  echo "</td></tr>\n";
  echo "<tr><td>Opponent&nbsp;</td><td>";
  oppDropMenu($player, $_POST['opp']);
  echo "</td></tr><tr><td>&nbsp;</td></tr>\n";
  echo "<tr><td colspan=2 align=\"center\">";
  echo "<input type=\"submit\" name=\"mode\" value=\"Filter Matches\">";
  echo "</td></tr><tr><td>&nbsp;</td></tr></table></form>\n";
}

function formatDropMenuP($player, $def) {
  $formats = $player->getFormatsPlayed();

  echo "<select name=\"format\">\n";
  echo "<option value=\"%\">- Format -</option>\n";
  foreach ($formats as $thisformat) {
    $sel = ($thisformat == $def) ? "selected" : "";
    echo "<option value=\"$thisformat\" $sel>$thisformat</option>\n";
  }
  echo "</select>\n";
}

function seriesDropMenuP($player, $def) {
  $series = $player->getSeriesPlayed();

  echo "<select name=\"series\">\n";
  echo "<option value=\"%\">- Series -</option>\n";
  foreach ($series as $thisseries) {
    $sel = ($thisseries == $def) ? "selected" : "";
    echo "<option value=\"$thisseries\" $sel>$thisseries</option>\n";
  }
  echo "</select>\n";
}

function seasonDropMenuP($player, $def) {
  $seasons = $player->getSeasonsPlayed();

  echo "<select name=\"season\">\n";
  echo "<option value=\"%\">- Season -</option>\n";
  foreach ($seasons as $thisseason) {
    $sel = (($thisseason == $def) && ($def != "%")) ? "selected" : "";
    echo "<option value=\"$thisseason\" $sel>$thisseason</option>\n";
  }
  echo "</select>\n";
}

function oppDropMenu($player, $def) {
  $opponents = $player->getOpponents();

  echo "<select name=\"opp\">\n";
  echo "<option value=\"%\">- Opponent -</option>\n";
  foreach ($opponents as $row) {
    $thisopp = $row['opp'];
    $cnt = $row['cnt'];
    $sel = ($thisopp == $def) ? "selected" : "";
    echo "<option value=\"$thisopp\" $sel>$thisopp [$cnt]</option>\n";
  }
  echo "</select>";
}

function print_statsTable() {
  global $player;
  echo "<table style=\"border-width: 0px\">";
  echo "<tr><td colspan=2><b>STATISTICS</td></tr>\n";
  echo "<tr><td>Record</td><td align=\"right\"> {$player->getRecord()}";
  echo "</td></tr>\n";
  echo "<tr><td>Longest Winning Streak</td><td align=\"right\"> {$player->getStreak("W")}";
  echo "</td></tr>\n";
  echo "<tr><td>Longest Losing Streak</td><td align=\"right\"> {$player->getStreak("L")}";
  echo "</td></tr>\n";
  echo "<tr><td>Biggest Rival</td><td align=\"right\"> ";
  $rival = $player->getRival();
  if ($rival != null) {
    $rivalrec = $player->getRecordVs($rival->name);
    echo $rival->linkTo();
    echo " ({$rivalrec})";
  } else {
    echo "none";
  }
  echo "</td></tr>";
  echo "<tr><td>Favorite Card</td><td align=\"right\"> {$player->getFavoriteNonLand()}";
  echo "</td></tr>\n";
  echo "<tr><td>Favorite Land</td><td align=\"right\"> {$player->getFavoriteLand()}";
  echo "</td></tr>\n";
  echo "<tr><td>Medals Won</td><td align=\"right\"> {$player->getMedalCount()}";
  echo "</td></tr>\n";
  echo "<tr><td>Events Won</td><td align=\"right\"> {$player->getMedalCount("1st")}";
  echo "</td></tr>\n";
  echo "<tr><td>&nbsp;</td></tr>\n";
  echo "<tr><td colspan=2 align=\"center\"><b>Most Recent Trophy</td></tr>\n";
  echo "<tr><td colspan=2 align=\"center\">"; statTrophy();
  echo "</td></tr>\n";
  echo "</table>\n";
#Fave Series
#Fave Format
#highrating
#lowrating
#bestdeck
#creativity
}

function statTrophy() {
  global $player;
  $trophyevent = $player->getLastEventWithTrophy();
  if ($trophyevent != NULL) {
    $event = new Event($trophyevent);
    echo $event->getTrophyImageLink();
  } else {
    echo "<i>No trophies earned</i>\n";
  }
}

function print_conditionalAllDecks() {
  global $player;
  $noentrycount = $player->getUnenteredCount();
  if ($noentrycount > 0) {
    echo "<br /><a href=\"player.php?mode=alldecks\" style=\"color: red;\">";
    echo "You have $noentrycount unreported decks<br />";
    echo "Click here to enter them.</a>";
  }
}

?>
