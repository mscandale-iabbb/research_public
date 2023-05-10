<?php

/*
 * 09/27/16 MJS - new file
 */

include '../intranet/init_server.php';

if ($_POST['iComplaintID']) {
	$result = UpdateRecord();
	echo $result;
}

function UpdateRecord() {
	global $conn;

	$iComplaintID = NoApost($_POST['iComplaintID']);
	$iChecked = NoApost($_POST['iChecked']);
	if ($iChecked == 'true') {
		$insert = "INSERT INTO BusinessComplaintChecked VALUES ('0000', '{$iComplaintID}') ";
		$r = $conn->execute($insert);
		//$data = $insert;
		$data = "Record {$iComplaintID} marked as reviewed";
	}
	else {
		$delete = "DELETE FROM BusinessComplaintChecked WHERE BBBID = '0000' AND ComplaintID = '{$iComplaintID}' ";
		$r = $conn->execute($delete);
		//$data = $delete;
		$data = "Record {$iComplaintID} marked as unreviewed";
	}

	return $data;
}

?>