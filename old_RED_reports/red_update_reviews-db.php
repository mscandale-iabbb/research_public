<?php

/*
 * 07/12/18 MJS - new file
 * 08/02/18 MJS - no longer needed
 */


return;



include '../intranet/init_server.php';
include '../intranet/classes.php';  // for email classes

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// get POST from AngularJS for all POST calls
	$_POST = json_decode(file_get_contents('php://input'), true);

}
else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$iType = NoApost($_GET['iType']);
	if ($iType == 'tasks') {
		$result = RetrieveTasks();
	}
	else if ($iType == 'run') {
		$result = RunTask();
	}
}
echo $result;


function RetrieveTasks() {
	global $conn;

	$query = "
		select count(*)
		from Core.dbo.datOrgCustomerReview cr
		left outer join CDW.dbo.BusinessCustomerReviewText crt WITH (NOLOCK) on crt.OrgReviewID = cr.OrgReviewID
		where
			ReviewText like 'H4%' and
			crt.OrgReviewID is null /* ONLY ONES THAT HAVE BLANK TEXT */
		";
	$rawtasks = $conn->execute($query);
	$tasks = $rawtasks->GetArray();
	foreach ($tasks as $k => $fields) {
		$oOrgReviewID = $fields[0];
		$result[] = [
			'oCount' => $oOrgReviewID
		];
	}
	return json_encode($result);
}

function RunTask() {
	global $conn;
	$iCount = NoApost($_GET['iCount']);
	if (! $iCount) return;

	$query = "
		select top {$iCount}
			cr.ReviewText,
			cr.OrgReviewID
		from Core.dbo.datOrgCustomerReview cr
		left outer join CDW.dbo.BusinessCustomerReviewText crt WITH (NOLOCK) on crt.OrgReviewID = cr.OrgReviewID
		where
			ReviewText like 'H4%' and
			crt.OrgReviewID is null /* ONLY ONES THAT HAVE BLANK TEXT */
		";
	$rawtask = $conn->execute($query);
	$task = $rawtask->GetArray();
	$log = 0;
	foreach ($task as $k => $fields) {
		$text_raw = $fields[0];
		$iOrgReviewID = $fields[1];

		$text_decoded = shell_exec("echo '{$text_raw}' | base64 -d | gunzip");
		$text_decoded = CleanString($text_decoded);
	
		//echo $text_decoded;
	
		$insert = "INSERT INTO BusinessCustomerReviewText (OrgReviewID, CustomerReviewText, DateCreated) VALUES ('{$iOrgReviewID}', '{$text_decoded}', GETDATE() );";
		$conn->execute($insert);
		$log++;
	}
	return json_encode($log);

}

function CleanString($text) {
	$text = str_replace("\n", " ", $text);
	$text = str_replace("\r", " ", $text);
	$text = str_replace("\t", " ", $text);
	while (strpos($text, "  ") !== false) {
		$text = str_replace("  ", " ", $text);
	}
	$text = trim($text);
	$text = str_replace("'", "`", $text);
	$text = str_replace("\"", "`", $text);
	return $text;
}

?>