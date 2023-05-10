<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 11/12/15 MJS - fixed bug with phone number
 * 08/25/16 MJS - aligned column headers
 * 06/07/17 MJS - cleaned up code
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$SortFields = array(
	'Name' => 'p.LastName,p.FirstName',
	'Title' => 'p.Title,p.LastName,p.FirstName',
	'Phone' => 'ph.PhoneNumber,p.LastName,p.FirstName',
	'Email' => 'p.Email,p.LastName,p.FirstName',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT
			p.LastName + ', ' + p.FirstName,
			p.Title,
			ph.PhoneNumber,
			p.Email,
			ph.Extension
		FROM BBBPerson p WITH (NOLOCK)
		LEFT OUTER JOIN BBBPhone ph WITH (NOLOCK) ON
			ph.BBBID = p.BBBID and ph.BBBBranchID = p.BBBBranchID and ph.PhoneID = p.PhoneID
		WHERE
			p.BBBID = 2000 and p.BBBBranchID = 0
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report($conn, count($rs));
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('Name', $SortFields['Name'], '', 'left'),
				array('Title', $SortFields['Title'], '', 'left'),
				array('Phone', $SortFields['Phone'], '', 'left'),
				array('Email', $SortFields['Email'], '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			$phone = FormatPhone($fields[2]);
			if ($fields[4]) $phone .= " " . $fields[4];
			$report->WriteReportRow(
				array (
					AddApost($fields[0]),
					AddApost($fields[1]),
					$phone,
					FormatEmail($fields[3]),
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>