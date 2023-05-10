<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 12/19/14 MJS - added Contact Info field
 * 04/20/16 MJS - locked vendors out
 * 08/25/16 MJS - aligned column headers
 * 11/10/16 MJS - changed REQUEST to POST
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckBBBOnly($BBBID);


$iBBBID = Numeric2($_POST['iBBBID']);
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
else if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 's.DateSubmitted DESC';
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('yoursonly') );
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$SortFields = array(
	'Date' => 's.DateSubmitted',
	'BBB City' => 'BBB.NickNameCity',
	'Business name' => 'b.BusinessName'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT TOP " . $iMaxRecs . "
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			s.DateSubmitted,
			b.BusinessName,
			s.HowToImprove,
			s.ContactInfo
		FROM BusinessReviewSurveys s WITH (NOLOCK)
		LEFT OUTER JOIN BBB WITH (NOLOCK) on BBB.BBBID =
			s.BBBID and BBB.BBBBranchID = 0
		LEFT OUTER JOIN Business b WITH (NOLOCK) on b.BBBID =
			s.BBBID and b.BusinessID = s.BusinessID
		WHERE
			s.BBBID = '{$iBBBID}' and
			s.DateSubmitted >= '{$iDateFrom}' and
			s.DateSubmitted <= '{$iDateTo}' and
			s.HowToImprove > '' and
			(s.HowToImproveIsJunk is null or s.HowToImproveIsJunk = '0') and
			s.SurveyType != 'Business-Review-IsHelpful'
		";
	if ($iSortBy > '') {
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
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Date', $SortFields['Date'], '', 'left'),
				array('Business', $SortFields['Business name'], '', 'left'),
				array('How To Improve', '', '', 'left'),
				array('Contact Info', '', '', 'left'),
			)
		);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_POST['output_type'] > '') $iPageSize = count($rs);
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
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[1]) . "</a>",
					FormatDate($fields[2]),
					AddApost($fields[3]),
					AddApost($fields[4]),
					AddApost($fields[5]),
				),
				''
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