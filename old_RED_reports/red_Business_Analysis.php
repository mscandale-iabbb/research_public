<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 12/16/15 MJS - ensured Scam Tracker records won't appear, cleaned up code
 * 02/10/16 MJS - added section for No Records Found
 * 04/20/16 MJS - locked vendors out
 * 08/25/17 MJS - cleaned up code
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckBBBOnly($BBBID);


$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iXField = NoApost($_POST['iXField']);
$iMinX = Numeric2($_REQUEST['iMinX']);
if (! $iMinX) $iMinX = 0;
$iMaxX = Numeric2($_REQUEST['iMaxX']);
if (! $iMaxX) $iMaxX = 999999;
$iYField = NoApost($_POST['iYField']);
$iMinY = Numeric2($_REQUEST['iMinY']);
if (! $iMinY) $iMinY = 0;
$iMaxY = Numeric2($_REQUEST['iMaxY']);
if (! $iMaxY) $iMaxY = 999999;
$iAB = NoApost($_REQUEST['iAB']);
$iMaxRecs = Numeric2($_REQUEST['iMaxRecs']);
if (! $iMaxRecs) $iMaxRecs = 1000;
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$xfields = array(
	'Employees' => 'b.NumberOfEmployees',
	'Employees (D&B)' => 'd.EMPLOYEES_HERE',
	'Rating Score' => 'b.BBBRatingScore',
	'Revenue (D&B)' => 'd.SALES',
	'Size' => 's.SizeOfBusinessSortOrder',
	'Years in Business' => 'ABS(DATEDIFF(year, GETDATE(), b.DateBusinessStarted))',
	'Years Accredited' => 'ABS(DATEDIFF(year, GETDATE(), p.DateFrom))',
	'Complaints Last Year' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and c.DateClosed >= GETDATE() - 365 and " .
		"c.ComplaintID not like 'scam%')",
	'Complaints Last 3 Years' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and c.DateClosed >= GETDATE() - 1095 and " .
		"c.ComplaintID not like 'scam%')",
	'Unresolved Complaints Last Year' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and DateClosed >= GETDATE() - 365 and " .
		"(c.CloseCode = 112 or c.CloseCode = 120 or c.CloseCode = 200) and " .
		"c.ComplaintID not like 'scam%')",
	'Unresolved Complaints Last 3 Years' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and DateClosed >= GETDATE() - 1095 and " .
		"(c.CloseCode = 112 or c.CloseCode = 120 or c.CloseCode = 200) and " .
		"c.ComplaintID not like 'scam%')",
	'Inquiries Last Year' => "(select sum(counttotal) from BusinessInquiry i where " .
		"i.BBBID = b.BBBID and i.BusinessID = b.BusinessID and DateOfInquiry >= GETDATE() - 365)",
	'Inquiries Last 3 Years' => "(select sum(counttotal) from BusinessInquiry i where " .
		"i.BBBID = b.BBBID and i.BusinessID = b.BusinessID and DateOfInquiry >= GETDATE() - 1095)",
	'Ad Reviews Last Year' => "(select count(*) from BusinessAdReview a where " .
		"a.BBBID = b.BBBID and a.BusinessID = b.BusinessID and a.DateClosed >= GETDATE() - 365)",
	'Ad Reviews Last 3 Years' => "(select count(*) from BusinessAdReview a where " .
		"a.BBBID = b.BBBID and a.BusinessID = b.BusinessID and a.DateClosed >= GETDATE() - 1095)",
);
$input_form->AddSelectField('iXField', 'Analyze field', $iXField, $xfields );
$input_form->AddTextField('iMinX', 'With values from', $iMinX, "width:75px;", '', 'number');
$input_form->AddTextField('iMaxX', ' to ', $iMaxX, "width:75px;", 'sameline', 'number');
$yfields = array(
	'Rating Score' => 'b.BBBRatingScore',
	'Employees' => 'b.NumberOfEmployees',
	'Employees (D&B)' => 'd.EMPLOYEES_HERE',
	'Revenue (D&B)' => 'd.SALES',
	'Size' => 's.SizeOfBusinessSortOrder',
	'Years in Business' => 'ABS(DATEDIFF(year, GETDATE(), b.DateBusinessStarted))',
	'Years Accredited' => 'ABS(DATEDIFF(year, GETDATE(), p.DateFrom))',
	'Complaints Last Year' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and c.DateClosed >= GETDATE() - 365)",
	'Complaints Last 3 Years' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and c.DateClosed >= GETDATE() - 1095)",
	'Unresolved Complaints Last Year' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and DateClosed >= GETDATE() - 365 and " .
		"(c.CloseCode = 112 or c.CloseCode = 120 or c.CloseCode = 200))",
	'Unresolved Complaints Last 3 Years' => "(select count(*) from BusinessComplaint c where " .
		"c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and DateClosed >= GETDATE() - 1095 and " .
		"(c.CloseCode = 112 or c.CloseCode = 120 or c.CloseCode = 200))",
	'Inquiries Last Year' => "(select sum(counttotal) from BusinessInquiry i where " .
		"i.BBBID = b.BBBID and i.BusinessID = b.BusinessID and DateOfInquiry >= GETDATE() - 365)",
	'Inquiries Last 3 Years' => "(select sum(counttotal) from BusinessInquiry i where " .
		"i.BBBID = b.BBBID and i.BusinessID = b.BusinessID and DateOfInquiry >= GETDATE() - 1095)",
	'Ad Reviews Last Year' => "(select count(*) from BusinessAdReview a where " .
		"a.BBBID = b.BBBID and a.BusinessID = b.BusinessID and a.DateClosed >= GETDATE() - 365)",
	'Ad Reviews Last 3 Years' => "(select count(*) from BusinessAdReview a where " .
		"a.BBBID = b.BBBID and a.BusinessID = b.BusinessID and a.DateClosed >= GETDATE() - 1095)",
);
$input_form->AddSelectField('iYField', 'With additional field', $iYField, $yfields );
$input_form->AddTextField('iMinY', 'With values from', $iMinY, "width:75px;", '', 'number');
$input_form->AddTextField('iMaxY', ' to ', $iMaxY, "width:75px;", 'sameline', 'number');
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
//$input_form->AddPagingOption();
$input_form->AddTextField('iMaxRecs', 'Records to analyze', $iMaxRecs, "width:75px;", '', 'number');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT TOP {$iMaxRecs}
			{$iXField},
			{$iYField}
		FROM Business b WITH (NOLOCK)
		LEFT OUTER JOIN DandB d WITH (NOLOCK) ON
			d.BBBID = b.BBBID and d.BusinessID = b.BusinessID
		LEFT OUTER JOIN BusinessProgramParticipation p WITH (NOLOCK) ON
			p.BBBID = b.BBBID and p.BusinessID = b.BusinessID and
			(p.BBBProgram = 'Membership' or p.BBBProgram = 'BBB Accredited Business')
		LEFT OUTER JOIN tblSizesOfBusiness s WITH (NOLOCK) on
			s.SizeOfBusiness = b.SizeOfBusiness
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			{$iXField} >= {$iMinX} and
			{$iXField} <= {$iMaxX} and
			{$iYField} >= {$iMinY} and
			{$iYField} <= {$iMaxY} and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();

	// tabular report
	if (count($rs) > 0 && $output_type == "excel") {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array($iXField, ''),
				array($iYField, ''),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
				)
			);
		}
		if ($iShowSource) {
			$report->WriteSource($query);
		}
		$report->Close('suppress');

		reset($rs);
	}

	// scatter plot
	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals0[] = $fields[0];
		}
		foreach ($rs as $k => $fields) {
			$vals1[] = $fields[1];
		}

		$scatterplot = new scatterplot('framed');
		$scatterplot->x_max = max($vals0);
		$scatterplot->y_max = max($vals1);
		//if ($scatterplot->x_max == 0) $scatterplot->x_max = 1;
		//if ($scatterplot->y_max == 0) $scatterplot->y_max = 1;
		$scatterplot->Open(array_search($iXField, $xfields), array_search($iYField, $yfields));
		foreach ($rs as $k => $fields) {
			$scatterplot->DrawDot($fields[0], $fields[1]);
		}
		$scatterplot->DrawTitle('Business Records Analysis', 16);
		$scatterplot->Close();
	}

	if (count($rs) == 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteReportRow(array ('No records found'));
		$report->Close('suppress');
	}

}

$page->write_pagebottom();

?>