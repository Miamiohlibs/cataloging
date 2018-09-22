<?php
//https://raw.githubusercontent.com/rayvoelker/2015RoeschLibraryInventory/master/php/inventory_barcode.php


// sanitize the input
if ( isset($_GET['location']) )  {
	header("Content-Type: application/json");


	// barcodes are ONLY alpha-numeric ... strip anything that isn't this.
	$location = preg_replace("/[^a-zA-Z0-9\s]/", "", $_GET['location']);
	echo $location;
}
else{
	die();
}

/* // commenting out for testing

include file (item_barcode.php) supplies the following
arguments as the example below illustrates :
	$username = "username";
	$password = "password";

	$dsn = "pgsql:"
		. "host=sierra-db.school.edu;"
		. "dbname=iii;"
		. "port=1032;"
		. "sslmode=require;"
		. "charset=utf8;"
*/


//reset all variables needed for our connection
$username = null;
$password = null;
$dsn = null;
$connection = null;

require_once('sierra_cred.php');

//make our database connection
try {
	// $connection = new PDO($dsn, $username, $password, array(PDO::ATTR_PERSISTENT => true));
	$connection = new PDO($dsn, $username, $password);
}

catch ( PDOException $e ) {
	$row = null;
	$statement = null;
	$connection = null;

	echo "problem connecting to database...\n";
	error_log('PDO Exception: '.$e->getMessage());
	exit(1);
}

//set output to utf-8
$connection->query('SET NAMES UNICODE');

$sql = '
SELECT
-- i.inventory_gmt,
lower(p.barcode) as barcode,
upper(p.call_number_norm || COALESCE(' ' || v.field_content, '') ) as call_number_norm,
b.best_title,
i.location_code,
i.item_status_code,
s.content AS inventory_note,
to_timestamp(c.due_gmt::text, 'YYYY-MM-DD HH24:MI:SS') as due_gmt --some dates may require 24 hour time stamp; idk

FROM
sierra_view.item_record_property	AS p
JOIN
sierra_view.item_record			AS i
ON
  p.item_record_id = i.id

LEFT OUTER JOIN
sierra_view.subfield			AS s
ON
  (s.record_id = p.item_record_id) AND s.field_type_code = 'w'

LEFT OUTER JOIN
sierra_view.checkout			AS c
ON
  (i.record_id = c.item_record_id)

LEFT OUTER JOIN
sierra_view.varfield			AS v
ON
  i.id = v.record_id AND v.varfield_type_code = 'v'

LEFT JOIN
sierra_view.bib_record_item_record_link AS l
ON
  l.item_record_id = i.id

LEFT JOIN
sierra_view.bib_record_property as b
ON
  b.bib_record_id = l.bib_record_id

WHERE
i.location_code = '$location'
--   --comment out this section for items organized by title
-- AND
-- p.call_number_norm >= lower('PR 4879 L2 D83 2009')
-- AND
-- p.call_number_norm <= lower('PZ    7 B8163 WH19')


-- since we have the situation where multiple bibs can share the same item record, we should remove duplicated items.
-- we need to tweak this ... not sure grouping is the best way to do this.

-- group by
-- s.content,
-- c.due_gmt,
-- p.barcode, p.call_number_norm,
-- v.field_content,
-- l.items_display_order,
-- i.location_code, i.item_status_code

order by
--b.best_title ASC, --for periodicals which are sorted by title
p.call_number_norm ASC,
l.items_display_order ASC

--LIMIT 10000

';

$statement = $connection->prepare($sql);
$statement->execute();
$row = $statement->fetch(PDO::FETCH_ASSOC);

if($row["volume"]) {
	$row["call_number_norm"] = $row["call_number_norm"] .
		" " .
		normalize_volume($row["volume"]);
}

header('Content-Type: application/json');
echo json_encode($row);

$row = null;
$statement = null;
$connection = null;

 //remove after testing

?>
