<?php

/** Functions to support parsing DCI-R files and pastings and turning them into matches and results.
 */

/****  DCI-R PASTINGS PARSING ****/

/**
 * Calls a function on each regex matching line in a text block, passing in the matches from the regex.
 * @param text the block of text (multiple lines)
 * @param regex the regular expression to match the lines.
 * @param callable a callable function that will be run with the argument of the matches when a line matches.
 * @return the number of lines that matched.
 */
function regex_lines_map($text, $regex, $callable) {
  $lines = explode("\n", $text);
  $matched = 0;
  foreach ($lines as $line) {
    if (preg_match($regex, $line, $m)) {
      call_user_func($callable, $m);
      $matched++;
    }
  }
  return $matched;
}

/**
 * Extracts pairings from a block of text which is in the DCI-R format.  That format looks like this:
 *
 *  12    Garlan, x    3-3     28    Zoltan, x
 *  20    notech, x    3-3     16    Malum, x
 *  22    Rakura, x    3-3      6    chann23, x
 *  11    FlxEx, x    3-3      2    bgdp009, x
 *  14    kokonade1000, x    3-3     25    Taoist, x
 *   4    brilk, x    3-3     17    MayDay11, x
 *   3    Boxes_O_Moxes, x    3-3     27    VIP, x
 *  23    rpitcher, x    0-0      5    bubblewrap, x
 *  26    tobon, x    0-0     21    Pie_Master, x
 *  10    falcodevil, x    0-0     15    Litanss, x
 *   1    aloehart, x    0-0      8    darkknight0901, x
 *   9    ETCarch, x    0-0     19    Mikano, x
 *  13    Hogger, x    0-0     29    Flucus, x
 *  24    Sanctified, x            * BYE *
 *
 * What you get out is an array of arrays, like
 * [[Garlan, Zoltan],[notech, Malum],...]
 *
 * Byes are left out.
 */
function extractPairings($text) {
  $pairings[] = array();
  $add_pairing_func = function($matches) { $pairings[] = array($matches[2], $matches[3]); };
  regex_lines_map($text, "/^\s*[0-9]+\s+([0-9]+\s+)?([0-9a-z_.\- ]+),.*\s+[0-9]+\s+([0-9a-z_.\- ]+),/i", $add_pairing_func);
  return $pairings;
}

/**
 * Extracts the bye from a pairings block.  See extractPairings for the format.
 */
function extractBye($text) {
  $bye = NULL;
  $bye_set_func = function($matches) { $bye = $matches[2]; };
  regex_lines_map($text, "/^\s*[0-9]+\s+([0-9]+\s+)?([0-9a-z_.\- ]+),.*\s+\* BYE \*/i", $bye_set_func);
  return $bye;
}

/**
 * Extracts standings from a block of text which is in the DCI-R format.  That format looks like this:
 *
 * 12    Garlan, x      9    66.6667    85.7143    65.0794    3/3/0/0
 * 20    notech, x      9    55.5556    100.0000    58.7302    3/3/0/0
 * 27    VIP, x      9    44.4444    75.0000    46.2302    3/3/0/0
 * 28    Zoltan, x      6    66.6667    66.6667    65.0794    3/2/0/0
 * 11    FlxEx, x      6    66.6667    66.6667    61.1111    3/2/0/0
 * 22    Rakura, x      6    66.6667    57.1429    60.5159    3/2/0/0
 * 21    Pie_Master, x      6    55.5556    71.4286    50.7937    3/2/0/0
 * 16    Malum, x      6    55.5556    66.6667    55.5556    3/2/0/0
 * 14    kokonade1000, x      6    55.5556    62.5000    50.3968    3/2/0/0
 *  2    bgdp009, x      6    55.5556    50.0000    57.3413    3/2/0/0
 * 17    MayDay11, x      6    44.4444    66.6667    44.4444    3/2/0/0
 *  6    chann23, x      6    44.4444    62.5000    44.4444    3/2/0/0
 *  5    bubblewrap, x      6    44.4444    62.5000    42.0635    3/2/0/0
 * 29    Flucus, x      6    33.3333    80.0000    38.0952    2/2/0/0
 * 10    falcodevil, x      3    77.7778    42.8571    74.2857    3/1/0/0
 *  3    Boxes_O_Moxes, x      3    66.6667    42.8571    56.9444    3/1/0/0
 * 24    Sanctified, x      3    66.6667    33.3333    69.0476    2/0/0/1
 * 25    Taoist, x      3    55.5556    42.8571    52.7778    3/1/0/0
 *  9    ETCarch, x      3    55.5556    42.8571    50.0000    3/1/0/0
 *  4    brilk, x      3    55.5556    28.5714    58.7302    3/1/0/0
 * 26    tobon, x      3    44.4444    33.3333    49.2063    3/1/0/0
 * 19    Mikano, x      3    44.4444    33.3333    44.4444    3/1/0/0
 *  1    aloehart, x      3    44.4444    33.3333    43.0556    3/1/0/0
 * 15    Litanss, x      3    33.3333    42.8571    36.5079    3/1/0/0
 *  7    csrrcr, x      0    100.0000    0.0000    100.0000    1/0/0/0
 * 18    mefoo33, x      0    100.0000    0.0000    100.0000    1/0/0/0
 * 13    Hogger, x      0    75.0000    0.0000    75.0000    2/0/0/0
 * 23    rpitcher, x      0    55.5556    25.0000    52.7778    3/0/0/0
 *  8    darkknight0901, x      0    44.4444    0.0000    47.6190    3/0/0/0
 *
 * What you get out is an associative array like this:
 * array("Garlan" => 9, "notech" => 9, "VIP" => 9, "Zoltan" => 6, ...)
 */
function extractStandings($text) {
  $standings = array();
  $add_to_standings = function($matches) { $standings[$matches[2]] = $matches[3]; };
  regex_lines_map($text, "/^\s*[0-9]+\s+([0-9]+\s+)?([0-9a-z_.\- ]+),.*\s+([0-9]+)\s+/i", $add_to_standings);
  return $standings;
}

/**
 * Extracts standings from a paste of pairings.  The pairings format is shown in extractPairings docs.
 * Returns standings like extractStandings does.
 */
function standFromPairs($text) {
  $standings = array();
  $add_stands_from_pairs = function($matches) {
    $standings[$matches[2]] = $matches[3];
    $standings[$matches[5]] = $matches[4];
  };
  regex_lines_map($text, "/^\s*[0-9]+\s+([0-9]+\s+)?([0-9a-z_.\- ]+),.*\s+([0-9]+)-([0-9]+)\s+[0-9]+\s+([0-9a-z_.\- ]+),/i", $add_stands_from_pairs);
  return $standings;
}

/**
 * Extract names from a finals DCI-R text.  The DCI-R text looks like
 * --------------------------------
 *  1 (  12) Garlan, x
 *  8 (  29) Flucus, x
 * --------------------------------
 *  5 (  11) FlxEx, x
 *  4 (  27) VIP, x
 * --------------------------------
 *  3 (  21) Pie_Master, x
 *  6 (  17) MayDay11, x
 * --------------------------------
 *  7 (  14) kokonade1000, x
 *  2 (  28) Zoltan, x
 * --------------------------------
 *
 * What you get back is an array of the names.
 * [Garlan, Flucus, FlxEx, VIP, Pie_Master, MayDay11, kokonade1000, Zoltan]
 */
function extractFinals($text) {
  $finals = array();
  $add_to_finals = function($matches) { $finals[] = $matches[1]; };
  regex_lines_map($text, "/[\t ]+([0-9a-z_.\- ]+),/i", $add_to_finals);
  return $finals;
}

// $a and $b are the players in this round
// $next is an array of the next round's players
// returns the name of the winner from this round.
function detwinner($a, $b, $next) {
  if (in_array($a, $next)) { 
    return $a;
  }
  if (in_array($b, $next)) {
    return $b;
  }
  return "No Winner";
}

/**
 * Creates matches with the correct results from pasting the pairings and standings from
 * DCI-R, in the special format.
 *
 * @param event The event object to add the matches to
 * @param pairingTexts an array of text pastes from the DCI-R program for pairings.  See extractPairings for format.
 * @param standingsTexts an array of text pastes from the DCI-R program for standings. See extractStandings for format.
 * @param finalsTexts an array of finals text pastes. See extractfinals for format.
 * @param champion the winner of the tournament
 * @return the number of matches parsed and added
 */
function matchesFromDCIPastes($event, $pairingTexts, $standingsTexts, $finalsTexts, $champion) {
  $pairings = array();
  $standings = array();
  $matchesadded = 0;
  for($rnd = 0; $rnd < sizeof($pairingTexts); $rnd++) {
    $pairings[$rnd] = extractPairings($pairingTexts[$rnd]);
    if($rnd == 0) {
      $standings[$rnd] = standFromPairs($pairingTexts[$rnd + 1]);
    } else {
      $testStr = chop($standingsTexts[$rnd - 1]);
      if(strcmp($testStr, "") == 0) {
        $standings[$rnd] = standFromPairs($pairingTexts[$rnd + 1]);
      } else {
        $standings[$rnd] = extractStandings($standingsTexts[$rnd - 1]);
      }
    }
  }
  $sid = $event->mainid;
  $onlyfirstround = true;
  for ($rnd = 1; $rnd < sizeof($pairingTexts); $rnd++) {
    if (strlen($pairingTexts[$rnd]) > 0) {
      $onlyfirstround = false;
      break;
    }
  }
  if ($onlyfirstround) {
    for ($pair = 0; $pair < sizeof($pairings[0]); $pair++) {
      $event->addPlayer($pairings[0][$pair][0]);
      $event->addPlayer($pairings[0][$pair][1]);
    }
    $byeplayer = extractBye($pairingTexts[0]);
    if ($byeplayer) {
      $event->addPlayer($byeplayer);
    }
    return 0;
  }
  for($rnd = 0; $rnd < sizeof($pairings); $rnd++) {
    for($pair = 0; $pair < sizeof($pairings[$rnd]); $pair++) {
      $printrnd = $rnd + 1;
      $playerA = $pairings[$rnd][$pair][0];
      $playerB = $pairings[$rnd][$pair][1];
      $winner = "D";
      if($rnd == 0) {
        if(isset($standings[$rnd][$playerA]) &&
        $standings[$rnd][$playerA] > 1) {$winner = "A";}
        if(isset($standings[$rnd][$playerB]) &&
        $standings[$rnd][$playerB] > 1) {$winner = "B";}
      }
      else {
        if(isset($standings[$rnd][$playerA]) &&
        isset($standings[$rnd - 1][$playerA]) &&
        $standings[$rnd][$playerA] - $standings[$rnd - 1][$playerA]>1)
        {$winner = "A";}
        if(isset($standings[$rnd][$playerB]) &&
        isset($standings[$rnd - 1][$playerB]) &&
        $standings[$rnd][$playerB] - $standings[$rnd - 1][$playerB]>1)
        {$winner = "B";}
      }

      $event->addPlayer($playerA);
      $event->addPlayer($playerB);

      $event->addMatch($playerA, $playerB, $rnd+1, $winner, 0, 0);
      $matchesadded++;
    }
  }
  $finals = array();
  foreach ($finalsTexts as $pasteround) {
    $finals[] = extractFinals($pasteround);
  }
  // At this point, $finals is an array of arrays, with the matchings in each round, i.e.
  // [0][0] Alphie     [1][0] Betta     [2][0] Dave
  // [0][1] Betta
  // [0][2] Charlie    [1][1] Dave
  // [0][3] Dave

  $fid = $event->finalid;
  $win = "";
  $sec = "";
  $t4 = array();
  $t8 = array();
  for($round = 0; $round < sizeof($finals); $round++) {
    for($match = 0; $match < sizeof($finals[$round]); $match+=2) {
      $playerA = $finals[$round][$match];
      $playerB = $finals[$round][$match + 1];
      $event->addPlayer($playerA);
      $event->addPlayer($playerB);
      if ($round < sizeof($finals) - 1) {
        $winner = detwinner($playerA, $playerB, $finals[$round + 1]);
      } else {
        $winner = $champion;
      }
      $res = "D";
      if(strcmp($winner, $playerA) == 0) {$res = "A";}
      if(strcmp($winner, $playerB) == 0) {$res = "B";}
      $event->addMatch($playerA, $playerB, $round + 1 + $event->mainrounds, $res, 0, 0);
      $matchesadded++;
      $loser = (strcmp($winner, $playerA) == 0) ? $playerB : $playerA;
      if ($round == sizeof($finals) - 1) {
        $win = $winner;
        $sec = $loser;
      } elseif ($round == sizeof($finals) - 2) {
        $t4[] = $loser;
      } elseif($round == sizeof($finals) - 3) {
        $t8[] = $loser;
      }
    }
  }
  $event->setFinalists($win, $sec, $t4, $t8);
}

/****  DCI-R Version 2.9x PARSING ****/

function explode_dcir_lines($data) {
  $data = preg_replace("/
\n/", "\n", $data);
  return explode("\n", $data);
}

function dciregister($event, $data) {
  echo "Registering DCI-R players for {$event->name}.<br />";
  $lines = explode_dcir_lines($data);
  $ret = array();
  foreach ($lines as $line) {
    $tokens = explode(",", $line);
    if (preg_match("/\"(.*)\"/", $tokens[3], $matches)) {
      $didadd = $event->addPlayer($matches[1]); 
      if ($didadd) {
        echo "Added player: {$matches[1]}.<br />";
      } else {
        echo "{$matches[1]} could not be added (maybe already registered?).<br />";
      }
      $ret[] = $matches[1];
    }
  }
  return $ret;
}

function dciinputmatches($event, $reg, $data) {
  echo "Adding matches to {$event->name}.<br />";
  $lines = explode_dcir_lines($data);
  for($table = 0; $table < sizeof($lines)/6; $table++) {
    $offset = $table * 6;
    $numberofrounds = explode(",", $lines[$offset]);
    $playeraresults = explode(",", $lines[$offset + 1]);
    $playerbresults = explode(",", $lines[$offset + 2]);
    $playerawins = explode(",", $lines[$offset + 3]);
    $playerbwins = explode(",", $lines[$offset + 4]);
    for ($round = 1; $round <= sizeof($numberofrounds); $round++) {
      if ($numberofrounds[$round - 1] != 0) { 
        // find by name returns player object! not just a name!
        $playera = Player::findByName($reg[$playeraresults[$round - 1] - 1]);
        $playerb = Player::findByName($reg[$playerbresults[$round - 1] - 1]);
        // may want to write a custom function later that just returns name
        // should probably do a check to for NULL here for to see if player object
        // was in fact returned for playera and playerb, just in case the dciregister
        // function above failed to register
        $result = 'D';
        // TODO: need to do a check for a bye here
        if ($playerawins[$round - 1] > $playerbwins[$round - 1]) {$result = 'A';} // player A wins
        if ($playerbwins[$round - 1] > $playerawins[$round - 1]) {$result = 'B';} // player B wins
        echo "{$playera->name} vs {$playerb->name} in Round: {$round} and ";
        if ($result == 'A') {
          echo "{$playera->name} wins {$playerawins[$round - 1]} - {$playeralosses[$round - 1]}<br />";
        }
        if ($result == 'B') {
          echo "{$playerb->name} wins {$playerbwins[$round - 1]} - {$playerblosses[$round - 1]}<br />";
        }
        if ($result == 'D') {
          echo " match is a draw<br />";
        }
        $event->addMatch($playera->name, $playerb->name, $round, $result, $playerawins[$round - 1], $playerbwins[$round - 1]);
      }
    }
  }
}

function dciinputplayoffs($event, $reg, $data) {
  $lines = explode_dcir_lines($data);
  $ntables = $lines[0];
  $nrounds = log($ntables, 2);
  for($rnd = 1; $rnd <= $nrounds; $rnd++) {
    $ngames = pow(2, $nrounds - $rnd);
    for($game = 0; $game < $ngames; $game++) {
      $offset = 2 + $game*24;
      $playera = $lines[$offset + ($rnd-1)*3];
      $pbl = $offset + ($rnd-1)*3 + 12;
      $playerb = $lines[$pbl];
      $winner  = $lines[(($pbl+1)+ 3*$rnd - 6)/2 - 1];
      $pa = $reg[$playera - 1];
      $pb = $reg[$playerb - 1];
      $res = 'D';
      if ($winner == $playera) {
        $event->addMatch($pa, $pb, $rnd + $event->mainrounds, 'A', 2, 0);
      } else if ($winner == $playerb) {
        $event->addMatch($pa, $pb, $rnd + $event->mainrounds, 'B', 0, 2);
      }
    }
  }
  $event->assignTropiesFromMatches();
}

/**** DCI-R version 3.x PARSING ****/

function dci3register($event, $data) {
  $result = array();
  $lines = explode_dcir_lines($data);
  foreach ($lines as $line) {
    $table = explode("\t", $line);
    if (count($table) > 5) {
      $playernumber = $table[0];
      $playername = $table[5];
      $result[$playernumber] = $playername;
      $event->addPlayer($playername);
    }
  }
  return $result;
}

/** This comes from the XXXX305.dat file ($data305)
 * As far as we know, the format of this file is:
 * round number, opponent number, tourney points
 * in batches for each player, in numbers. so the first batch is for player number 0, etc.
 * We have a map of the players from the 302 file that we parsed in dci3register.
 */

/**
 * This comes from the XXXX303.dat file ($data)
 * As far as I know, the format of this file is:
 * round number, match number, player 1 number, player 2 number, player 1 wins, player 2 wins, draws
 *
 * match number is 0 when there wasn't a Nth match that round
 */
function dci3makematches($event, $data, $regmap) {
  $event = new Event($_POST['name']);
  $result = array();
  $lines = explode_dcir_lines($data);
  foreach ($lines as $line) {
    $table = explode(",", $line);
    $roundnum = $table[0];
    $matchnum = $table[1];
    $playeranum = $table[2];
    $playerbnum = $table[3];
    $playerawins = $table[4];
    $playerbwins = $table[5];
    if ($matchnum == 0) {
      continue;
    }
    if ($playerawins > $playerbwins) {
      $res = 'A';
    } else if ($playerawins < $playerbwins) {
      $res = 'B';
    } else {
      $res = 'D';
    }
    $event->addMatch($regmap[$playeranum], $regmap[$playerbnum], $roundnum, $res, $playerawins, $playerbwins);
  }
  $event->assignTropiesFromMatches();
}
