<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 01/14/15 MJS - fixed bug where local BBBs couldn't select ALL
 * 01/11/16 MJS - added trimming of trailing comma from sales category to exclude blank value from non-all selections
 * 01/11/16 MJS - fixed bug where partial years' data (less than 12 months) would be divided by 12 and give wrong numbers
 * 01/11/16 MJS - undid fix because it didn't work
 * 01/12/16 MJS - fixed bug again to average by actual number of months examined (which isn't always 12)
 * 02/10/16 MJS - added section for No Records Found
 * 01/09/17 MJS - changed calls to define links and tabs
 * 12/19/17 MJS - split into multiple graphs
 * 01/02/18 MJS - changed to billable ABs
 * 06/27/18 MJS - added option for billable or total
 * 06/27/18 MJS - made 2nd chart 1st
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
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iBillable = NoApost($_REQUEST['iBillable']);
$iPeriod = NoApost($_REQUEST['iPeriod']);
$iSalesCategory = TrimTrailingComma(NoApost($_POST['iSalesCategory']));
$iShowSource = $_POST['iShowSource'];

if (! $_POST && $iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
if (! $iPeriod) $iPeriod = 'months';
if (! $iBillable) $iBillable = 'Billable';

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddDateField('iDateFrom','Date range',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddRadio('iPeriod', 'Period', $iPeriod,
	array( 'Months' => 'months', 'Years' => 'years', )
);
$input_form->AddRadio('iBillable', 'AB type', $iBillable,
	array( 'Billed ABs' => 'Billable', 'Total ABs' => 'Total', )
);
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$iShowBillable = $iBillable;
	if ($iShowBillable == 'Total') $iShowBillable = '';
	if ($iPeriod == 'years') {
		$query = "select
				SUM(s.CountOf{$iShowBillable}ABs) /
					/* number of months */
					/* (this is to prevent wrong totals if, for example, only 11 months of snapshot data exist so far) */
					(select count(*) from
						(select s2.MonthNumber from SnapshotStats s2 WITH (NOLOCK)
							inner join BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = s2.BBBID and BBB2.BBBBranchID = 0
							where
								s2.[Year] = s.[Year] and
								('{$iBBBID}' = '' or s2.BBBID = '{$iBBBID}') and
								('{$iSalesCategory}' = '' or
									BBB2.SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "'))
							group by s2.MonthNumber
						) as subset
					),
				SUM(s.CountOfNew{$iShowBillable}ABs),
				SUM(s.CountOfDropped{$iShowBillable}ABs),
				[Year]
			from SnapshotStats s WITH (NOLOCK)
			inner join BBB WITH (NOLOCK) on BBB.BBBID = s.BBBID AND BBB.BBBBranchID = 0 /*AND BBB.IsActive = '1'*/
			where
				('{$iBBBID}' = '' or s.BBBID = '{$iBBBID}') and
				('{$iSalesCategory}' = '' or
					SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
				[Year] >= YEAR('{$iDateFrom}') and
				[Year] <= YEAR('{$iDateTo}')
			group by s.[Year]
			order by s.[Year]
			";
	}
	else {
		$query = "select
				SUM(s.CountOf{$iShowBillable}ABs),
				SUM(s.CountOfNew{$iShowBillable}ABs),
				SUM(s.CountOfDropped{$iShowBillable}ABs),
				(CONVERT(varchar(7), CAST( [MonthNumber] as VARCHAR) + '/' + CAST( [Year] AS VARCHAR) )) AS MonthYearText
			from SnapshotStats s WITH (NOLOCK)
			inner join BBB WITH (NOLOCK) on BBB.BBBID = s.BBBID AND BBB.BBBBranchID = 0 /*AND BBB.IsActive = '1'*/
			where
				('{$iBBBID}' = '' or s.BBBID = '{$iBBBID}') and
				('{$iSalesCategory}' = '' or
					SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
				CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' + CAST( s.[Year] AS VARCHAR) ) >= '" . $iDateFrom . "' and
				CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' + CAST( s.[Year] AS VARCHAR) ) <= '" . $iDateTo . "'
			GROUP BY s.[Year], s.[MonthNumber]
			ORDER BY s.[Year], s.[MonthNumber]
			";
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	
	if (count($rs) > 0 && $output_type == "") {
		$report = new report( $conn, count($rs) );


		/* chart 2 of 2 */
		
		$vals = array();
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[0];
		}
		$barchart2 = new barchart($vals, 'framed');
		$barchart2->Open();
		$barchart2->bar_color = '#43859B';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart2->DrawBar($xcount, $fields[0], $fields[3]);
		}
		$barchart2->DrawTitle("BBB {$iShowBillable} ABs over Time");
		$barchart2->DrawTrendLine($vals);
		$barchart2->legend_offset = 40;
		$barchart2->DrawLegendItem(2, '#43859B', 'ABs');
		$barchart2->Close();


		/* chart 1 of 2 */

		$vals = array();
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
		$barchart = new barchart($vals, 'framed');
		$barchart->Open();	
		$barchart->bar_color = '#43859B';
		$barchart->bar_color = '#A9CB7D';
		$barchart->cap_color = '#CFF1A3';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[1], $fields[3]);
		}
		$barchart->DrawTitle("BBB New {$iShowBillable} ABs over Time");
		$barchart->DrawTrendLine($vals);
		$barchart->legend_offset = 80;
		$barchart->DrawLegendItem(1, '#A9CB7D', 'New ABs');
		$barchart->Close();

	}
	else if (count($rs) > 0 && $output_type > "") {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('ABs', ''),
				array('Sales', ''),
				array('Drops', ''),
				array('Month/Year', ''),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					$fields[2],
					$fields[3],
				)
			);
		}
		$report->Close('suppress');
	}
	else {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteReportRow(array ('No records found'));
		$report->Close('suppress');
	}
}
	
$page->write_pagebottom();

?>