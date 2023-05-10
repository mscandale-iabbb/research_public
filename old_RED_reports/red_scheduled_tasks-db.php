<?php

/*
 * 11/09/16 MJS - new file
 * 12/08/16 MJS - only show message about truncating if relevant
 * 12/08/16 MJS - stripped more html tags
 * 03/17/17 MJS - modified to use class for email
 * 05/16/19 MJS - used SETTINGS for org name
 */

include '../intranet/init_server.php';
include '../intranet/classes.php';  // for email classes

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// get POST from AngularJS for all POST calls
	$_POST = json_decode(file_get_contents('php://input'), true);

	$result = SendEmail();
}
else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$iType = NoApost($_GET['iType']);
	if ($iType == 'tasks') {
		$result = RetrieveTasks();
	}
}
echo $result;


function SendEmail() {
	global $conn, $SETTINGS;

	$iMessage = NoApost($_POST['iMessage']);
	$iRecipient = NoApost($_POST['iRecipient']);
	$iSubject = NoApost($_POST['iSubject']);
	$iReportName = NoApost($_POST['iReportName']);
	$iTesting = NoApost($_POST['iTesting']);


	$iMessage = RemoveHTMLSection($iMessage, "<head>");
	for ($i = 0; $i <= 3; $i++) {
		$iMessage = RemoveHTMLSection($iMessage, "<script>");
	}
	$iMessage = strip_tags($iMessage,"<br><div><p><span><table><td><th><tr>");

	if (strlen(trim(strip_tags($iMessage))) < 10) $iMessage =
		"<p><b>(No data for this report for your BBB this month)</b></p>";
	$iMessage = "
		<html>
		<head>
		<style>
		body {
			font-size: 12px;
			font-family: 'Montserrat', Verdana, Geneva, sans-serif;
		}
		table {
			width: 100%;
		}
		td {
			background-color: #EFEFEF;
			padding: 5px 5px 5px 5px;
		}
		</style>
		</head>
		<body>
		<p>
		Here is the report that you requested be automatically sent to you monthly.
		You can also run it <a href=https://bbb-services.bbb.org/intranet/{$iReportName}>here</a> at any time
		or select to stop receiving these monthly reports.
		</p>
		{$iMessage}
		</body>
		</html>
		";
	if (substr_count($iMessage,"<tr>") >= 500) $iMessage .=
		"<p><i>Note that list may be truncated to 500 records for the sake of space.</i><p>";

	if ($iTesting == '1') $iRecipient = "testing_" . $iRecipient . "_testing";

	$headers = "MIME-Version: 1.0" . "\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1" . "\n";
	$headers .= "From: Matthew Scandale <{$SETTINGS['ADMIN_EMAIL_ADDRESS']}>\n";
	$headers .= "Bcc: {$SETTINGS['ADMIN_EMAIL_ADDRESS']}\n";

	//mail($iRecipient, $iSubject, $iMessage, $headers);
	$e = new email();
	$e->email_to = $iRecipient;
	$e->email_from = $SETTINGS['ADMIN_EMAIL_ADDRESS'];
	$e->email_subject = $iSubject;
	$e->email_body = $iMessage;
	$e->email_bcc = $SETTINGS['ADMIN_EMAIL_ADDRESS'];
	$e->send();

	$data['message'] = $iMessage;
	return json_encode($data);
}

function RetrieveTasks() {
	global $conn, $DB_SERVER_1, $SETTINGS;

	$query = "
		SELECT
			'BBB ' + BBB.NicknameCity,
			t.UserName,
			t.ReportFilename,
			BBB.BBBID,
			t.DateCreated
		from tblDataWarehouseTasks t WITH (NOLOCK)
		left outer join {$DB_SERVER_1}.AuthenticateDB.dbo.aspnet_Membership m WITH (NOLOCK) on
			m.LoweredEmail = t.UserName
		left outer join BBB WITH (NOLOCK) on BBB.BBBID = m.BBBID and BBB.BBBBranchID = 0
		inner join tblDataWarehouseReports r WITH (NOLOCK) on
			r.ReportFileName = t.ReportFileName
		WHERE
			/*ReportFileName = '{$iSection}' and*/
			BBB.BBBID is not null and
			t.UserName != '{$SETTINGS['ADMIN_EMAIL_ADDRESS']}'
		order by t.UserName
		";
	$rawtasks = $conn->execute($query);
	$tasks = $rawtasks->GetArray();
	foreach ($tasks as $k => $fields) {
		$oBBB = $fields[0];
		$oUser = $fields[1];
		$oReportName = "red_" . $fields[2] . ".php";
		$oTitle = str_replace("_"," ",$fields[2]);
		$oBBBID = $fields[3];
		$oCreated = FormatDate($fields[4]);
		$result[] = [
			'oBBB' => $oBBB,
			'oUser' => $oUser,
			'oReportName' => $oReportName,
			'oTitle' => $oTitle,
			'oBBBID' => $oBBBID,
			'oCreated' => $oCreated
		];
	}
	return json_encode($result);
}

function RemoveHTMLSection($iMessage, $iTag) {
	if (stripos($iMessage,$iTag) === False) return $iMessage;
	$iEndTag = "</" . substr($iTag, 1);
	$start_pos = strpos($iMessage,$iTag);
	$end_pos = strpos($iMessage,$iEndTag) + strlen($iEndTag) - 1;
	$iMessage = substr($iMessage, 0, $start_pos) . substr($iMessage, $end_pos + 1);
	return $iMessage;
}

?>