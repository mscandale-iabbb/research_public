<?php

/*
 * 12/03/15 MJS - modified so iComplaintID accepts alpha characters for Scam Tracker records
 * 12/03/15 MJS - fixed layout by adding tab strip
 * 05/09/16 MJS - modified labels and added fields for Scam Tracker records, split address fields
 * 08/01/16 MJS - exclude certain scam miscellaneous fields
 * 09/19/17 MJS - added link to Similar Complaints page
 * 09/27/17 MJS - changed wording in link
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();

function ParseOutcome($json) {
	$array = json_decode($json, true);
	$vars = $array['Scam'];
	foreach ($vars as $field => $value) {
		if ($field == 'VictimNoContact') continue;
		if ($field == 'isVictim') continue;
		if ($field == 'Age') continue;
		if ($field == 'InitialContact') continue;
		$outcome .= $field . ": " . $value . "<br/>";
	}
	return $outcome;
}

$iBBBID = Numeric2($_GET['iBBBID']);
$iComplaintID = NoApost($_GET['iComplaintID']);

function ShowField($label, $value) {
	echo "
		<tr>
		<td class='labelback' width=15%>
		{$label}
		<td class='table_cell'>
		{$value}
		";
}

echo "
	<div class='main_section roundedborder'>
	<table class='report_table'>
	";
if ($_GET) {
	$query = "
		SELECT
			c.ComplaintID,
			c.ConsumerPrefix,
			c.ConsumerFirstName,
			c.ConsumerLastName,
			c.ConsumerSuffix,
			c.ConsumerStreetAddress,
			c.ConsumerStreetAddress2,
			c.ConsumerCity,
			c.ConsumerStateProvince,
			c.ConsumerPostalCode,
			c.ConsumerPhone,
			c.ConsumerEveningPhone,
			c.ConsumerFax,
			c.ConsumerEmail,
			t.ConsumerComplaint,
			t.DesiredOutcome,
			c.BusinessName,
			c.BusinessStreetAddress,
			c.BusinessCity,
			c.BusinessStateProvince,
			c.BusinessPostalCode,
			c.BusinessPhone,
			c.BusinessWebsite
		FROM BusinessComplaint c WITH (NOLOCK)
		left outer join BusinessComplaintText t WITH (NOLOCK) ON
			t.BBBID = c.BBBID AND t.ComplaintID = c.ComplaintID
		WHERE
			c.BBBID = '{$iBBBID}' and
			c.ComplaintID = '{$iComplaintID}'
		";

	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			$scam = false;
			if (strtolower(substr($fields[0],0,4)) == 'scam') $scam = true;
			$label = "Complaint ID";
			if ($scam) $label = "Scam ID";
			ShowField($label, $fields[0]);
			ShowField("Consumer name", $fields[1] . ' ' . $fields[2] . ' ' . $fields[3] . ' ' . $fields[4]);
			ShowField("Consumer street", $fields[5] . ' ' . $fields[6]);
			ShowField("Consumer city", $fields[7]);
			ShowField("Consumer state/province", $fields[8]);
			ShowField("Consumer postal code", $fields[9]);
			ShowField("Phone", $fields[10]);
			if (strlen($fields[11]) >= 7) ShowField("Evening phone", $fields[11] );
			if (strlen($fields[12]) >= 7) ShowField("Fax", $fields[12] );
			ShowField("Email", $fields[13]);
			ShowField("Narrative", strip_tags($fields[14]));
			$outcome = strip_tags($fields[15]);
			$label = "Desired outcome";
			if ($scam) $label = "Miscellaneous";
			if ($scam) $outcome = ParseOutcome($outcome);
			ShowField($label, $outcome);
			if ($scam) {
				ShowField("Business name", $fields[16]);
				ShowField("Business street", $fields[17]);
				ShowField("Business city", $fields[18]);
				ShowField("Business state/province", $fields[19]);
				ShowField("Business postal code", $fields[20]);
				ShowField("Business phone", $fields[21]);
				ShowField("Business website", $fields[22]);
			}
			else {
				ShowField("", "<a href='red_Similar_Complaints.php?iBBBID={$iBBBID}&iComplaintID={$iComplaintID}'>" .
					"Look for pattern in similar complaints against this company</a>");
			}
		}
	}
}
echo "
	<tr><td colspan=2 class='column_header thickpadding center'>
	<a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a>
	</table>
	</div>
	";

$page->write_pagebottom();

?>