<?php
//https://raw.githubusercontent.com/rayvoelker/2015RoeschLibraryInventory/master/php/inventory_barcode.php
function left_pad_number($number, $pad_amount) {
	//returns a string value of a number padded out to the maximum length of the $pad_amount

	//if the length of the number is the same or greater than the pad_amount, just return the number unpadded
	if ( strlen($number) >= $pad_amount ) {
		return $number;
	}

	$result = array();
	$number = array_map('intval', str_split($number));

	//pop off values from the end of number and push them onto the $result stack
	while ($number) {
		array_push($result, array_pop($number) );
	}

	while ( count($result) < $pad_amount ) {
		array_push($result, " ");
	}

	$result = array_reverse($result);
	$string = implode('', $result);

	return $string;
}

function normalize_volume($string_data) {
	//will return a string formatted to sort properly among other volumes
	// For example:
	// given a volume number:
	// "v.1"
	// will return:
	// "v.    1"

	// given a volume number:
	// "v.11"
	// will return:
	// "v.   11"

	$return_string = "";
	$len = strlen($string_data);

	//split everything that is a number, and everything that is not a number into $matches
	$regex = "/[0-9]+|[^0-9]+/";
	preg_match_all($regex, $string_data, $matches);

	for($i=0; $i<count($matches[0]); $i++) {
		if ( is_numeric ($matches[0][$i]) ) {
			$matches[0][$i] = left_pad_number($matches[0][$i], 5);
		}
	}

	$string = implode('', $matches[0]);

	return $string;
} //end function normalize_callnumber


// sanitize the input
if ( isset($_GET['barcode']) )  {
	header("Content-Type: application/json");
	// ensure that the barcode value is formatted somewhat sanely
	if( strlen($_GET['barcode']) > 14 ) {
		//we don't expect barcodes to be longer than 12 alpha-numeric characters
		//although, 99.9 % of our scannable barcodes are 10 digit, I'm leaving some breathing room
		echo "{}";
		die();
	}
	// barcodes are ONLY alpha-numeric ... strip anything that isn't this.
	$barcode = preg_replace("/[^a-zA-Z0-9]/", "", $_GET['barcode']);
}
else{
	die();
}

/*

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
i.location_code = \'' . $barcode . '\'
--   --comment out this section for items organized by title
-- AND
-- p.call_number_norm >= lower('BF   77 U53 1992 SE')
-- AND
-- p.call_number_norm <= lower('TX  335 L69 1990 TR BOOK')


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



header('Content-Type: application/json');
echo json_encode($row);

$row = null;
$statement = null;
$connection = null;
?>
