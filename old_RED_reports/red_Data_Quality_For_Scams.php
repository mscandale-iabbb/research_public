<?php

/*
 * 11/06/17 MJS - new file
 * 07/25/18 MJS - added option for id
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$userBBBID = $BBBID;

$iBBBID = Numeric2($_POST['iBBBID']);
if (! $_POST && $userBBBID != '2000') $iBBBID = $userBBBID;
else if (! $_POST && $userBBBID == '2000') $iBBBID = '1066';
$iSuppress = NoApost($_POST['iSuppress']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddTextField('iSuppress', '', $iSuppress, "visibility:hidden;" );
$SortFields = array(
	'Business name' => 'b.BusinessName',
	'ID' => 'b.BusinessID',
	'BBB city' => "BBB.NicknameCity,b.BusinessName",
	'Grade' => 'b.BBBRatingGrade',
	'Business Profile' => 'b.ReportURL'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	/* special option to suppress */
	if ($iSuppress) {
		$tmpfields = explode("|", $iSuppress);
		$iSuppressBBBID = $tmpfields[0];
		$iSuppressBusinessID = $tmpfields[1];
		$update = "
			UPDATE BusinessFlagScam SET
				Suppress = '1'
			WHERE BBBID = '{$iSuppressBBBID}' and BusinessID = '{$iSuppressBusinessID}'";
		$r = $conn->execute($update);
	}

	$query = "
		SELECT DISTINCT TOP {$iMaxRecs}
			b.BBBID,
			BBB.NickNameCity,
			b.BusinessID,
			b.BusinessName,
			b.ReportURL,
			b.BBBRatingGrade
		FROM BusinessFlagScam f WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON
			b.BBBID = f.BBBID and b.BusinessID = f.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			(b.IsBBBAccredited = '0' or b.IsBBBAccredited is null) and
			b.IsReportable = '1' and
			(f.Suppress = '0' or f.Suppress is null)
		";
	if ($iSortBy) {
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
				array('ID', $SortFields['ID'], '', 'left'),
				array('Name', $SortFields['Business name'], '', 'left'),
				array('Grade', $SortFields['Grade'], '', 'left'),
				array('Business Profile', $SortFields['Business Profile'], '', 'left'),
				array('', ''),
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

			$suppress_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This business has been examined and " .
				"should be removed from this list?')) { form1.iSuppress.value = '{$fields[0]}|{$fields[2]}'; " .
				"form1.submit(); } else return false; \">X</a>";

			$oReportURL = $fields[4];
			if (substr($oReportURL, 0, 4) != "http") $oReportURL = "http://" . $oReportURL;

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>" .
						NoApost($fields[1]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID={$fields[0]}&iBusinessID={$fields[2]}>" .
						NoApost($fields[2]) . "</a>",
					NoApost($fields[3]),
					$fields[5],
					"<a target=detail href={$oReportURL} >Click Here</a>",
					$suppress_button
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