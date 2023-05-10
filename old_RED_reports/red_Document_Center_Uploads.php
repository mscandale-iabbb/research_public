<?php

/*
 * 11/12/15 MJS - new file
 * 11/13/15 MJS - fixed bug in link names
 * 11/13/15 MJS - changed default sort order to date uploaded
 * 12/15/15 MJS - restricted access to CBBB only
 * 08/25/16 MJS - align column headers
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


$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'DateFileUploaded DESC,BBB.NicknameCity';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$SortFields = array(
	'Date uploaded' => 'DateFileUploaded,BBB.NicknameCity',
	'BBB city' => 'NicknameCity',
	'Description' => 'DocumentTypeID,BBB.NicknameCity'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			NicknameCity + ', ' + State as 'BBB City',
			DocumentTypeID,
			DateFileUploaded,
			REPLACE(DocumentFileName,'/usr2/bbb-services/intranet/uploads/','https://bbb-services.bbb.org/intranet/uploads/') as Link
		FROM BBBDocument d WITH (NOLOCK)
		LEFT OUTER JOIN BBB WITH (NOLOCK) ON BBB.BBBIDFull = d.BBBIDFull
		WHERE
			d.BBBIDFull != '2000' and
			not d.DocumentTypeID in ('AutoLineMap', 'AutoLineFutureMap', 'RegionByLaws', 'iBBBFile', 'iBBBFile2', 'iBBBFile3') and
			d.DocumentTypeID not like 'CDS%'
		";
	if ($iSortBy) $query .= " ORDER BY " . $iSortBy;

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Description', $SortFields['Description'], '', 'left'),
				array('Date Uploaded', $SortFields['Date uploaded'], '', 'left'),
				array('Link', '', '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {

			$link = '';
			if ($fields[3]) $link = "<a target=_new href='" . $fields[3] . "'>" . $fields[3] . "</a>";

			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					FormatDate($fields[2]),
					$link
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>