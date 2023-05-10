<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 02/10/16 MJS - added section for No Records Found
 * 04/19/16 MJS - locked out vendors
 * 01/09/17 MJS - changed calls to define links and tabs
 * 08/07/17 MJS - cleaned up code
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);
$page->CheckBBBOnly($BBBID);

$iBBBID = $_REQUEST['iBBBID'];
$iYearFrom = Numeric2( GetInput('iYearFrom',date('Y') - 10) );
$iYearTo = Numeric2( GetInput('iYearTo',date('Y') - 2) );
$iShowSource = $_POST['iShowSource'];

if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddTextField('iYearFrom', 'Years', $iYearFrom, "width:50px;", '', 'year');
$input_form->AddTextField('iYearTo', ' to ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		select
			ROUND( SUM(f.TotalRevenue) / 1000000, 1),
			ROUND( SUM(f.TotalExpenses) / 1000000, 1),
			ROUND( SUM(f.EndFundBalance) / 1000000, 1),
			f.[Year] as Year
		FROM BBBFinancials f WITH (NOLOCK)
		where
			('{$iBBBID}' = '' or f.BBBID = '{$iBBBID}') and
			f.[Year] >= '{$iYearFrom}' and
			f.[Year] <= '{$iYearTo}'
		GROUP BY f.[Year]
		ORDER BY f.[Year]
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	
	if (count($rs) > 0 && $output_type == "") {
		$report = new report( $conn, count($rs) );
	
		foreach ($rs as $k => $fields) {
			$vals[] = max($fields[0], $fields[1], $fields[2]);
		}

		$barchart = new barchart($vals, 'framed');
		$barchart->bar_width = 27;
		$barchart->offset_factor = 90;
		$barchart->Open('suppress_average');
	
		$barchart->bar_color = '#6F9F39';
		$barchart->cap_color = '#87B157';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[0], '');
		}
	
		$barchart->bar_color = '#BF2D19';
		$barchart->cap_color = '#9D2B1D';
		$xcount = 0.30;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[1], $fields[3]);
		}
	
		$barchart->bar_color = '#635F5B';
		$barchart->cap_color = '#E3DFDB';
		$xcount = 0.60;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[2], '');
		}
	
		$barchart->DrawTitle('BBB Finances over Time');
		$barchart->trendline_color = '#6F9F39';
		$barchart->DrawTrendLine($vals);
		$barchart->legend_offset = 60;
		$barchart->DrawLegendItem(1, '#635F5B', 'Balance');
		$barchart->DrawLegendItem(2, '#BF2D19', 'Expense');
		$barchart->DrawLegendItem(3, '#6F9F39', 'Revenue');
		$barchart->Close();
	}
	else if (count($rs) > 0 && $output_type > "") {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Revenue', ''),
				array('Expenses', ''),
				array('Fund Balance', ''),
				array('Year', ''),
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

	if (count($rs) == 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteReportRow(array ('No records found'));
		$report->Close('suppress');
	}

}
	
$page->write_pagebottom();

?>