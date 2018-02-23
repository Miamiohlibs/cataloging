<?php

$host = "10.3.9.185";
$port = "1032";
$dbname = "iii";
$user="sierra_sql2";
$pass="CxInJ50Ew5kF";
$options = "sslmode=require";
// Connecting, selecting database
$dbconn = new PDO("pgsql:dbname=$dbname;host=$host;port=$port",$user,$pass)
    or die("Could not connect: " . pg_last_error());


// Free resultset
pg_free_result($result);

// Closing connection
pg_close($dbconn);
?>
