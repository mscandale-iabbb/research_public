<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 08/24/16 MJS - aligned column headers
 * 10/26/17 MJS - cleaned up code
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
$iCountry = $_POST['iCountry'];
$iAB = NoApost($_REQUEST['iAB']);
if (! $_POST) $iAB = 1;
$iSize = NoApost($_REQUEST['iSize']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Businesses DESC';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddSelectField('iSize', 'Business size', $iSize, $input_form->BuildSizesArray('all') );
$SortFields = array(
	'Businesses' => 'Businesses',
	'TOB code' => 'b.TOBID',
	'TOB description' => 'tblYPPA.yppa_text'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT TOP " . $iMaxRecs . "
			tblYPPA.yppa_text,
			b.TOBID,
			count(*) as Businesses
		from Business b WITH (NOLOCK)
		inner join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		inner join BBB WITH (NOLOCK) on b.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "')) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}') and
			('{$iSize}' = '' or b.SizeOfBusiness = '{$iSize}')
		GROUP BY b.TOBID, tblYPPA.yppa_text
		";
	if ($iSortBy) {
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
				array('TOB Description', $SortFields['TOB description'], '', 'left'),
				array('TOB Code', $SortFields['TOB code'], '', 'left'),
				array('Businesses', $SortFields['Businesses'], '', 'right'),
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
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Total',
				'',
				'',
				array_sum( get_array_column($rs, 2) ),
			)
		);
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>