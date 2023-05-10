<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 07/15/15 MJS - fixed alignment of totals row
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 08/24/16 MJS - aligned column headers
 * 02/10/17 MJS - added CountOfABsForRetention (only for BBB Austin or other merged BBBs)
 * 02/11/17 MJS - added CountOfDroppedABsForRetention (only for BBB Austin or other merged BBBs)
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);

$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
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
$input_form->AddTextField('iMonthTo', 'Month to', $iMonthTo, "width:35px;", '', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
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
	'ABs at start' => 'Members',
	'ABs dropped' => 'LostMembers',
	'ABs retained' => 'Retained',
	'Retention rate' => 'RetentionRate'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @dateto date;
		set @dateto = CONVERT(datetime,
			'" . $iMonthTo . "' + '/1/' + '" . $iYearTo . "');
		
		/* set Date To to the last day of month */
		IF MONTH(@dateto) < 12 BEGIN
			SET @dateto = CONVERT(datetime,
				CAST(MONTH(@dateto) + 1 as varchar(2)) + '/' + '1/' + CAST(YEAR(@dateto) as varchar(4))
				) - 1;
		END
		IF MONTH(@dateto) = 12 BEGIN
			SET @dateto = CONVERT(datetime, '12/31/' + CAST(YEAR(@dateto) as varchar(4))
				) - 1;
		END
		
		/* subtract a month if running before the 6th day of the month */
		if DAY(GETDATE()) < 6 AND DATEDIFF(day,GETDATE(),@dateto) < 6 SET @dateto = DATEADD(month,-1,@dateto);
		
		/* don't allow future dates */
		IF @dateto > GETDATE() SET @dateto = GETDATE();
		
		declare @monthfrom int;
		declare @yearfrom int;
		IF MONTH(@dateto) < 12 BEGIN
			SET @monthfrom = MONTH(@dateto) + 1;
			SET @yearfrom = YEAR(@dateto) - 1;
		END
		IF MONTH(@dateto) = 12 BEGIN
			SET @monthfrom = 1;
			SET @yearfrom = YEAR(@dateto);
		END
		declare @datefrom date;
		set @datefrom = CONVERT(datetime,
			cast(@monthfrom as varchar(2)) + '/1/' +
			cast(@yearfrom as varchar(4))	);
		SELECT
			BBB.NickNameCity + ', ' + BBB.State, 
			BBB.SalesCategory,
			tblRegions.RegionAbbreviation,
			cast(MONTH(@datefrom) as varchar(2)) + '/' +
				cast(DAY(@datefrom) as varchar(2)) + '/' +
				cast(YEAR(@datefrom) as varchar(4)) as datefrom,
			cast(MONTH(@dateto) as varchar(2)) + '/' +
				cast(DAY(@dateto) as varchar(2)) + '/' +
				cast(YEAR(@dateto) as varchar(4)) as dateto,
			case when
					(SELECT SnapshotStats.CountOfABsForRetention from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
							[MonthNumber] = MONTH(@datefrom) and
							[Year] = YEAR(@datefrom)
					) > 0
				then
					(SELECT SnapshotStats.CountOfABsForRetention from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
							[MonthNumber] = MONTH(@datefrom) and
							[Year] = YEAR(@datefrom)
					)
				else
					(SELECT SnapshotStats.CountOfABs from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
							[MonthNumber] = MONTH(@datefrom) and
							[Year] = YEAR(@datefrom)
					)
				end as Members,
			(SELECT sum(
					case when SnapshotStats.CountOfDroppedABsForRetention > 0
					then SnapshotStats.CountOfDroppedABsForRetention
					else SnapshotStats.CountOfDroppedABs end
				) from SnapshotStats WITH (NOLOCK)
				where SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
					CAST( [Year] AS VARCHAR(4)) ) >= @datefrom and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
					CAST( [Year] AS VARCHAR(4)) ) <= @dateto
				) as LostMembers,
			case when
					(SELECT SnapshotStats.CountOfABsForRetention from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
							[MonthNumber] = MONTH(@datefrom) and
							[Year] = YEAR(@datefrom)
					) > 0
				then
					(SELECT SnapshotStats.CountOfABsForRetention from SnapshotStats
						WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
						[MonthNumber] = MONTH(@datefrom) and
						[Year] = YEAR(@datefrom)
					) -
					(SELECT sum(
							case when SnapshotStats.CountOfDroppedABsForRetention > 0
							then SnapshotStats.CountOfDroppedABsForRetention
							else SnapshotStats.CountOfDroppedABs end
						) from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
						CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
							CAST( [Year] AS VARCHAR(4)) ) >= @datefrom and
						CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
							CAST( [Year] AS VARCHAR(4)) ) <= @dateto
					)
				else
					(SELECT SnapshotStats.CountOfABs from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
						[MonthNumber] = MONTH(@datefrom) and
						[Year] = YEAR(@datefrom)
					) -
					(SELECT sum(
							case when SnapshotStats.CountOfDroppedABsForRetention > 0
							then SnapshotStats.CountOfDroppedABsForRetention
							else SnapshotStats.CountOfDroppedABs end
						) from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
						CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
							CAST( [Year] AS VARCHAR(4)) ) >= @datefrom and
						CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
							CAST( [Year] AS VARCHAR(4)) ) <= @dateto
					)
				end as Retained,
			case when
					(SELECT SnapshotStats.CountOfABsForRetention from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
							[MonthNumber] = MONTH(@datefrom) and
							[Year] = YEAR(@datefrom)
					) > 0
				then
					(
						(SELECT SnapshotStats.CountOfABsForRetention from SnapshotStats WITH (NOLOCK)
							where SnapshotStats.BBBID = BBB.BBBID and
							[MonthNumber] = MONTH(@datefrom) and
							[Year] = YEAR(@datefrom)
						) -
						(SELECT sum(
								case when SnapshotStats.CountOfDroppedABsForRetention > 0
								then SnapshotStats.CountOfDroppedABsForRetention
								else SnapshotStats.CountOfDroppedABs end
							) from SnapshotStats WITH (NOLOCK)
							where SnapshotStats.BBBID = BBB.BBBID and
							CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
								CAST( [Year] AS VARCHAR(4)) ) >= @datefrom and
							CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
								CAST( [Year] AS VARCHAR(4)) ) <= @dateto
						)
					) /
					cast ( (SELECT SnapshotStats.CountOfABsForRetention from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
						[MonthNumber] = MONTH(@datefrom) and
						[Year] = YEAR(@datefrom)
						) as decimal(14,2)
					)
				else
					(
						(SELECT SnapshotStats.CountOfABs from SnapshotStats WITH (NOLOCK)
							where SnapshotStats.BBBID = BBB.BBBID and
							[MonthNumber] = MONTH(@datefrom) and
							[Year] = YEAR(@datefrom)
						) -
						(SELECT sum(
								case when SnapshotStats.CountOfDroppedABsForRetention > 0
								then SnapshotStats.CountOfDroppedABsForRetention
								else SnapshotStats.CountOfDroppedABs end
							) from SnapshotStats WITH (NOLOCK)
							where SnapshotStats.BBBID = BBB.BBBID and
							CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
								CAST( [Year] AS VARCHAR(4)) ) >= @datefrom and
							CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
								CAST( [Year] AS VARCHAR(4)) ) <= @dateto
						)
					) /
					cast ( (SELECT SnapshotStats.CountOfABs from SnapshotStats WITH (NOLOCK)
						where SnapshotStats.BBBID = BBB.BBBID and
						[MonthNumber] = MONTH(@datefrom) and [Year] = YEAR(@datefrom)
						) as decimal(14,2)
					)
				end as RetentionRate,
			BBB.BBBID
		from BBB WITH (NOLOCK)
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' and
			('" . $iRegion . "' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('" . $iSalesCategory . "' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('" . $iState . "' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
			";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();

	$report = new report( $conn, count($rs) );
	$report->Open();
	if ( count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('#', '', '', 'right'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('Period From', '', '', 'left'),
				array('Period To', '', '', 'left'),
				array('ABs at Start of Period', $SortFields['ABs at start'], '', 'right'),
				array('Dropped During Period', $SortFields['ABs dropped'], '', 'right'),
				array('Retained During Period', $SortFields['ABs retained'], '', 'right'),
				array('Retention Rate', $SortFields['Retention rate'], '', 'right')
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[9] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[9] .
						"><span class='{$class}'>" . AddApost($fields[0]) . "</span></a>",
					$fields[1],
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
					FormatPercentage($fields[8], 1)
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				'',
				'',
				'',
				'',
				'',
				array_sum( get_array_column($rs, 5) ),
				array_sum( get_array_column($rs, 6) ),
				array_sum( get_array_column($rs, 7) ),
				FormatPercentage(
					array_sum( get_array_column($rs, 7) ) / array_sum( get_array_column($rs, 5) ), 1)
			)
		);
		$report->WriteTotalsRow(
			array (
				'Averages',
				'',
				'',
				'',
				'',
				'',
				intval(round(array_sum( get_array_column($rs, 5) ) / count($rs))),
				intval(round(array_sum( get_array_column($rs, 6) ) / count($rs))),
				intval(round(array_sum( get_array_column($rs, 7) ) / count($rs))),
				FormatPercentage(array_sum( get_array_column($rs, 8) ) / count($rs), 1),
			)
		);
		$report->WriteTotalsRow(
			array (
				'Medians',
				'',
				'',
				'',
				'',
				'',
				intval(round(GetMedian( get_array_column($rs, 5) ))),
				intval(round(GetMedian( get_array_column($rs, 6) ))),
				intval(round(GetMedian( get_array_column($rs, 7) ))),
				FormatPercentage(GetMedian( get_array_column($rs, 8) ), 1),
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