<?php

/*
 * 06/05/18 MJS - new file
 * 06/06/18 MJS - changed name column to link
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
$iTOB = NoApost($_REQUEST['iTOB']);
$iTOBCode = NoApost($_REQUEST['iTOBCode']);
$iDeleteURL = NoApost($_POST['iDeleteURL']);
$iSuppress = NoApost($_POST['iSuppress']);
$iNotChallengeable = NoApost($_POST['iNotChallengeable']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddSelectField('iAB','Businesses of AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddTextField('iTOB','Business TOB description contains',$iTOB);
$input_form->AddMultipleSelectField('iTOBCode', 'Business TOB code', $iTOBCode, $input_form->BuildTOBsArray(),
	'width:300px;', '', '', 'width:300px;');
$input_form->AddTextField('iSuppress', '', $iSuppress, "visibility:hidden;" );
$input_form->AddTextField('iNotChallengeable', '', $iNotChallengeable, "visibility:hidden;" );
$SortFields = array(
	'ID' => 'b.BusinessID',
	'BBB city' => "BBB.NicknameCity,b.BusinessName",
	'Business name' => 'b.BusinessName',
	'AB' => 'AB',
	'TOB' => 'yppa_text'
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
		$update = "UPDATE FreeTrial SET Suppress = '1' WHERE BBBID = '" . substr($iSuppress,0,4) . "' and BusinessID = '" . substr($iSuppress,5) . "'";
		$r = $conn->execute($update);
	}

	/* special option to mark as not challengeable */
	if ($iNotChallengeable) {
		$update = "UPDATE FreeTrial SET NotChallengeable = '1' WHERE BBBID = '" . substr($iNotChallengeable,0,4) . "' and BusinessID = '" . substr($iNotChallengeable,5) . "'";
		$r = $conn->execute($update);
	}
 	
	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$column_OOB = "OutOfBusinessTypeId";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$column_OOB = "OutOfBusinessStatusTypeId";
	}

	$query = "
		SELECT DISTINCT TOP {$iMaxRecs}
			b.BBBID,
			BBB.NickNameCity,
			BBB.State,
			b.BusinessID,
			b.BusinessName,
			yppa_text,
			case when b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB
		FROM FreeTrial f WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON
			b.BBBID = f.BBBID and b.BusinessID = f.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
			('{$iTOBCode}' = '' or b.TOBID IN ('" . str_replace(",", "','", $iTOBCode) . "')) and
			b.IsReportable = '1' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			(f.Suppress = '0' or f.Suppress is null) and
			(f.NotChallengeable = '0' or f.NotChallengeable is null)
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
				array('ID', $SortFields['ID'], '', 'left'),
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('TOB', $SortFields['TOB'], '', 'left'),
				array('', ''),
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
				"onclick=\" if (confirm('This free trial business has been assigned 90050-000 and " .
				"should be removed from this list?')) { form1.iSuppress.value = '" . $fields[0] . "|" . $fields[3] . "'; " .
				"form1.submit(); } else return false; \">Assigned</a>";

			$notchallengeable_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This is not a free trial business and it " .
				"should be removed from this list?')) { form1.iNotChallengeable.value = '" . $fields[0] . "|" . $fields[3] . "'; " .
				"form1.submit(); } else return false; \">Skip</a>";

			$report->WriteReportRow(
				array (
					$fields[3],
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[3]}>" . AddApost($fields[4]) . "</a>",
					$fields[6],
					$fields[5],
					$suppress_button,
					$notchallengeable_button
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