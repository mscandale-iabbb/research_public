<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 03/13/15 MJS - added 150 close code
 * 12/16/15 MJS - ensured Scam Tracker records won't appear
 * 08/25/15 MJS - aligned column headers
 * 01/03/17 MJS - changed calls to define links and tabs
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$SortFields = array(
	'Vendor' => 'Vendor,NicknameCity',
	'BBB city' => 'NicknameCity',
	'Last checked' => 'lastchecked,NicknameCity',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State as BBB,
			BBB.Vendor,
			( select CDWLastUpdate from qaBusiness WITH (NOLOCK) where
				qaBusiness.BBBID = BBB.BBBID ) as lastchecked,
			( select COUNT(*) from BusinessComplaint WITH (NOLOCK) where
				BusinessComplaint.BBBID = BBB.BBBID and
				DateClosed >= '1/1/" . date('Y') . "' AND
				DateClosed <= GETDATE() and
				CloseCode IN ('110','111','112','120','121','122','150','200','300') and
				ComplaintID not like 'scam%')
				as Complaints,
			( select sum( CountOfReportableBusinessComplaintsYTD ) from
				qaBusinessComplaint WITH (NOLOCK) where
				qaBusinessComplaint.BBBID = BBB.BBBID ) as ComplaintsQA,
			( select sum(countTotal) from BusinessInquiry WITH (NOLOCK)
				where BusinessInquiry.BBBID = BBB.BBBID and
				DateOfInquiry >= '1/1/" . date('Y') . "' and
				DateOfInquiry <= GETDATE() ) as Inquiries,
			( select sum( CountOfBusinessInquiriesYTD ) from qaBusinessInquiry
				WITH (NOLOCK) where qaBusinessInquiry.BBBID = BBB.BBBID )
				as InquiriesQA,
			( select count(distinct Business.BusinessID) from Business
				WITH (NOLOCK)
				inner join BusinessProgramParticipation WITH (NOLOCK)
				on BusinessProgramParticipation.BBBID = Business.BBBID AND
				BusinessProgramParticipation.BusinessID = Business.BusinessID and
				(BBBProgram = 'Membership' or
				BBBProgram = 'BBB Accredited Business')
				where Business.BBBID = BBB.BBBID and
				DateFrom >= '1/1/" . date('Y') . "' AND DateFrom <= GETDATE() AND
				(DateFrom != '1/1/1900' OR DateTo != '1/1/1900') AND
				(NOT DateFrom IS NULL OR NOT DateTo IS NULL) ) as NewABs,
			( select sum( CountOfNewABsYTD ) from qaBusiness WITH (NOLOCK)
				where qaBusiness.BBBID = BBB.BBBID ) as NewABsQA,
			( select count(*) from Business WITH (NOLOCK) where
				Business.BBBID = BBB.BBBID and IsBBBAccredited = '1' )
				as ABs,
			( select sum( CountOfABs ) from qaBusiness WITH (NOLOCK) where
				qaBusiness.BBBID = BBB.BBBID ) as ABsQA,
			( select count(*) from Business WITH (NOLOCK) where
				Business.BBBID = BBB.BBBID ) as Businesses,
			( select sum( CountOfBusinesses ) from qaBusiness WITH (NOLOCK) where
				qaBusiness.BBBID = BBB.BBBID ) as BusinessesQA /*,
			( select COUNT(*) from BusinessAdReview WITH (NOLOCK) where
				BusinessAdReview.BBBID = BBB.BBBID and
				BusinessAdReview.DateClosed >= '1/1/" . date('Y') . "' AND
				BusinessAdReview.DateClosed <= GETDATE() ) as AdReview,
			( select sum( CountOfBusinessAdReviewsYTD ) from
				qaBusinessAdReview WITH (NOLOCK) where
				qaBusinessAdReview.BBBID = BBB.BBBID ) as AdReviewQA */
		FROM BBB WITH (NOLOCK)
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}')
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Vendor', $SortFields['Vendor'], '', 'left'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Checked', $SortFields['Last checked'], '', 'left'),
				array('Complaints YTD', $SortFields['xxxx'], '', 'left'),
				array('Inquiries YTD', $SortFields['xxxx'], '', 'left'),
				array('New ABs YTD', $SortFields['xxxx'], '', 'left'),
				array('ABs Now YTD', $SortFields['xxxx'], '', 'left'),
				array('Businesses Now', $SortFields['xxxx'], '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[2],
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[1]) . "</a>",
					FormatDate($fields[3]),
					FormatPercentage($fields[4] / $fields[5], 1) . '<br/>' .
						$fields[4] . '/' . $fields[5],
					FormatPercentage($fields[6] / $fields[7], 1) . '<br/>' .
						$fields[6] . '/' . $fields[7],
					FormatPercentage($fields[8] / $fields[9], 1) . '<br/>' .
						$fields[8] . '/' . $fields[9],
					FormatPercentage($fields[10] / $fields[11], 1) . '<br/>' .
						$fields[10] . '/' . $fields[11],
					FormatPercentage($fields[12] / $fields[13], 1) . '<br/>' .
						$fields[12] . '/' . $fields[13]
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				'',
				'',
				FormatPercentage(
					array_sum( get_array_column($rs, 4) ) /
					array_sum( get_array_column($rs, 5) ),
				1),
				FormatPercentage(
					array_sum( get_array_column($rs, 6) ) /
					array_sum( get_array_column($rs, 7) ),
				1),
				FormatPercentage(
					array_sum( get_array_column($rs, 8) ) /
					array_sum( get_array_column($rs, 9) ),
				1),
				FormatPercentage(
					array_sum( get_array_column($rs, 10) ) /
					array_sum( get_array_column($rs, 11) ),
				1),
				FormatPercentage(
					array_sum( get_array_column($rs, 12) ) /
					array_sum( get_array_column($rs, 13) ),
				1),
			)
		);
		$report->Close();
	}
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>