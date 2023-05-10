<?php

/*
 * 01/19/16 MJS - new file
 * 01/20/16 MJS - fixed typo in sort criteria
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

$iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') - 1) ) );
$iSortBy = $_POST['iSortBy'];
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'number');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Email' => 'p.Email',
	'Year' => 'y.[Year]',
	'Inquiries' => 'y.CountOfInquiries',
	'Complaints' => 'y.CountOfComplaints',
	'ABs' => 'y.CountOfABsYearEnd',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @xyear smallint;
		set @xyear = '{$iYear}';
		
		SELECT
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State as BBB,
			p.Email,
			y.[Year],
			y.CountOfInquiries as Inquiries,
			y.CountOfComplaints as Complaints,
			y.CountOfABsYearEnd as ABs
		FROM BBB WITH (NOLOCK)
		LEFT OUTER JOIN YearlyStats y WITH (NOLOCK) ON y.BBBID = BBB.BBBID and
			y.[Year] = @xyear
		LEFT OUTER JOIN BBBPerson p WITH (NOLOCK) ON
			p.BBBID = BBB.BBBID AND p.CEO = 1
		WHERE
			BBB.BBBBranchID = '0' and BBB.IsActive = '1'
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
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('CEO Email', $SortFields['Email'], '', 'left'),
				array('Year', $SortFields['Year'], '', 'left'),
				array('Inquiries', $SortFields['Inquiries'], '', 'right'),
				array('Complaints', $SortFields['Complaints'], '', 'right'),
				array('ABs', $SortFields['ABs'], '', 'right'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>" .
						AddApost($fields[1]) . "</a>",
					$fields[2],
					strval($fields[3]),
					intval($fields[4]),
					intval($fields[5]),
					intval($fields[6]),
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