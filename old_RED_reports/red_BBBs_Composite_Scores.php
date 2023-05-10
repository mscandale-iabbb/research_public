<?php

/*
 * 09/13/17 MJS - new file
 * 01/30/18 MJS - refactored for APICore
 * 02/06/18 MJS - fixed bug in APICore query
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
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
	'One-Star Companies' => 'OneStarCompanies',
	'Two-Star Companies' => 'TwoStarCompanies',
	'Three-Star Companies' => 'ThreeStarCompanies',
	'Four-Star Companies' => 'FourStarCompanies',
	'Five-Star Companies' => 'FiveStarCompanies',
	'One-Star Percentage' => 'OneStarPercentage',
	'Two-Star Percentage' => 'TwoStarPercentage',
	'Three-Star Percentage' => 'ThreeStarPercentage',
	'Four-Star Percentage' => 'FourStarPercentage',
	'Five-Star Percentage' => 'FiveStarPercentage'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$table_Stats = "CORE.dbo.datOrgStats";
		$column_StarRating = "StarRating";
		$link_org = "o.orgId = cr.orgId";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$table_Stats = "APICore.dbo.[Statistics]";
		$column_StarRating = "StarRatingScore";
		$link_org = "o.BureauCode = cr.BureauCode and o.SourceBusinessId = cr.SourceBusinessId";
	}
	$query = "
		SELECT
			BBB.NickNameCity,
			BBB.BBBID,
			count(*) as Companies,
			sum(case when {$column_StarRating} >= 1 and {$column_StarRating} < 2 then 1 else 0 end) as OneStarCompanies,
			sum(case when {$column_StarRating} >= 1 and {$column_StarRating} < 2 then 1 else 0 end) / cast(count(*) as decimal(14,2)) as OneStarPercentage,
			sum(case when {$column_StarRating} >= 2 and {$column_StarRating} < 3 then 1 else 0 end) as TwoStarCompanies,
			sum(case when {$column_StarRating} >= 2 and {$column_StarRating} < 3 then 1 else 0 end) / cast(count(*) as decimal(14,2)) as TwoStarPercentage,
			sum(case when {$column_StarRating} >= 3 and {$column_StarRating} < 4 then 1 else 0 end) as ThreeStarCompanies,
			sum(case when {$column_StarRating} >= 3 and {$column_StarRating} < 4 then 1 else 0 end) / cast(count(*) as decimal(14,2)) as ThreeStarPercentage,
			sum(case when {$column_StarRating} >= 4 and {$column_StarRating} < 5 then 1 else 0 end) as FourStarCompanies,
			sum(case when {$column_StarRating} >= 4 and {$column_StarRating} < 5 then 1 else 0 end) / cast(count(*) as decimal(14,2)) as FourStarPercentage,
			sum(case when {$column_StarRating} = 5 then 1 else 0 end) as FiveStarCompanies,
			sum(case when {$column_StarRating} = 5 then 1 else 0 end) / cast(count(*) as decimal(14,2)) as FiveStarPercentage
			/*
			sum(cr.ReviewTotal) as CustomerReviews,
			TotalPositive, TotalNegative, TotalNeutral,
			cr.Review1Star, cr.Review2Star, cr.Review3Star, cr.Review4Star, cr.Review5Star
			*/
		FROM {$table_Stats} cr WITH (NOLOCK)
		INNER JOIN {$table_Org} o WITH (NOLOCK) on {$link_org}
		INNER JOIN Business b WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessID
		INNER JOIN BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = 0
		WHERE
			o.{$column_StarRating} > 0 and
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
				array('Companies 1.0 - 1.9 Stars', $SortFields['One-Star Companies'], '', 'right'),
				array('%', $SortFields['One-Star Percentage'], '', 'right'),
				array('Companies 2.0 - 2.9 Stars', $SortFields['Two-Star Companies'], '', 'right'),
				array('%', $SortFields['Two-Star Percentage'], '', 'right'),
				array('Companies 3.0 - 3.9 Stars', $SortFields['Three-Star Companies'], '', 'right'),
				array('%', $SortFields['Three-Star Percentage'], '', 'right'),
				array('Companies 4.0 - 4.9 Stars', $SortFields['Four-Star Companies'], '', 'right'),
				array('%', $SortFields['Four-Star Percentage'], '', 'right'),
				array('Companies 5.0 Stars', $SortFields['Five-Star Companies'], '', 'right'),
				array('%', $SortFields['Five-Star Percentage'], '', 'right'),
				array('Total Companies', $SortFields['Companies'], '', 'right'),
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
					$fields[3],
					FormatPercentage($fields[4]),
					$fields[5],
					FormatPercentage($fields[6]),
					$fields[7],
					FormatPercentage($fields[8]),
					$fields[9],
					FormatPercentage($fields[10]),
					$fields[11],
					FormatPercentage($fields[12]),
					$fields[2]
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				array_sum( get_array_column($rs, 3) ),
				'',
				array_sum( get_array_column($rs, 5) ),
				'',
				array_sum( get_array_column($rs, 7) ),
				'',
				array_sum( get_array_column($rs, 9) ),
				'',
				array_sum( get_array_column($rs, 11) ),
				'',
				array_sum( get_array_column($rs, 2) ),
			)
		);
		$report->WriteTotalsRow(
			array (
				'Averages',
				intval(array_sum( get_array_column($rs, 3) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 4) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 5) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 6) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 7) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 8) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 9) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 10) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 11) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 12) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 2) ) / count($rs)),
			)
		);
		$report->WriteTotalsRow(
			array (
				'Medians',
				intval(GetMedian( get_array_column($rs, 3) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 4) ) ),
				intval(GetMedian( get_array_column($rs, 5) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 6) ) ),
				intval(GetMedian( get_array_column($rs, 7) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 8) ) ),
				intval(GetMedian( get_array_column($rs, 9) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 10) ) ),
				intval(GetMedian( get_array_column($rs, 11) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 12) ) ),
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