<?php

/*
 * 11/04/15 MJS - new file
 * 12/17/15 MJS - changed rate from 6% to 5.85%
 * 12/18/15 MJS - truncated cents from dollar amounts
 * 12/18/15 MJS - default year changed from 2015 to 2016
 * 04/19/16 MJS - locked out vendors
 * 11/20/17 MJS - changed default year to 2018
 * 11/20/17 MJS - added rates by year
 * 11/20/17 MJS - only allowed 2013 and beyond
 * 01/08/18 MJS - fixed bug with rounding
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
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
else if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$tmpyear = date('Y');
if ($tmpyear == 2015) $tmpyear = 2016;
if ($tmpyear == 2017) $tmpyear = 2018;
$iYear = ValidYear( Numeric2( GetInput('iYear',$tmpyear) ) );
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('yoursonly') );
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'number min=2016');
$input_form->AddNote('This report covers 2016 and later years.');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			BBB.NicknameCity + ', ' + BBB.State,
			r.BBBAdjustedRevenue,
			r.FoundationAdjustedRevenue,
			r.BBBAdjustedRevenue + r.FoundationAdjustedRevenue,
			DuesRate,
			cast(round( (r.BBBAdjustedRevenue + r.FoundationAdjustedRevenue) * cast(DuesRate as decimal(20,4)), 8) as decimal(18,0)),
			cast(round ( ((r.BBBAdjustedRevenue + r.FoundationAdjustedRevenue) * cast(DuesRate as decimal(20,4))) / 12.00, 8) as decimal(18,0))
			/*cast(round ( ((r.BBBAdjustedRevenue + r.FoundationAdjustedRevenue) * cast(DuesRate as decimal(20,4))) / 12.00, 8) as decimal(18,2))*/
		FROM BBBRevenueForm r WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) ON BBB.BBBID = r.BBBID AND BBB.BBBBranchID = 0
		INNER JOIN CBBBDuesRate WITH (NOLOCK) ON DuesYear = ('{$iYear}' - 3)
		WHERE
			r.BBBID = '{$iBBBID}' and
			r.[Year] = '{$iYear}' - 3
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow( array( 'BBB city', AddApost($fields[0]), ) );
			$report->WriteReportRow( array( 'Adjusted BBB revenue for ' . ($iYear - 3), '$' . AddComma(floor($fields[1])), ) );
			$report->WriteReportRow( array( 'Adjusted Foundation revenue for ' . ($iYear - 3), '$' . AddComma(floor($fields[2])), ) );
			$report->WriteReportRow( array( 'Adjusted BBB revenue + Adjusted Foundation revenue for ' . ($iYear - 3), '$' . AddComma(floor($fields[3])), ) );
			$report->WriteReportRow( array( 'CBBB dues rate for ' . $iYear, FormatPercentage($fields[4], 2) . ' rate', ) );
			$report->WriteReportRow( array( 'CBBB dues (annually) ' . $iYear, '$' . AddComma($fields[5]), ) );
			$report->WriteReportRow( array( 'CBBB dues (monthly) ' . $iYear, '$' . AddComma($fields[6]), ) );
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>