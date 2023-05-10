<?php

/*
 * 07/11/18 MJS - new file
 * 07/16/18 MJS - added decoding
 * 08/02/18 MJS - this will need to change for new schema
 * 08/03/18 MJS - rewrote for new schema
 * 08/03/18 MJS - added fields
 * 08/17/18 MJS - removed column Experience Type
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();


$iBBBID = Numeric2($_GET['iBBBID']);
$iCustomerReviewID = NoApost($_GET['iCustomerReviewID']);

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
			cr.CustomerReviewID,
			ltrim(cr.ConsumerFirstName),
			ltrim(cr.ConsumerLastName),
			cr.ConsumerPostalCode,
			ltrim(cr.ConsumerEmail),
			ltrim(t.CustomerReviewText),
			'',
			cr.RecommendLevel,
			case when cr.IsCertified = '1' then 'Yes' else 'No' end,
			ltrim(t.BusinessResponseDetail),
			cr.DateBusinessResponded,
			ltrim(t.CustomerResponseDetail),
			cr.DateCustomerResponded,
			ltrim(t.BusinessRebuttalDetail),
			cr.DateBusinessRebutted,

			cr.DateReceived,
			case when cr.IsPublished = '1' then 'Yes' else 'No' end,
			cr.Stars,
			cr.ConsumerPhone,
			cr.ConsumerIPAddress,
			ltrim(cr.ConsumerDisplayName)
		FROM BusinessCustomerReview cr
		INNER JOIN Business b WITH (NOLOCK) on b.BBBID = cr.BBBID and b.BusinessID = cr.BusinessID
		LEFT OUTER JOIN BusinessCustomerReviewText t WITH (NOLOCK) on t.BBBID = cr.BBBID and t.CustomerReviewID = cr.CustomerReviewID
		inner join BBB WITH (NOLOCK) on b.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			b.BBBID = '{$iBBBID}' and
			cr.CustomerReviewID = '{$iCustomerReviewID}'
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			ShowField("Customer review ID", $fields[0]);
			ShowField("Consumer name", $fields[1] . ' ' . $fields[2] . ' - ' . $fields[20]);
			ShowField("Consumer postal code", $fields[3]);
			ShowField("Consumer email", $fields[4]);
			ShowField("Consumer phone", $fields[18]);
			ShowField("Consumer IP address", $fields[19]);

			ShowField("Stars", $fields[17]);
			ShowField("Recommend level", $fields[7]);
			ShowField("Certified", $fields[8]);
			ShowField("Published", $fields[16]);

			ShowField("Text", strip_tags($fields[5]));
			ShowField("Date received", FormatDate($fields[15]));

			//$string_decoded = shell_exec("echo '{$fields[9]}' | base64 -d | gunzip");
			ShowField("Business response", $fields[9]);
			$oDateBusinessResponded = FormatDate($fields[10]);
			if ($oDateBusinessResponded == '01/01/1900') $oDateBusinessResponded = '';
			ShowField("Date business responded", $oDateBusinessResponded);

			//$string_decoded = shell_exec("echo '{$fields[11]}' | base64 -d | gunzip");
			ShowField("Customer response", $fields[11]);
			$oDateCustomerResponded = FormatDate($fields[12]);
			if ($oDateCustomerResponded == '01/01/1900') $oDateCustomerResponded = '';
			ShowField("Date customer responded", $oDateCustomerResponded);

			//$string_decoded = shell_exec("echo '{$fields[13]}' | base64 -d | gunzip");
			ShowField("Business rebuttal", $fields[13]);
			$oDateBusinessRebutted = FormatDate($fields[14]);
			if ($oDateBusinessRebutted == '01/01/1900') $oDateBusinessRebutted = '';
			ShowField("Date business rebutted", $oDateBusinessRebutted);
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