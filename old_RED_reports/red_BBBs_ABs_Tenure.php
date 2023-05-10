<?php

/*
 * 07/05/18 MJS - new file
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iRegion = NoApost($_POST['iRegion']);
$iBBBID = NoApost($_POST['iBBBID']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
$iMaxRecs = Numeric2($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'avgyears DESC';
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddMultipleSelectField('iBBBID', 'BBBs', $iBBBID,
	$input_form->BuildBBBCitiesArray('all'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB' => 'NicknameCity',
	'ABs' => 'ABs',
	'Average years accredited' => 'avgyears',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			NicknameCity,
			tblRegions.RegionAbbreviation,
			SalesCategory,
			AVG( ABS( cast (DATEDIFF( year, GETDATE(), p.DateFrom ) as decimal) ) ) as avgyears,
			COUNT(*) as ABs,
			SUM( ABS( cast (DATEDIFF( year, GETDATE(), p.DateFrom ) as decimal) ) ) as sumyears /* used for totals */
		from Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on b.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0' and BBB.IsActive = '1'
		inner join BusinessProgramParticipation p WITH (NOLOCK) on p.BBBID = b.BBBID AND p.BusinessID = b.BusinessID and
			(p.BBBProgram = 'Membership' or p.BBBProgram = 'BBB Accredited Business') and
			NOT p.DateFrom IS NULL
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			(p.DateTo > GETDATE() OR p.DateTo IS NULL) and
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iBBBID}' = '' or BBB.BBBID IN ('" . str_replace(",", "','", $iBBBID) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
		GROUP BY NicknameCity, tblRegions.RegionAbbreviation, SalesCategory
		";
	if ($iSortBy > '') {
		$query .= " ORDER BY " . $iSortBy;
	}

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) AbortREDReport($query);
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('#', '', '', 'right'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array('Average Years Accredited', $SortFields['Average years accredited'], '', 'right'),
				array('ABs', $SortFields['ABs'], '', 'right'),
			)
		);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_REQUEST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;

		foreach ($rs as $k => $fields) {
			$xcount++;

			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;

			$report->WriteReportRow(
				array (
					$xcount,
					AddApost($fields[0]),
					$fields[1],
					$fields[2],
					round($fields[3], 1),
					$fields[4],
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'',
				'Totals',
				'',
				'',
				round(
					array_sum( get_array_column($rs, 5) ) /
					array_sum( get_array_column($rs, 4) ),
				1),
				array_sum( get_array_column($rs, 4) ),
			)
		);
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>