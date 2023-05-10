<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 07/20/15 MJS - added column for Net
 * 08/19/15 MJS - use latest Census data (not necessarily current year) for establishments
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 08/24/16 MJS - aligned column headers
 * 12/19/16 MJS - added word Now to Estabs In Area column header, separated Now columns
 * 12/27/16 MJS - fixed summary rows which were missing a blank column
 * 01/09/17 MJS - changed calls to define links and tabs
 * 12/19/17 MJS - cleaned up code
 * 03/16/18 MJS - added option to select by BBB
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
$iRegion = NoApost($_POST['iRegion']);
$iBBBID = NoApost($_POST['iBBBID']);
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
$input_form->AddTextField('iMonthFrom', 'Month range', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddMultipleSelectField('iBBBID', 'BBBs', $iBBBID,
	$input_form->BuildBBBCitiesArray('all'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'New ABs' => 'NewMembers',
	'Lost ABs' => 'LostMembers',
	'Net ABs' => 'NetMembers',
	'ABs' => 'Members',
	'ABs now' => 'MembersAsOfNow',
	'Billable ABs now' => 'BillableMembersAsOfNow',
	'Estabs in area' => 'EstabsInArea',
	'Market share' => 'MarketShare'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @datefrom date;
		set @datefrom = CONVERT(datetime, '{$iMonthFrom}' + '/1/' + '{$iYearFrom}');
		declare @dateto date;
		set @dateto = CONVERT(datetime, '{$iMonthTo}' + '/1/' + '{$iYearTo}');
		SELECT
			NickNameCity + ', ' + BBB.State,
			SalesCategory,
			tblRegions.RegionAbbreviation,
			(SELECT sum(SnapshotStats.CountOfNewABs)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@datefrom and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@dateto
				) as NewMembers,
			(SELECT sum(SnapshotStats.CountOfDroppedABs) from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@datefrom and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@dateto )
				as LostMembers,
			(SELECT SnapshotStats.CountOfABs from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and [MonthNumber] = MONTH(@dateto) and
				[Year] = YEAR(@dateto)
				) as Members,
			(select count(distinct Business.BusinessID) from Business WITH (NOLOCK) where
				Business.BBBID = BBB.BBBID and Business.IsBBBAccredited = '1'
				) as MembersAsOfNow,
			(select count(distinct Business.BusinessID) from Business WITH (NOLOCK) where
				Business.BBBID = BBB.BBBID and Business.IsBBBAccredited = '1' and Business.IsBillable = '1'
				) as BillableMembersAsOfNow,
			f.EstabsInArea,
			(
				(select count(distinct Business.BusinessID) from Business WITH (NOLOCK) where
					Business.BBBID = BBB.BBBID and Business.IsBBBAccredited = '1'
				) /
				cast(f.EstabsInArea as decimal(14,2))
			) as MarketShare,
			BBB.BBBID,
			(SELECT sum(SnapshotStats.CountOfNewABs) - sum(SnapshotStats.CountOfDroppedABs) from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@datefrom and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@dateto )
				as NetMembers
		from BBB WITH (NOLOCK)
		inner join BBBFinancials f WITH (NOLOCK) on
			f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and /*f.[Year] = YEAR(GETDATE())*/
			(select count(*) from BBBFinancials f2 WITH (NOLOCK) WHERE
				f2.BBBID = f.BBBID and f2.BBBBranchID = f.BBBBranchID and
				f2.[Year] > f.[Year]) = 0
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iBBBID}' = '' or BBB.BBBID IN ('" . str_replace(",", "','", $iBBBID) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
		";
	if ($iSortBy > '') {
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
				array('New ABs ' . $iMonthFrom . '/' . $iYearFrom . ' - ' . $iMonthTo . '/' .
					$iYearTo, $SortFields['New ABs'], '', 'right'),
				array('Lost ABs ' . $iMonthFrom . '/' . $iYearFrom . ' - ' . $iMonthTo . '/' .
					$iYearTo, $SortFields['Lost ABs'], '', 'right'),
				array('Net ABs ' . $iMonthFrom . '/' . $iYearFrom . ' - ' . $iMonthTo . '/' .
						$iYearTo, $SortFields['Net ABs'], '', 'right'),
				array('ABs ' . $iMonthTo . '/' . $iYearTo, $SortFields['ABs'], '', 'right'),
				array(' '),
				array('ABs Now', $SortFields['ABs now'], '', 'right'),
				array('Billable ABs Now', $SortFields['Billable ABs now'], '', 'right'),
				array('Estabs in Area Now', $SortFields['Estabs in area'], '', 'right'),
				array('Market Share Now', $SortFields['Market share'], '', 'right')
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[10] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[10] .
						"><span class='{$class}'>" . AddApost($fields[0]) . "</span></a>",
					$fields[1],
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[11],
					$fields[5],
					" &nbsp ",
					$fields[6],
					$fields[7],
					$fields[8],
					FormatPercentage($fields[9], 1)
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
				array_sum( get_array_column($rs, 3) ),
				array_sum( get_array_column($rs, 4) ),
				array_sum( get_array_column($rs, 11) ),
				array_sum( get_array_column($rs, 5) ),
				'',
				array_sum( get_array_column($rs, 6) ),
				array_sum( get_array_column($rs, 7) ),
				array_sum( get_array_column($rs, 8) ),
				FormatPercentage(
					array_sum( get_array_column($rs, 6) ) / array_sum( get_array_column($rs, 8) ), 1)
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Averages',
				'',
				'',
				intval(round(array_sum( get_array_column($rs, 3) ) / count($rs))),
				intval(round(array_sum( get_array_column($rs, 4) ) / count($rs))),
				intval(round(array_sum( get_array_column($rs, 11) ) / count($rs))),
				intval(round(array_sum( get_array_column($rs, 5) ) / count($rs))),
				'',
				intval(round(array_sum( get_array_column($rs, 6) ) / count($rs))),
				intval(round(array_sum( get_array_column($rs, 7) ) / count($rs))),
				intval(round(array_sum( get_array_column($rs, 8) ) / count($rs))),
				FormatPercentage(array_sum( get_array_column($rs, 9) ) / count($rs), 1),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Medians',
				'',
				'',
				intval(round(GetMedian( get_array_column($rs, 3) ))),
				intval(round(GetMedian( get_array_column($rs, 4) ))),
				intval(round(GetMedian( get_array_column($rs, 11) ))),
				intval(round(GetMedian( get_array_column($rs, 5) ))),
				'',
				intval(round(GetMedian( get_array_column($rs, 6) ))),
				intval(round(GetMedian( get_array_column($rs, 7) ))),
				intval(round(GetMedian( get_array_column($rs, 8) ))),
				FormatPercentage(GetMedian( get_array_column($rs, 9) ), 1),
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