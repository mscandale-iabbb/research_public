<?php

/*
 * 09/18/19 MJS - new file
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
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];

$input_form = new input_form($conn);

$input_form->AddDateField('iDateFrom','Dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$SortFields = array(
	'Business name' => 'b.BusinessName',
	'BBB city' => 'BBB.NicknameCity,b.BusinessName',
	'Joined' => 'joined',
	'Dropped' => 'dropped',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT TOP " . $iMaxRecs . "
			b.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			b.BusinessID,
			b.BusinessName,
			p.DateFrom as joined,
			p.DateTo as dropped
		FROM BusinessProgramParticipation p WITH (NOLOCK)
		INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = p.BBBID and b.BusinessID = p.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			(p.BBBProgram = 'Membership' or p.BBBProgram = 'BBB Accredited Business') and
			p.DateTo >= '{$iDateFrom}' AND p.DateTo < '{$iDateTo}' and
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}')
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
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('Name', $SortFields['Business name'], '', 'left'),
				array('Joined', $SortFields['Joined'], '', 'left'),
				array('Dropped', $SortFields['Dropped'], '', 'left'),
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
						">" . NoApost($fields[1]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[2] .  ">" . NoApost($fields[3]) . "</a>",
					FormatDate($fields[4]),
					FormatDate($fields[5]),
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

?>