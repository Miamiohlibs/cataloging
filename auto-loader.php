<?php
/*
Purpose of this script is to provide a basic output for subject selectors to weed collections

Sample URL: http://ulblwebt02.lib.miamioh.edu/~bomanca/collection/floyd.php?location=scr&start=AY%20%20%2067%20N5%20W7%20%202005&end=PN%20%20171%20F56%20W35%201998
*/

require_once('getdb.php');

$db_handle = getdb(); //call the db function

$start = preg_replace("/[^a-zA-Z0-9]/", "", $_GET['start']);
$end = preg_replace("/[^a-zA-Z0-9]/", "", $_GET['end']);
$location = preg_replace("/[^a-zA-Z0-9]/", "", $_GET['location']);

$query = "
SELECT
--count(*)
DISTINCT m.record_type_code || m.record_num	AS item_record_num,
i.item_status_code || ' ' || sn.name AS item_status,
-- Copy --not sure we can get copy
p.call_number_norm,
-- Volume
b.best_author,
b.best_title,
b.publish_year,
i.last_checkout_gmt,
i.last_checkin_gmt,
i.checkout_total,
i.internal_use_count,
i.renewal_total,
m.creation_date_gmt::date


FROM
  sierra_view.item_record_property 	AS p
JOIN
  sierra_view.phrase_entry 		AS e
ON
  e.record_id = p.item_record_id

JOIN
  sierra_view.item_record 		AS i
ON
  p.item_record_id = i.id

JOIN
  sierra_view.item_status_property 	AS s
ON
  s.code = i.item_status_code

JOIN
  sierra_view.item_status_property_name	AS sn
ON
  s.id = sn.item_status_property_id

JOIN
  sierra_view.record_metadata		AS m
on
  i.record_id = m.id


LEFT OUTER JOIN
  sierra_view.varfield			AS v
ON
  i.id = v.record_id

LEFT JOIN
  sierra_view.bib_record_item_record_link AS l
ON
  l.item_record_id = i.id

LEFT JOIN
  sierra_view.bib_record_property 	AS b
ON
  b.bib_record_id = l.bib_record_id


WHERE
m.campus_code = ''

AND

--i.location_code = 'scr' --test
i.location_code = '$location'  --production

  --comment out this section for items organized by title
AND
--test
--p.call_number_norm BETWEEN lower('AY   67 N5 W7  2005') AND lower('PN  171 F56 W35 1998')
--production
p.call_number_norm BETWEEN lower('$start') AND lower('$end')

--LIMIT 100 --test
ORDER BY
p.call_number_norm ASC
";


$result = pg_query($db_handle, $query);

$rows = array();
while($r = pg_fetch_row($result)) {
      $rows[] = $r;
 }
echo json_encode($rows);

 pg_free_result($result);
//deprecated pg_close($db_handle);
?>
