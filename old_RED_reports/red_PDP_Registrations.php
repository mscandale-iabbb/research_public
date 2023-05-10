<?php

/*
 * 02/01/16 MJS - new file
 * 08/25/16 MJS - align column headers
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
if (! $iSortBy) $iSortBy = 'DateRegistered DESC';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'number');
$SortFields = array(
	'Date' => 'DateRegistered',
	'Name' => 'LastName,FirstName',
	'BBB city' => 'NicknameCity',
	'Email' => 'Email',
	'Level' => 'SessionSelections',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @xyear smallint = '{$iYear}';		
		select
				ConferenceAttendee.DateRegistered,
				ConferenceAttendee.LastName + ', ' + ConferenceAttendee.FirstName,
				/*ConferenceAttendee.Nickname,*/
				ConferenceAttendee.Title,
				BBB.NickNameCity + ', ' + BBB.State,
				ConferenceAttendee.Email,
				ConferenceAttendee.CellPhone,
				ConferenceAttendee.SessionSelections,
				ConferenceAttendee.Address + ' ' + ConferenceAttendee.Address2 + ' ' +
					ConferenceAttendee.City + ', ' + ConferenceAttendee.State + ' ' +
					ConferenceAttendee.Zip,
				ConferenceAttendee.Notes
			from ConferenceAttendee WITH (NOLOCK)
			inner join BBB WITH (NOLOCK) on BBB.BBBID = ConferenceAttendee.BBBID AND BBB.BBBBranchID = 0
			where
				ConferenceAttendee.BBBBranchID = 0 and ConferenceAttendee.ConferenceID = 'PDP' and
				ConferenceAttendee.ConferenceYear = @xyear
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
				array('Date', $SortFields['Date'], '', 'left'),
				array('Name', $SortFields['Name'], '', 'left'),
				array('Title', '', '', 'left'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Email', $SortFields['Email'], '', 'left'),
				array('Cell Phone', '', '', 'left'),
				array('Level', $SortFields['Level'], '', 'left'),
				array('Billing Address', '', '', 'left'),
				array('Notes', '', '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					FormatDate($fields[0]),
					$fields[1],
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
					$fields[8],
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