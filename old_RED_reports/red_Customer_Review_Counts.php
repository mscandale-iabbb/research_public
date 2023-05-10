<?php

/*
 * 09/12/17 MJS - new file
 * 10/11/17 MJS - fixed column labels
 * 01/30/18 MJS - refactored for APICore
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
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
if ($iMonthTo == 0) {
	$iMonthTo = 12;
	$iYearTo--;
	$iMonthFrom = $iMonthTo;
	$iYearFrom = $iYearTo;
}
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Companies' => 'Companies',
	'Positive Reviews' => 'PositiveReviews',
	'Negative Reviews' => 'NegativeReviews',
	'Neutral Reviews' => 'NeutralReviews',
	'Total Reviews' => 'TotalReviews',
	'Percent Positive' => 'PercentPositive',
	'Percent Negative' => 'PercentNegative',
	'Percent Neutral' => 'PercentNeutral',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
	}
	$query = "
		SELECT
			BBB.NickNameCity,
			BBB.BBBID,
			count(*) as Companies,
			sum(TotalPositive) as PositiveReviews,
			sum(TotalNegative) as NegativeReviews,
			sum(TotalNeutral) as NeutralReviews,
			sum(TotalPositive + TotalNegative + TotalNeutral) as TotalReviews,
			round(sum(TotalPositive) / cast(sum(TotalPositive + TotalNegative + TotalNeutral) as decimal(14,2)), 2) as PercentPositive,
			round(sum(TotalNegative) / cast(sum(TotalPositive + TotalNegative + TotalNeutral) as decimal(14,2)), 2) as PercentNegative,
			round(sum(TotalNeutral) / cast(sum(TotalPositive + TotalNegative + TotalNeutral) as decimal(14,2)), 2) as PercentNeutral
		FROM {$table_Org} o WITH (NOLOCK)
		INNER JOIN Business b WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessID
		INNER JOIN BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = 0
		INNER JOIN tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			(o.TotalPositive > 0 or o.TotalNegative > 0 or o.TotalNeutral > 0) and
			BBB.IsActive = '1' AND
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
		GROUP BY BBB.BBBID, BBB.NickNameCity
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Total Reviews', $SortFields['Total Reviews'], '', 'right'),
				array('Positive Reviews', $SortFields['Positive Reviews'], '', 'right'),
				array('Negative Reviews', $SortFields['Negative Reviews'], '', 'right'),
				array('Neutral Reviews', $SortFields['Neutral Reviews'], '', 'right'),
				array('Positive %', $SortFields['Percent Positive'], '', 'right'),
				array('Negative %', $SortFields['Percent Negative'], '', 'right'),
				array('Neutral %', $SortFields['Percent Neutral'], '', 'right'),
				array('Companies', $SortFields['Companies'], '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[13] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[1] .
						"><span class='{$class}'>" . AddApost($fields[0]) . "</span></a>",
					$fields[6],
					$fields[3],
					$fields[4],
					$fields[5],
					FormatPercentage($fields[7]),
					FormatPercentage($fields[8]),
					FormatPercentage($fields[9]),
					$fields[2]
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				array_sum( get_array_column($rs, 6) ),
				array_sum( get_array_column($rs, 3) ),
				array_sum( get_array_column($rs, 4) ),
				array_sum( get_array_column($rs, 5) ),
				FormatPercentage(array_sum( get_array_column($rs, 3) ) / array_sum( get_array_column($rs, 6) )),
				FormatPercentage(array_sum( get_array_column($rs, 4) ) / array_sum( get_array_column($rs, 6) )),
				FormatPercentage(array_sum( get_array_column($rs, 5) ) / array_sum( get_array_column($rs, 6) )),
				array_sum( get_array_column($rs, 2) ),
			)
		);
		$report->WriteTotalsRow(
			array (
				'Averages',
				intval(array_sum( get_array_column($rs, 6) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 3) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 4) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 5) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 7) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 8) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 9) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 2) ) / count($rs)),
			)
		);
		$report->WriteTotalsRow(
			array (
				'Medians',
				intval(GetMedian( get_array_column($rs, 6) ) ),
				intval(GetMedian( get_array_column($rs, 3) ) ),
				intval(GetMedian( get_array_column($rs, 4) ) ),
				intval(GetMedian( get_array_column($rs, 5) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 7) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 8) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 9) ) ),
				intval(GetMedian( get_array_column($rs, 2) ) ),
			)
		);
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

?>