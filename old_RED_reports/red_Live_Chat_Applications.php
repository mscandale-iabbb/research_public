<?php

/*
 * 02/01/16 MJS - new file
 * 02/23/17 MJS - removed option for export type
 */

include '../intranet/init_allow_vendors.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckCouncilOnly($BBBID);


$iYear = ValidYear( Numeric2( GetInput('iYear',date('Y')) ) );
$iSortBy = $_POST['iSortBy'];
//if (! $iSortBy) $iSortBy = 'DateRegistered DESC';
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
//$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'number');
$SortFields = array(
	'Name' => 'LastName',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
//$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT LastName from EmployeeApplication WITH (NOLOCK)
		";
	if ($iSortBy > '') $query .= " ORDER BY " . $iSortBy;

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('Name', $SortFields['Name']),
			)
		);
		foreach ($rs as $k => $fields) {
			$name = "<a target=livechatdetails
				href='red_Live_Chat_Application_Details.php?iLastName={$fields[0]}'>{$fields[0]}</a>";
			$report->WriteReportRow(
				array (
					$name,
				),
				''
			);
		}
	}
	$report->Close();
	if ($iShowSource > '') $report->WriteSource($query);
}

$page->write_pagebottom();

?>