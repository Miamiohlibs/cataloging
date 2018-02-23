<?php

$host = "";
$port = "";
$dbname = "";
$user="";
$pass="";
$credentials = "user=user password=pass";
$options = "sslmode=require";
// Connecting, selecting database
$dbconn = new PDO("pgsql:dbname=$dbname;host=$host;port=$port",$user,$pass)
    or die("Could not connect: " . pg_last_error());

// Performing SQL query
$query = '
SELECT
*
FROM
sierra_view.phrase_entry as p
WHERE
p.index_tag = \'i\'
AND
p.varfield_type_code = \'i\'
LIMIT 10
';

$result = $dbconn->query($query) or die('Query failed: ' . pg_last_error());

// Printing results in HTML
echo "<table>\n";
while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    echo "\t<tr>\n";
    foreach ($line as $col_value) {
        echo "\t\t<td>$col_value</td>\n";
    }
    echo "\t</tr>\n";
}
echo "</table>\n";

// Free resultset
pg_free_result($result);

// Closing connection
pg_close($dbconn);
?>
