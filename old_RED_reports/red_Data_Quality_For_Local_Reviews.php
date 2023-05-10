<?php

/*
 * 07/31/17 MJS - new file
 * 03/22/18 MJS - excluded out of business
 * 10/09/19 MJS - modified query - commented out criteria retrieving non-accredited
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBID = Numeric2($_POST['iBBBID']);
if (! $iBBBID && $BBBID != '2000') $iBBBID = $BBBID;
else if (! $iBBBID && $BBBID == '2000') $iBBBID = '1066';
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('') );
$SortFields = array(
	'Local review name' => 'b.BusinessName',
	'Single review BBB' => 'BBB.NicknameCity',
	'Single review name' => 'Single.BusinessName',
	'Single review grade' => 'Single.BBBRatingGrade'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$column_OOB = "OutOfBusinessTypeId";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$column_OOB = "OutOfBusinessStatusTypeId";
	}

	$query = "
		SELECT TOP {$iMaxRecs}
			b.BusinessID,
			b.BusinessName,
			BBB.NicknameCity,
			Single.BBBID,
			Single.BusinessID,
			Single.BusinessName,
			case when Single.IsBBBAccredited = '1' then 'Yes' else 'No' end,
			Single.BBBRatingGrade as 'Rating',
			case when b.IsBBBAccredited = '1' then 'Yes' else 'No' end
		FROM Business b WITH (NOLOCK)
		INNER JOIN Business Single WITH (NOLOCK) on
			Single.BBBID = b.ReportingBBBID and Single.BusinessID = b.ReportingBusinessID
		INNER JOIN BBB WITH (NOLOCK) on BBB.BBBID = Single.BBBID AND BBB.BBBBranchID = '0'
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		WHERE
			b.BBBID = '{$iBBBID}' and
			b.ReportType = 'Local' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			b.IsReportable = '1' and
			(
				(
					b.IsBBBAccredited = '1' and
					Single.BBBRatingGrade in ('B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'F')
				) /* or
				(
					(b.IsBBBAccredited = '0' or b.IsBBBAccredited is null) and
					Single.IsBBBAccredited = '1'
				) */
			)
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
				array('Local Review Name', $SortFields['Local review name'], '', 'left'),
				array('AB', '', '', 'left'),
				array('Single Review BBB', $SortFields['Single review BBB'], '', 'left'),
				array('Single Review Name', $SortFields['Single review name'], '', 'left'),
				array('AB', '', '', 'left'),
				array('Single Review Grade', $SortFields['Single review grade'], '', 'left'),
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
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $iBBBID .
						"&iBusinessID={$fields[0]}>{$fields[1]}</a>",
					$fields[8],
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[3]}>" .
						"BBB {$fields[2]}</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[3] .
						"&iBusinessID={$fields[4]}>{$fields[5]}</a>",
					$fields[6],
					$fields[7],
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