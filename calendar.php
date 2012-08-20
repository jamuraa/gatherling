<?php require_once 'lib.php';

header("Content-Type: text/calendar");

print "BEGIN:VCALENDAR\r\n";
print "VERSION:2.0\r\n";
print "PRODID:-//{$CONFIG['site_name']}/gatherling//EN\r\n";
print "X-WR-CALNAME;VALUE=TEXT:{$CONFIG['site_name']} Tournament Schedule\r\n";
print "X-WR-CALDESC;VALUE=TEXT:{$CONFIG['calendar_description']}\r\n";
print "X-WR-RELCALID:480fC555:F784:4C19:9D38:A65F931880AB\r\n";
print "X-WR-TIMEZONE:US/Eastern\r\n";
print "BEGIN:VTIMEZONE\r\n";
print "TZID:US/Eastern\r\n";
print "X-LIC-LOCATION:US/Eastern\r\n";
print "BEGIN:STANDARD\r\n";
print "TZOFFSETFROM:-0400\r\n";
print "TZOFFSETTO:-0500\r\n";
print "TZNAME:EST\r\n";
print "DTSTART:19700308T020000\r\n";
print "END:STANDARD\r\n";
print "BEGIN:DAYLIGHT\r\n";
print "TZOFFSETFROM:-0500\r\n";
print "TZOFFSETTO:-0400\r\n";
print "TZNAME:EDT\r\n";
print "DTSTART:19701101T020000\r\n";
print "END:DAYLIGHT\r\n";
print "END:VTIMEZONE\r\n";

function printEventIcal($eventstart, $eventname, $eventlink = "") {
  $timeStartFormatted = date('Ymd\THis', $eventstart);
  // All events will last for 5 hours for now.
  $timeEndFormatted = date('Ymd\THis', $eventstart + (60 * 60 * 5));
  print "BEGIN:VEVENT\r\n";
  print "DTSTART:{$timeStartFormatted}\r\n";
  print "DTEND:{$timeEndFormatted}\r\n";
  print "SUMMARY:{$eventname}\r\n";
  if (strcmp($eventlink, "") != 0) {
     print "URL:{$eventlink}\r\n";
  }
  print "END:VEVENT\r\n";
}

// The last 50 ones.

$db = Database::getConnection();

$result = $db->query("SELECT UNIX_TIMESTAMP(DATE_SUB(start, INTERVAL 30 MINUTE)) as d, format, series, name, threadurl FROM events WHERE start < NOW() ORDER BY start DESC LIMIT 50");

$result or die($db->error);

while ($row = $result->fetch_assoc()) {
  printEventIcal($row['d'], $row['name'], $row['threadurl']);
}

$result->close();

// And all of the ones that haven't happened yet.

$result = $db->query("SELECT UNIX_TIMESTAMP(DATE_SUB(start, INTERVAL 30 MINUTE)) as d, format, series, name, threadurl FROM events WHERE start > NOW() ORDER BY start ASC");

$result or die($db->error);

while ($row = $result->fetch_assoc()) {
  printEventIcal($row['d'], $row['name'], $row['threadurl']);
}

?>
END:VCALENDAR

