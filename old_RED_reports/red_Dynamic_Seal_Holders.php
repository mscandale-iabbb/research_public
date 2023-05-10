<?php

/*
 * 05/14/15 MJS - new file
 * 07/26/16 MJS - redirect
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);

$page->Redirect('red_reports.php');

/*

$iBBBID = $_REQUEST['iBBBID'];
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];

$input_form = new input_form($conn);

$input_form->AddDateField('iDateFrom','Dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('yoursonly') );
$SortFields = array(
	'Business name' => 'gs.BusinessName',
	'BBB city' => 'BBB.NickNameCity,BBB.State,gs.BusinessName',
	'Date first seen' => 'gs.DateFirstSeen',
	'Date last seen' => 'gs.DateLastSeen',
	'Seal location' => 'gs.PageLastSeen',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT DISTINCT TOP {$iMaxRecs}
			gs.BBBID,
			gs.BusinessID,
			BBB.NickNameCity + ', ' + BBB.State,
			gs.BusinessName,
			CONVERT(CHAR(10), gs.DateFirstSeen, 120),
			CONVERT(CHAR(10), gs.DateLastSeen, 120),
			gs.PageLastSeen,
			gs.DateFirstSeen,
			gs.DateLastSeen,
			BBB.NickNameCity,
			BBB.State
		FROM B3Online.dbo.GeneratedSeals gs WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) on BBB.BBBID = gs.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			('{$iBBBID}' = '' or gs.BBBID = '{$iBBBID}') and
			gs.valid = 1 and
			gs.Datelastseen >= '{$iDateFrom}' and
			gs.Datelastseen <= '{$iDateTo}'
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
				array('BBB City', $SortFields['BBB city']),
				array('Business Name', $SortFields['Business name']),
				array('Date First Seen', $SortFields['Date first seen']),
				array('Date Last Seen', $SortFields['Date last seen']),
				array('Seal Location', $SortFields['Seal location']),
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
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . NoApost($fields[2]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[1] .  ">" . NoApost($fields[3]) . "</a>",
					FormatDate($fields[4]),
					FormatDate($fields[5]),
					$fields[6],
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

	
$page->write_pagebottom();

*/

?>