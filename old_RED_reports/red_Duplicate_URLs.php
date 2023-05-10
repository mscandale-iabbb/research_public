<?php

/*
 * 06/21/17 MJS - new file
 * 06/22/17 MJS - added AB column and input field
 * 06/22/17 MJS - added HQ column and input field
 * 06/22/17 MJS - removed single/local and systemwide
 * 06/27/17 MJS - added BID column
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
$iAB = NoApost($_REQUEST['iAB']);
$iHQ = NoApost($_REQUEST['iHQ']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddSelectField('iHQ','HQ status',$iHQ, array('Both' => '', 'HQ' => '1', 'Non-HQ' => '0') );
$SortFields = array(
	'URL' => 'u.CondensedURL'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	$query = "
		SELECT TOP {$iMaxRecs}
			b.BBBID,
			b.BusinessID,
			u.URL,
			u.CondensedURL,
			case when b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			Case When b.IsHQ = '1' then 'Yes' else 'No' end as HQ
		FROM BusinessURL u WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON
			b.BBBID = u.BBBID and b.BusinessID = u.BusinessID
		left outer join SystemwideAB sab WITH (NOLOCK) ON
			sab.BBBID = b.BBBID and sab.BusinessID = b.BusinessID
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.ReportType != 'Single' and b.ReportType != 'Local' and
			sab.BusinessID is null and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			(
				('{$iHQ}' = '') or
				('{$iHQ}' = '1' and b.IsHQ = 1) or
				('{$iHQ}' = '0' and (b.IsHQ = 0 or b.IsHQ is null))
			) and
			u.URL > '' and u.CondensedURL > '' and
			u.CondensedURL not like '% %' and u.CondensedURL like '%.%' and
			u.IsPrimaryURL = '1' and
			u.PublishToCIBR = '1' and
			b.IsReportable = '1' and b.PublishToCIBR = '1' and
			(
				select count(*) from BusinessURL u2 WITH (NOLOCK)
				inner join Business b2 WITH (NOLOCK) ON
					b2.BBBID = u2.BBBID and b2.BusinessID = u2.BusinessID
				left outer join SystemwideAB sab2 WITH (NOLOCK) ON
					sab2.BBBID = b2.BBBID and sab2.BusinessID = b2.BusinessID
				where
					u2.CondensedURL = u.CondensedURL and
					u2.BBBID != u.BBBID and
					b2.ReportType != 'Single' and b2.ReportType != 'Local' and
					sab2.BusinessID is null and
					u2.IsPrimaryURL = '1' and
					u2.PublishToCIBR = '1' and
					b2.IsReportable = '1' and b2.PublishToCIBR = '1'
			) > 0
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
				array('Your URL', $SortFields['URL'], '', 'left'),
				array('ID', '', '', 'right'),
				array('AB', '', '', 'left'),
				array('HQ', '', '', 'left'),
				array('Other BBBs', '', '', 'left'),
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

			$OtherRecords = '';
			$subquery = "
				SELECT TOP 10
					BBB.NickNameCity,
					BBB.State,
					u.BBBID,
					u.BusinessID
				FROM BusinessURL u WITH (NOLOCK)
				inner join Business b WITH (NOLOCK) ON
					b.BBBID = u.BBBID and b.BusinessID = u.BusinessID
				inner join BBB WITH (NOLOCK) on BBB.BBBID = u.BBBID AND BBB.BBBBranchID = '0'
				left outer join SystemwideAB sab WITH (NOLOCK) ON
					sab.BBBID = b.BBBID and sab.BusinessID = b.BusinessID
				WHERE
					u.CondensedURL = '{$fields[3]}' and
					u.BBBID != '{$fields[0]}' and
					b.ReportType != 'Single' and b.ReportType != 'Local' and
					sab.BusinessID is null and
					u.IsPrimaryURL = '1' and
					u.PublishToCIBR = '1' and
					b.IsReportable = '1' and b.PublishToCIBR = '1'
				ORDER BY BBB.NicknameCity
				";
			$rs2raw = $conn->execute($subquery);
			if (! $rs2raw) AbortREDReport($subquery);
			$rs2 = $rs2raw->GetArray();
			$ycount = 0;
			foreach ($rs2 as $k2 => $fields2) {
				$OtherRecords .= "<a target=detail href=red_Business_Details.php?iBBBID=" .
					$fields2[2] . "&iBusinessID={$fields2[3]}>{$fields2[0]}</a> &nbsp;";
				$ycount++;
			}
			if ($ycount == 10) $OtherRecords .= "...";

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[1]}>{$fields[3]}</a>",
					$fields[1],
					$fields[4],
					$fields[5],
					$OtherRecords
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