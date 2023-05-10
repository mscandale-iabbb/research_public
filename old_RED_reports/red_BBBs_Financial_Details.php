<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 04/19/16 MJS - locked out vendors
 * 05/27/16 MJS - include inactive bbbs
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 08/25/16 MJS - aligned column headers
 * 09/05/17 MJS - cleaned up code
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


$iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') - 2) ) );
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'year');
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'Total revenue' => 'TotalRevenue',
	'Dues revenue' => 'DuesRevenue',
	'Total expenses' => 'TotalExpenses',
	'Total salaries' => 'TotalSalaries',
	'Begin balance' => 'BeginFundBalance',
	'End balance' => 'EndFundBalance',
	'Change balance' => 'ChangeInBalance',
	'Change balance %' => 'ChangeInBalancePerc'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			NickNameCity + ', ' + BBB.State,
			SalesCategory,
			tblRegions.RegionAbbreviation,
			round(f.TotalRevenue,0),
			round(f.DuesRevenue,0),
			round(f.TotalExpenses,0),
			round(f.TotalSalaries,0),
			round(f.BeginFundBalance,0),
			round(f.EndFundBalance,0),
			round(f.EndFundBalance - f.BeginFundBalance, 0) as ChangeInBalance,
			(f.EndFundBalance - f.BeginFundBalance) / abs(f.BeginFundBalance) as ChangeInBalancePerc,
			BBB.BBBID
		FROM BBB WITH (NOLOCK)
		INNER JOIN BBBFinancials f WITH (NOLOCK) on
			f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and f.[Year] = '{$iYear}'
		INNER JOIN tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			BBB.BBBBranchID = 0 AND BBB.BBBID != '2000' /*include inactive*/ /*BBB.IsActive = '1'*/ AND
			(
				TotalRevenue > 0 or DuesRevenue > 0 or TotalExpenses > 0 or TotalSalaries > 0 or
				BeginFundBalance > 0 or EndFundBalance > 0
			) and
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
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
				array('#', '', '', 'right'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('Total Revenue', $SortFields['Total revenue'], '', 'right'),
				array('Dues Revenue', $SortFields['Dues revenue'], '', 'right'),
				array('Total Expenses', $SortFields['Total expenses'], '', 'right'),
				array('Total Salaries', $SortFields['Total salaries'], '', 'right'),
				array('Begin Balance', $SortFields['Begin balance'], '', 'right'),
				array('End Balance', $SortFields['End balance'], '', 'right'),
				array('Change Balance', $SortFields['Change balance'], '', 'right'),
				array('Change Balance %', $SortFields['Change balance %'], '', 'right')
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[11] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[11] .
						"><span class='{$class}'>" . AddApost($fields[0]) . "</span></a>",
					$fields[1],
					$fields[2],
					intval($fields[3]),
					intval($fields[4]),
					intval($fields[5]),
					intval($fields[6]),
					intval($fields[7]),
					intval($fields[8]),
					intval($fields[9]),
					FormatPercentage($fields[10], 1)
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'',
				'Totals',
				'',
				'',
				intval( array_sum( get_array_column($rs, 3) )),
				intval( array_sum( get_array_column($rs, 4) )),
				intval( array_sum( get_array_column($rs, 5) )),
				intval( array_sum( get_array_column($rs, 6) )),
				intval( array_sum( get_array_column($rs, 7) )),
				intval( array_sum( get_array_column($rs, 8) )),
				intval( array_sum( get_array_column($rs, 9) )),
				FormatPercentage(
					intval( array_sum( get_array_column($rs, 9) ) ) /
					intval( abs(array_sum( get_array_column($rs, 7) )) ),
					1),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Averages',
				'',
				'',
				intval( array_sum( get_array_column($rs, 3)) / count( get_array_column($rs, 3)) ),
				intval( array_sum( get_array_column($rs, 4)) / count( get_array_column($rs, 4)) ),
				intval( array_sum( get_array_column($rs, 5)) / count( get_array_column($rs, 5)) ),
				intval( array_sum( get_array_column($rs, 6)) / count( get_array_column($rs, 6)) ),
				intval( array_sum( get_array_column($rs, 7)) / count( get_array_column($rs, 7)) ),
				intval( array_sum( get_array_column($rs, 8)) / count( get_array_column($rs, 8)) ),
				intval( array_sum( get_array_column($rs, 9)) / count( get_array_column($rs, 9)) ),
				FormatPercentage(
					intval( array_sum( get_array_column($rs, 10) ) ) / count($rs),
					1),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Medians',
				'',
				'',
				intval( GetMedian( get_array_column($rs, 3) ) ),
				intval( GetMedian( get_array_column($rs, 4) ) ),
				intval( GetMedian( get_array_column($rs, 5) ) ),
				intval( GetMedian( get_array_column($rs, 6) ) ),
				intval( GetMedian( get_array_column($rs, 7) ) ),
				intval( GetMedian( get_array_column($rs, 8) ) ),
				intval( GetMedian( get_array_column($rs, 9) ) ),
				FormatPercentage( GetMedian( get_array_column($rs, 10) ), 1),
			)
		);
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

?>