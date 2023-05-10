<?php

/*
 * 10/11/17 MJS - new file
 * 10/12/17 MJS - added options for date fields
 * 03/22/18 MJS - excluded out of business
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
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('') );
$input_form->AddDateField('iDateFrom','Claimed from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$SortFields = array(
	'Local report name' => 'Local.BusinessName',
	'Local report BBB' => 'BBB.NicknameCity',
	'Single report name' => 'Single.BusinessName',
	'Date claimed' => 'lr.DateEntered'
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
			Local.BBBID,
			BBB.NicknameCity,
			Local.BusinessID,
			Local.BusinessName,
			Local.ReportType,
			Local.ReportingBBBID,
			Local.ReportingBusinessID,
			Single.BBBID,
			Single.BusinessID,
			Single.BusinessName,
			lr.DateEntered
		FROM LocalReports lr WITH (NOLOCK)
		INNER JOIN Business Single WITH (NOLOCK) on
			Single.BBBID = lr.BBBIDofSingleReport and Single.BusinessID = lr.BusinessIDofSingleReport
		INNER JOIN Business Local WITH (NOLOCK) on
			Local.BBBID = lr.BBBID and Local.BusinessID = lr.BusinessID
		INNER JOIN BBB WITH (NOLOCK) on BBB.BBBID = Local.BBBID AND BBB.BBBBranchID = '0'
		left outer join {$table_Org} o WITH (NOLOCK) on Local.BBBID = o.BureauCode and Local.BusinessID = o.SourceBusinessId
		WHERE
			lr.BBBIDofSingleReport = '{$iBBBID}' and
			lr.DateEntered >= '{$iDateFrom}' and lr.DateEntered <= '{$iDateTo}' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(Local.BOConlyIsOutOfBusiness is null or Local.BOConlyIsOutOfBusiness = '0') and
			Local.IsReportable = '1' and
			Local.ReportType = 'Standard'
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
				array('Local Report BBB', $SortFields['Local report BBB'], '', 'left'),
				array('Local Report Business Name', $SortFields['Local report name'], '', 'left'),
				array('Local Report Type', '', '', 'left'),
				array('Local Report Points To', '', '', 'left'),
				array('Single Report Business Name', $SortFields['Single report name'], '', 'left'),
				array('Date Claimed', $SortFields['Date claimed'], '', 'left'),
				array('Remind', '', '', 'left'),
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

			if ( AccessLocalReviews($conn, $BBBID, $_SESSION['LoweredEmail']) ) {
				$remind_button =
					"<form target=_blank name=form{$xcount} action=locrev_assign.php name='form{$xcount}' method=post>" .
					"<input type=hidden name=iBusinessID value='{$fields[8]}'>" .
					"<input type=checkbox name=checkbox[] id='checkbox[]' " .
					"value='{$fields[7]}|{$fields[8]}|{$fields[0]}|{$fields[2]}|{$fields[3]}' " .
					"onclick=\" if (confirm('Send email to other BBB asking to update its " .
					"database?')) { form{$xcount}.submit(); } else return false; \">" .
					"</form>";
			}
			else {
				$remind_button = "";
			}

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>" .
						"BBB {$fields[1]}</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[2]}>{$fields[3]}</a>",
					$fields[4],
					"(Unlinked)", /*$fields[5] . " " . $fields[6]*/
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[7] .
						"&iBusinessID={$fields[8]}>{$fields[9]}</a>",
					FormatDate($fields[10]),
					$remind_button
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