<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
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
if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$iDateFrom = CleanDate( GetInput('iDateFrom',
	date( 'n/j/Y', strtotime('-7 days', strtotime( date('n') . '/1/' . date('Y') )) ) ) );
$iDateTo = CleanDate( GetInput('iDateTo',
	date( 'n/j/Y', strtotime('-1 day', strtotime( date('n') . '/1/' . date('Y') )) ) ) );
$iMaxRecs = Numeric2($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 's.clickdate DESC';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('yoursonly') );
$input_form->AddDateField('iDateFrom','Dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$SortFields = array(
	'Date' => 's.clickdate',
	'Business ID' => 'b.BusinessID',
	'Business name' => 'b.BusinessName',
	'Clicks' => 'clicks'
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
			s.BranchID,
			BBB.NickNameCity + ', ' + BBB.State,
			s.clickdate,
			s.BusinessID,
			b.BusinessName,
			count(*) as clicks
		FROM {$DB_SERVER_1}.CIBR.dbo.tblCIBR_Stats_Click s WITH (NOLOCK)
		LEFT OUTER JOIN BBB WITH (NOLOCK) on BBB.BBBID =
			s.BranchID and BBB.BBBBranchID = 0
		LEFT OUTER JOIN Business b WITH (NOLOCK) on b.BBBID =
			s.BranchID and b.BusinessID = s.BusinessID
		WHERE
			s.BranchID = '{$iBBBID}' and
			s.clickdate >= '{$iDateFrom}' and
			s.clickdate <= '{$iDateTo}'
		GROUP BY s.BranchID, BBB.NicknameCity, BBB.State, s.BusinessID, b.BusinessName, s.clickdate
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
				array('#'),
				array('BBB City', ''),
				array('Date', $SortFields['Date']),
				array('Business ID', $SortFields['Business name']),
				array('Business Name', $SortFields['Business name']),
				array('Clicks', $SortFields['clicks']),
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
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[1]) . "</a>",
					FormatDate($fields[2]),
					$fields[3],
					AddApost($fields[4]),
					$fields[5],
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