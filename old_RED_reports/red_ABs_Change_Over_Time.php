<?php

/*
 * 07/02/18 MJS - new file
 * 07/03/18 MJS - added averages and medians
 * 07/05/18 MJS - added rank column
 * 07/05/18 MJS - switched order of region and sales cat columns
 * 07/05/18 MJS - added input option for region
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = $_REQUEST['iBBBID'];
$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
$iBillable = NoApost($_REQUEST['iBillable']);
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = TrimTrailingComma(NoApost($_POST['iSalesCategory']));
$iShowSource = $_POST['iShowSource'];
$iSortBy = NoApost($_POST['iSortBy']);

if (! $_POST && $iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
if (! $iBillable) $iBillable = 'Billable';

$input_form = new input_form($conn);
$input_form->AddTextField('iMonthFrom', 'Months', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddRadio('iBillable', 'AB type', $iBillable,
	array( 'Billed ABs' => 'Billable', 'Total ABs' => 'Total', )
);
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'ABs From' => 'ABsFrom',
	'ABs To' => 'ABsTo',
	'ABs Net' => 'ABsNet',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$iShowBillable = $iBillable;
	if ($iShowBillable == 'Total') $iShowBillable = '';

	$query = "
		SELECT
			BBB.BBBID,
			NickNameCity,
			SalesCategory,
			tblRegions.RegionAbbreviation,
			(SELECT SnapshotStats.CountOf{$iShowBillable}ABs from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and [MonthNumber] = '{$iMonthFrom}' and
				[Year] = '{$iYearFrom}'
			) as ABsFrom,
			(SELECT SnapshotStats.CountOf{$iShowBillable}ABs from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and [MonthNumber] = '{$iMonthTo}' and
				[Year] = '{$iYearTo}'
			) as ABsTo,
			(
				(SELECT SnapshotStats.CountOf{$iShowBillable}ABs from SnapshotStats WITH (NOLOCK) where
					SnapshotStats.BBBID = BBB.BBBID and [MonthNumber] = '{$iMonthTo}' and
					[Year] = '{$iYearTo}'
				) -
				(SELECT SnapshotStats.CountOf{$iShowBillable}ABs from SnapshotStats WITH (NOLOCK) where
					SnapshotStats.BBBID = BBB.BBBID and [MonthNumber] = '{$iMonthFrom}' and
					[Year] = '{$iYearFrom}'
				)
			) as ABsNet
		from BBB WITH (NOLOCK)
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "'))
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
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array("{$iShowBillable} ABs {$iMonthFrom}/{$iYearFrom}", $SortFields['ABs From'], '', 'right'),
				array("{$iShowBillable} ABs {$iMonthTo}/{$iYearTo}", $SortFields['ABs To'], '', 'right'),
				array("{$iShowBillable} ABs Net", $SortFields['ABs Net'], '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[0] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						"><span class='{$class}'>" . AddApost($fields[1]) . "</span></a>",
					$fields[3],
					$fields[2],
					$fields[4],
					$fields[5],
					$fields[6],
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'',
				'Averages',
				'',
				'',
				intval(array_sum( get_array_column($rs, 4) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 5) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 6) ) / count($rs)),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Medians',
				'',
				'',
				intval(GetMedian( get_array_column($rs, 4) ) ),
				intval(GetMedian( get_array_column($rs, 5) ) ),
				intval(GetMedian( get_array_column($rs, 6) ) ),
			)
		);
		$report->Close();
		if ($iShowSource) {
			$report->WriteSource($query);
		}
	}
}

$page->write_pagebottom();

?>