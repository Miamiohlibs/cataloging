<?php
// keep file; it is an include file listed for any barcode, inventory, or receipt.php scripts


function userPass() {

	$array = array(
		"user1" => "pass1",
		"user2" => "pass2",
		"user3" => "pass3",
		"user4" => "pass4",
		"user5" => "pass5",
);

	$numb = rand(1,5);

	foreach ($array as $user => $pass) {
		if ($user == "inventory" . $numb) {
			return array($user, $pass);
		}
	};
	
}
?>
