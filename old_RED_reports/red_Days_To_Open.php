<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 06/22/15 MJS - fixed bug in name of sort field
 * 07/06/15 MJS - disabled max records parameter
 * 07/20/15 MJS - fixed bug in sort field
 * 12/15/15 MJS - ensured Scam Tracker records won't appear
 * 07/26/16 MJS - excluded blank close codes
 * 08/24/16 MJS - aligned column headers
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBID = Numeric2($_REQUEST['iBBBID']);
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
else if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('yoursonly') );
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$SortFields = array(
	'ID' => 'c.ComplaintID',
	'Date closed' => 'c.DateClosed',
	'Date filed' => 'DateFiled',
	'Date opened' => 'DateOpened',
	'Raw days to open' => 'RawDaysOpen',
	'Weekdays to open' => 'DaysOpen'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption('hidemaxrecs');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT /* no MaxRecs */
			ComplaintID,
			c.DateClosed,
			c.DateComplaintFiledWithBBB as DateFiled,
			c.DateComplaintOpenedByBBB as DateOpened,
			DATEDIFF( day, c.DateComplaintFiledWithBBB, c.DateComplaintOpenedByBBB ) as RawDaysOpen,
			dbo.CDW_WEEKDAYDIFF(c.DateComplaintFiledWithBBB,c.DateComplaintOpenedByBBB) as DaysOpen
		FROM BusinessComplaint c WITH (NOLOCK) WHERE
			('{$iBBBID}' = '' OR c.BBBID = '{$iBBBID}') and
			c.DateClosed >= '{$iDateFrom}' and
			c.DateClosed <= '{$iDateTo}' and
			c.DateComplaintFiledWithBBB IS NOT NULL AND
			c.DateComplaintOpenedByBBB IS NOT NULL AND
			c.DateComplaintFiledWithBBB <= c.DateComplaintOpenedByBBB and
			c.ComplaintID not like 'scam%' and
			c.CloseCode is not null and c.CloseCode > 0
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) AbortREDReport($query);
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('#', '', '', 'right'),
				array('ID', $SortFields['ID'], '', 'left'),
				array('Date Closed', $SortFields['Date closed'], '', 'left'),
				array('Date Filed', $SortFields['Days filed'], '', 'left'),
				array('Date Opened', $SortFields['Days opened'], '', 'left'),
				array('Raw Days to Open', $SortFields['Raw days to open'], '', 'right'),
				array('Weekdays to Open', $SortFields['Weekdays to open'], '', 'right')
				)
			);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_REQUEST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;

		foreach ($rs as $k => $fields) {
			$xcount++;

			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;

			$report->WriteReportRow(
				array (
					$xcount,
					$fields[0],
					FormatDate($fields[1]),
					FormatDate($fields[2]),
					FormatDate($fields[3]),
					$fields[4],
					$fields[5]
					)
				);
		}
		$report->WriteTotalsRow(
			array (
				'Average',
				'',
				'',
				'',
				'',
				'',
				round( array_sum( get_array_column($rs, 5) ) /
					count( get_array_column($rs, 5) ), 2)
			)
		);
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>