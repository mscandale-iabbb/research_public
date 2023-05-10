<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 07/06/15 MJS - disabled max records parameter
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
	'Days to close' => 'DaysToClose',
	'Consumer last name' => 'ConsumerLastName'
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
			DateClosed,
			CountOfDaysToProcessComplaint as DaysToClose,
			ConsumerLastName
		FROM BusinessComplaint c WITH (NOLOCK) WHERE
			('{$iBBBID}' = '' OR c.BBBID = '{$iBBBID}') and
			c.DateClosed >= '{$iDateFrom}' and
			c.DateClosed <= '{$iDateTo}' and
			CountOfDaysToProcessComplaint >= 0 and
			CloseCode != '400' and
			CloseCode is not null and CloseCode > 0 and
			c.ComplaintID not like 'scam%'
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute("$query");
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
				array('Consumer Last Name', $SortFields['Consumer last name'], '', 'left'),
				array('Date Closed', $SortFields['Date closed'], '', 'left'),
				array('Days to Close per Vendor', $SortFields['Days to close'], '', 'right'),
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
					AddApost($fields[3]),
					FormatDate($fields[1]),
					$fields[2]
					)
				);
		}
		$report->WriteTotalsRow(
			array (
				'Average',
				'',
				'',
				'',
				round( array_sum( get_array_column($rs, 2) ) /
					count( get_array_column($rs, 2) ), 2)
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