<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 01/14/15 MJS - fixed bug where local BBBs couldn't select ALL
 * 02/10/16 MJS - added section for No Records Found
 * 01/03/17 MJS - changed calls to define links and tabs
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

// input
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
$iPeriod = NoApost($_REQUEST['iPeriod']);

if (! $_POST && $iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
if (! $iPeriod) $iPeriod = 'months';

// form
$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddTextField('iMonthFrom', 'Month range', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddRadio('iPeriod', 'Period', $iPeriod,
	array( 'Months' => 'months', 'Years' => 'years', )
);
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	if ($iPeriod == 'years') {
		$query = "
			declare @datefrom date;
			set @datefrom = CONVERT(datetime, '" . $iMonthFrom . "' + '/1/' + '" . $iYearFrom . "');
			declare @dateto date;
			set @dateto = CONVERT(datetime, '" . $iMonthTo . "' + '/1/' + '" . $iYearTo . "');
			select
				sum(s.CountOfReportableComplaints) as Complaints,
				[Year]
			from SnapshotStats s WITH (NOLOCK)
			inner join BBB WITH (NOLOCK) on BBB.BBBID = s.BBBID AND BBB.BBBBranchID = 0 AND BBB.IsActive = '1'
			where
				('" . $iBBBID . "' = '' or BBB.BBBID = '" . $iBBBID . "') and
				[Year] >= YEAR(@datefrom) and
				[Year] <= YEAR(@dateto)
			group by s.[Year]
			order by s.[Year]
			";
	}
	else {
		$query = "
			declare @datefrom date;
			set @datefrom = CONVERT(datetime, '" . $iMonthFrom . "' + '/1/' + '" . $iYearFrom . "');
			declare @dateto date;
			set @dateto = CONVERT(datetime, '" . $iMonthTo . "' + '/1/' + '" . $iYearTo . "');
			select
				sum(s.CountOfReportableComplaints) as Complaints,
				(CONVERT(varchar(7), CAST( [MonthNumber] as VARCHAR) + '/' + CAST( [Year] AS VARCHAR) )) AS ComplaintMonthYearText
			from SnapshotStats s WITH (NOLOCK)
			inner join BBB WITH (NOLOCK) on BBB.BBBID = s.BBBID AND BBB.BBBBranchID = 0 AND BBB.IsActive = '1'
			where
				('" . $iBBBID . "' = '' or BBB.BBBID = '" . $iBBBID . "') and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' + CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' + CAST( [Year] AS VARCHAR) ) <= @dateto
			group by s.[Year], s.MonthNumber
			order by s.[Year], s.MonthNumber
			";
	}

	$rsraw = $conn->execute("$query");
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();

	if (count($rs) > 0 && $output_type == "") {
		$report = new report( $conn, count($rs) );
	
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[0];
		}
	
		$barchart = new barchart($vals, 'framed');
		$barchart->Open();
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[0], $fields[1]);
		}
		$barchart->DrawTitle('BBB Complaints over Time');
		$barchart->DrawTrendLine($vals);
		$barchart->Close();
	}
	else if (count($rs) > 0 && $output_type > "") {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Complaints', ''),
				array('Month/Year', ''),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
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