<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 03/09/16 MJS - modified to not count records for inactive or invalid BBBs
 * 03/09/16 MJS - changed word cibr to bbb.org
 * 03/17/16 MJS - added word "total" to market share, changed formula for market share
 * 03/18/16 MJS - fixed bug in market share formula
 * 04/20/16 MJS - locked vendors out
 * 08/25/16 MJS - aligned column headers
 * 01/09/17 MJS - changed calls to define links and tabs
 * 03/16/18 MJS - changed words Business Review to Business Profile
 * 02/01/19 MJS - added option for BBB
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);
$page->CheckBBBOnly($BBBID);

$iYear = date('Y') - 2;

$iBBBID = NoApost($_POST['iBBBID']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all', '') );
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			(	select COUNT(*) from Business WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on
					BBB.BBBID = Business.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1 and
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as Businesses,
			(	select COUNT(*) from Business WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on
					BBB.BBBID = Business.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1
				inner join tblRatingCodes WITH (NOLOCK) on
					tblRatingCodes.BBBRatingCode = Business.BBBRatingGrade
				where
					BBBRatingGrade != 'NR' AND BBBRatingGrade != 'NA' AND
					BBBRatingGrade != '' AND NOT BBBRatingGrade IS NULL AND
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as BusinessesWithRatings,
			(	select COUNT(*) from Business WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on
					BBB.BBBID = Business.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1
				inner join tblRatingCodes WITH (NOLOCK) on
					tblRatingCodes.BBBRatingCode = Business.BBBRatingGrade
				where
					BBBRatingGrade != 'NA' AND
					BBBRatingGrade != '' AND NOT BBBRatingGrade IS NULL AND
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as BusinessesWithRatings2,
			(	select distinct count(*)
				from Business WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on
					BBB.BBBID = Business.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1
				where
					NOT ReportURL is NULL and
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as ReportURLs,
			( select COUNT(*) from Business WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on
					BBB.BBBID = Business.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1
				where
					Business.IsBBBAccredited = '1' and
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as ABs,
			( select COUNT(*) from Business WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on
					BBB.BBBID = Business.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1
				where
					Business.IsBBBAccredited = '1' and Business.IsBillable = '1' and
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as BillableABs,
			(	select AVG( ABS( cast (DATEDIFF(
					year, GETDATE(), BusinessProgramParticipation.DateFrom
					) as decimal) ) )
				from Business WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on
					BBB.BBBID = Business.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1
				inner join BusinessProgramParticipation WITH (NOLOCK) on
					BusinessProgramParticipation.BBBID = Business.BBBID AND
					BusinessProgramParticipation.BusinessID = Business.BusinessID and
					(BBBProgram = 'Membership' or BBBProgram = 'BBB Accredited Business') and
					NOT BusinessProgramParticipation.DateFrom IS NULL
				where
					(DateTo > GETDATE() OR DateTo IS NULL) and
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as AverageYearsAsAB,
			(
				select sum(f.DuesRevenue) from BBBFinancials f WITH (NOLOCK) where
					f.[Year] = '{$iYear}' and
					('{$iBBBID}' = '' or f.BBBID = '{$iBBBID}')
			) as DuesRevenue,
			(
				select sum(EstabsInArea) from BBBFinancials f WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on BBB.BBBID = f.BBBID and BBB.BBBBranchID = 0 and BBB.IsActive = 1
				where
					f.[Year] = YEAR(GETDATE()) and f.EstabsInArea >= 1 and
					('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
			) as Estabs
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Statistic', '', '', 'left'),
				array('Value', '', '', 'right'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow( array( 'Total business records as of today', $fields[0],) );
			$report->WriteReportRow( array( 'Total business records with rating grades A to F as of today', $fields[1],) );
			$report->WriteReportRow( array( 'Total business records with rating grades A to F and NR as of today', $fields[2],) );
			$report->WriteReportRow( array( 'Total business profiles on bbb.org as of today', $fields[3],) );
			$report->WriteReportRow( array( 'Total ABs as of today', $fields[4],) );
			$report->WriteReportRow( array( 'Total non-ABs as of today', $fields[0] - $fields[4],) );
			$report->WriteReportRow( array( 'Total percentage ABs as of today', FormatPercentage($fields[4] / $fields[8], 1), ) );
			$report->WriteReportRow( array( 'Average years as AB', round($fields[6],1),) );
			$report->WriteReportRow( array( 'Total billable ABs as of today', $fields[5],) );
			$report->WriteReportRow( array( 'Total dues revenue in ' . $iYear, intval(round($fields[7],0)),) );
		}
		$report->Close('suppress_msg');
	}
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>