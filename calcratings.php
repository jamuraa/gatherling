<?php
include 'lib.php';

$db = Database::getConnection();
$db->query("DELETE FROM ratings") or die($db->error);

$compQuery = "SELECT name, start FROM events ORDER BY start";
$futexQuery = "SELECT name, start FROM events WHERE format=\"Future Extended\" ORDER BY start";
$classicQuery = "SELECT name, start FROM events WHERE format=\"Classic\" ORDER BY start";
$stdQuery= "SELECT name, start FROM events WHERE format=\"Standard\" ORDER BY start";
$otherQuery= "SELECT name, start FROM events WHERE format!=\"Standard\" AND format!=\"Future Extended\" AND format!=\"Classic\" ORDER BY start";
$modernQuery = "SELECT name, start FROM events WHERE START >='2007-10-29' ORDER BY start";
$xpdcQuery= "SELECT name, start FROM events WHERE season=1 and series=\"XPDC\" ORDER BY start";

calcRating("Composite", $compQuery);
calcRating("Classic", $classicQuery);
calcRating("Future Extended", $futexQuery);
calcRating("Standard", $stdQuery);
calcRating("Other Formats", $otherQuery);
calcRating("Modern", $modernQuery);
calcRating("XPDC Season 1", $xpdcQuery);

function calcRating($format, $query) {
  global $db;
  $db->query($query) or die($db->error);
  $result = mysql_query($query, $db) or die(mysql_error());
  while($row = $result->fetch_assoc()) {
    $event = $row['name'];
    $players = calcPostEventRatings($event, $format);
    insertRatings($players, $format, $row['start']);
  }
  mysql_free_result($result);
}

function insertRatings($players, $format, $date) {
  global $db;
  foreach($players as $player=>$data) {
    $rating = $data['rating'];
    $wins = $data['wins']; $losses = $data['losses'];
    $stmt = $db->prepare("INSERT INTO ratings VALUES(?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssdd", $player, $rating, $format, $date, $wins, $losses);
    $stmt->execute() or die($stmt->error);
    $stmt->close();
  }
}

function calcPostEventRatings($event, $format) {
  global $db;
  $players = getEntryRatings($event, $format);
  $matches = getMatches($event);
  foreach ($matches as $match) {
    $aPts = 0.5; $bPts = 0.5;
    if(strcmp($match['result'], 'A') == 0) {
      $aPts = 1.0; $bPts = 0.0;
      $players[$match['playera']]['wins']++;
      $players[$match['playerb']]['losses']++;
    }
    elseif(strcmp($match['result'], 'B') == 0) {
      $aPts = 0.0; $bPts = 1.0;
      $players[$match['playerb']]['wins']++;
      $players[$match['playera']]['losses']++;
    }
    $newA = newRating($players[$match['playera']]['rating'],
      $players[$match['playerb']]['rating'],
      $aPts, $match['kvalue']);
    $newB = newRating($players[$match['playerb']]['rating'],
      $players[$match['playera']]['rating'],
      $bPts, $match['kvalue']);

    $players[$match['playera']]['rating'] = $newA;
    $players[$match['playerb']]['rating'] = $newB;
  }
  return $players;
}

function newRating($old, $opp, $pts, $k) {
  $new = $old + ($k * ($pts - winProb($old, $opp)));
  if($old < $new) {$new = ceil($new);}
  elseif($old > $new) {$new = floor($new);}
  return $new;
}

function winProb($rating, $oppRating) {
  return 1/(pow(10, ($oppRating - $rating)/400) + 1);
}

function getMatches($event) {
  global $db;
  $stmt = $db->prepare("SELECT LCASE(m.playera) AS playera, LCASE(m.playerb) AS playerb, m.result, e.kvalue
    FROM matches AS m, subevents AS s, events AS e
    WHERE m.subevent=s.id AND s.parent=e.name AND e.name = ?
    ORDER BY s.timing, m.round");
  $stmt->bind_param("s", $event);
  $stmt->execute();
  $stmt->bind_result($playera, $playerb, $result, $kvalue);
  $data = array();
  while ($stmt->fetch()) {
    $data[] = array('playera' => $playera,
                    'playerb' => $playerb,
                    'result' => $result,
                    'kvalue' => $kvalue);
  }
  $stmt->close();
  return $data;
}

function getEntryRatings($event, $format) {
  global $db;
  $stmt = $db->prepare("SELECT LCASE(n.player) AS player, r.rating, q.qmax, r.wins, r.losses
    FROM entries AS n
    LEFT OUTER JOIN ratings AS r ON r.player = n.player
    LEFT OUTER JOIN
    (SELECT qr.player AS qplayer, MAX(qr.updated) AS qmax
     FROM ratings AS qr, events AS qe
     WHERE qr.updated<qe.start AND qe.name = ? AND qr.format = ?
     GROUP BY qr.player) AS q
    ON q.qplayer=r.player
    WHERE n.event = ? AND ((q.qmax=r.updated AND q.qplayer=r.player AND r.format = ?)
         OR q.qmax IS NULL)
    GROUP BY n.player ORDER BY n.player");
  $stmt->bind_param("ssss", $event, $format, $event, $format);
  $stmt->execute();
  $stmt->bind_result($player, $rating, $qmax, $wins, $losses);
  $data = array();
  while ($stmt->fetch()) {
    $datum = array();
    if(!is_null($qmax)) {
      $datum['rating'] = $rating;
      $datum['wins'] = $wins;
      $datum['losses'] = $losses;
    } else {
      $datum['rating'] = 1600;
      $datum['wins'] = 0;
      $datum['losses'] = 0;
    }
    $data[$player] = $datum;
  }
  $stmt->close();
  return $data;
}

?>
