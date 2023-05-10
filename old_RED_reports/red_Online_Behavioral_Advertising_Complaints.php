<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 12/15/15 MJS - restricted access to CBBB only
 * 08/26/16 MJS - align column headers
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckCouncilOnly($BBBID);


$iDateFrom = CleanDate( GetInput('iDateFrom',
	date( 'n/j/Y', strtotime('-1 day', strtotime( date('n/j/Y') )) ) ) );
$iDateTo = CleanDate( GetInput('iDateTo',
	date( 'n/j/Y', strtotime('-0 days', strtotime( date('n/j/Y') )) ) ) );
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];

if (! $iSortBy) $iSortBy = 'date_of_submission DESC';


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$SortFields = array(
	'Submission Date' => 'date_of_submission',
	'URL' => 'url_where_found',
	'Advertiser' => 'name_of_advertiser',
	'Product' => 'product_advertised',
	'Browser' => 'browser_used',
	'Email' => 'email_of_user',
	'Referer' => 'referer',
	'Details' => 'report_details',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT TOP " . $iMaxRecs . "
			REPLACE(p.url_where_found,'\"','''') as url_where_found,
			CAST(REPLACE(p.date_of_submission,'\"','''') as DATETIME) as date_of_submission,
			REPLACE(p.name_of_advertiser,'\"','''') as name_of_advertiser,
			REPLACE(p.product_advertised,'\"','''') as product_advertised,
			REPLACE(p.browser_used,'\"','''') as browser_used,
			REPLACE(p.email_of_user,'\"','''') as email_of_user,
			REPLACE(p.referer,'\"','''') as referer,
			REPLACE(p.report_details,'\"','''') as report_details
		FROM " . $DB_SERVER_1 . ".BBBODR.dbo.OBA_report_form_submissions p WITH (NOLOCK)
		WHERE
			CAST (p.date_of_submission as DATE) >= '" . $iDateFrom . "' AND
			CAST (p.date_of_submission as DATE) <= '" . $iDateTo . "'
		";
	if ($iSortBy > '') {
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
				array('#', ''. '', 'right'),
				array('URL Where Found', $SortFields['URL'], '', 'left'),
				array('Submission Date', $SortFields['Submission Date'], '', 'left'),
				array('Name of Advertiser', $SortFields['Advertiser'], '', 'left'),
				array('Product Advertised', $SortFields['Product'], '', 'left'),
				array('Browser Used', $SortFields['Browser'], '', 'left'),
				array('User Email', $SortFields['Email'], '', 'left'),
				array('Referer', $SortFields['Referer'], '', 'left'),
				array('Report Details', $SortFields['Details'], '', 'left'),
			)
		);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
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
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
				),
				'sized'
			);
		}
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

	
$page->write_pagebottom();

?>