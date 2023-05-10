<?php

/*
 * 11/06/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 04/23/15 MJS - added Revenue parameter (not active yet)
 * 04/25/16 MJS - refactored to calculate totals without querying separately
 * 04/25/16 MJS - added parameters for BBB region, sales category, and state
 * 08/24/16 MJS - aligned column headers
 * 06/07/17 MJS - cleaned up code
 * 11/15/17 MJS - added option for NAICS
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iOnlyAtoF = NoApost($_POST['iOnlyAtoF']);
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
$iNAICS = NoApost($_POST['iNAICS']);
$iSize = NoApost($_REQUEST['iSize']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = 'r.BBBRatingSortOrder';
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddRadio('iOnlyAtoF', 'Only A to F (exclude NR and NA)', $iOnlyAtoF, array('Yes' => 'yes','No' => '',));
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddSelectField('iNAICS', 'Industry', $iNAICS, $input_form->BuildNAICSGroupArray() );
$input_form->AddMultipleSelectField('iSize', 'Business size', $iSize,
	$input_form->BuildSizesArray('all'), '', '', '', 'width:400px');
/*
$input_form->AddMultipleSelectField('iRevenue', 'Business revenue category', $iRevenue,
	$input_form->BuildRevenueCategoriesArray(), '', '', '', 'width:400px');
*/
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	$query = "
		SELECT
			r.BBBRatingCode,
			(select count(*) from Business b WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = 0
				left outer join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
				where
					b.BBBRatingGrade = r.BBBRatingCode and b.IsBBBAccredited = '1' and
					('{$iOnlyAtoF}' = '' or b.BBBRatingGrade not like 'n%') and
					('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
					('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
					('{$iSize}' = '' or b.SizeOfBusiness IN ('" . str_replace(",", "','", $iSize) . "')) and
					b.IsReportable = 1 and
					('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
					('{$iSalesCategory}' = '' or
						SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
					('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
			) as ABs,
			(select count(*) from Business b WITH (NOLOCK)
				inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = 0
				left outer join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
				where
					b.BBBRatingGrade = r.BBBRatingCode and
					(b.IsBBBAccredited = '0' or b.IsBBBAccredited is null) and
					('{$iOnlyAtoF}' = '' or b.BBBRatingGrade not like 'n%') and
					('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
					('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
					('{$iSize}' = '' or b.SizeOfBusiness IN ('" . str_replace(",", "','", $iSize) . "')) and
					b.IsReportable = 1 and
					('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
					('{$iSalesCategory}' = '' or
						SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
					('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
			) as NonABs
		from tblRatingCodes r WITH (NOLOCK)
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);

	$rs = $rsraw->GetArray();

	// tabular report
	if (count($rs) > 0) {

		// get totals
		$TotalABs = array_sum( get_array_column($rs, 1) );
		$TotalNonABs = array_sum( get_array_column($rs, 2) );

		$report = new report($conn, count($rs));
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Rating', '', '', 'left'),
				array('% of All ABs', '', '', 'right'),
				array('ABs', '', '', 'right'),
				array('% of All Non-ABs', '', '', 'right'),
				array('Non-ABs', '', '', 'right'),
				array('% of All Businesses', '', '', 'right'),
				array('All Businesses', '', '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					FormatPercentage($fields[1] / $TotalABs, 1),
					$fields[1],
					FormatPercentage($fields[2] / $TotalNonABs, 1),
					$fields[2],
					FormatPercentage( ($fields[1] + $fields[2]) / ($TotalABs + $TotalNonABs), 1),
					($fields[1] + $fields[2]),
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				'',
				array_sum( get_array_column($rs, 1) ),
				'',
				array_sum( get_array_column($rs, 2) ),
				'',
				array_sum( get_array_column($rs, 1) ) + array_sum( get_array_column($rs, 2) ),
			)
		);
		if ($iShowSource) {
			$report->WriteSource($query);
		}
		$report->Close('suppress');
	}

	reset($rs);

	// chart 1 of 3
	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
	
		$barchart = new barchart($vals);
		$barchart->gridline_indent = 40;
		$barchart->label_position = 9;
		$barchart->Open();
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[1], $fields[0]);
		}
		$barchart->DrawTitle('Ratings Distribution for ABs');
		$barchart->Close();
	}

	reset($rs);
	$vals = null;

	// chart 2 of 3
	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[2];
		}
	
		$barchart = new barchart($vals);
		$barchart->gridline_indent = 40;
		$barchart->label_position = 9;
		$barchart->Open();
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[2], $fields[0]);
		}
		$barchart->DrawTitle('Ratings Distribution for Non-ABs');
		$barchart->Close();
	}

	reset($rs);
	$vals = null;

	// chart 3 of 3
	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1] + $fields[2];
		}
	
		$barchart = new barchart($vals);
		$barchart->gridline_indent = 40;
		$barchart->label_position = 9;
		$barchart->Open();
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->bar_color = '#076989';
			$barchart->cap_color = '#2991B3';
			$barchart->DrawBar($xcount, $fields[1] + $fields[2], $fields[0]);
		}

		$barchart->DrawTitle('Ratings Distribution for All Businesses');
		$barchart->Close();
	}


	// pie charts

	reset($rs);
	$vals = null;

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}

		$totalval = array_sum($vals);

		$piechart = new piechart();
		$piechart->Open();

		$piechart->position = 0;
		foreach ($rs as $k => $fields) {
			$newposition = $piechart->position + ($fields[1] / $totalval);
			if ( ($fields[1] / $totalval) >= 0.01) {
				$piechart->DrawSlice($piechart->position, $newposition,
					$fields[0] . ' ' . AddComma($fields[1])
				);
			}
			$piechart->position = $newposition;
		}
		$piechart->DrawTitle('Ratings Distribution for ABs');
		$piechart->Close();
	}

	reset($rs);
	$vals = null;

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[2];
		}

		$totalval = array_sum($vals);

		$piechart = new piechart();
		$piechart->Open();

		$piechart->position = 0;
		foreach ($rs as $k => $fields) {
			$newposition = $piechart->position + ($fields[2] / $totalval);
			if ( ($fields[2] / $totalval) >= 0.01) {
				$piechart->DrawSlice($piechart->position, $newposition,
					$fields[0] . ' ' . AddComma($fields[2])
				);
			}
			$piechart->position = $newposition;
		}
		$piechart->DrawTitle('Ratings Distribution for Non-ABs');
		$piechart->Close();
	}

	reset($rs);
	$vals = null;

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1] + $fields[2];
		}

		$totalval = array_sum($vals);

		$piechart = new piechart();
		$piechart->Open();

		$piechart->position = 0;
		foreach ($rs as $k => $fields) {
			$newposition = $piechart->position + (($fields[1] + $fields[2]) / $totalval);
			if ( (($fields[1] + $fields[2]) / $totalval) >= 0.01) {
				$piechart->DrawSlice($piechart->position, $newposition,
					$fields[0] . ' ' . AddComma($fields[1] + $fields[2])
				);
			}
			$piechart->position = $newposition;
		}
		$piechart->DrawTitle('Ratings Distribution for All Businesses');
		$piechart->Close();
	}

	/////////////////////////////////////

}

$page->write_pagebottom();

?>