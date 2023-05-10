<?php

/*
 * 05/09/16 MJS - new file
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();

$iBBBID = Numeric2($_GET['iBBBID']);
$iComplaintID = NoApost($_GET['iComplaintID']);

function ShowField($label, $value) {
	echo "
		<tr>
		<td class='labelback'>
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
	$query = "SELECT
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
			t.DesiredOutcome
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
			ShowField("Scam ID", $fields[0]);
			ShowField("Consumer name", $fields[1] . ' ' . $fields[2] . ' ' . $fields[3] . ' ' . $fields[4]);
			ShowField("Consumer address", $fields[5] . ' ' . $fields[6] . ' ' . $fields[7] . ' ' .
				$fields[8] . ' ' . $fields[9]);
			ShowField("Phone", $fields[10] );
			if (strlen($fields[11]) >= 7) ShowField("Evening phone", $fields[11] );
			if (strlen($fields[12]) >= 7) ShowField("Fax", $fields[12] );
			ShowField("Email", $fields[13]);
			ShowField("Narrative", strip_tags($fields[14]));
			ShowField("Desired outcome", strip_tags($fields[15]));
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