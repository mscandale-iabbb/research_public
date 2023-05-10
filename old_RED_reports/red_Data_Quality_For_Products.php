<?php

/*
 * 08/29/19 MJS - new file
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
$iInvalid = NoApost($_POST['iInvalid']);
$iProcessed = NoApost($_POST['iProcessed']);
$iProcessedBBBID = NoApost($_POST['iProcessedBBBID']);
$iProcessedBusinessID = NoApost($_POST['iProcessedBusinessID']);
$iInvalidBBBID = NoApost($_POST['iInvalidBBBID']);
$iInvalidBusinessID = NoApost($_POST['iInvalidBusinessID']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddSelectField('iProcessed','Processed',$iProcessed, array('Unprocessed' => '', 'Processed' => '1') );
$input_form->AddTextField('iInvalidBBBID', '', $iInvalid, "visibility:hidden;", 'sameline');
$input_form->AddTextField('iInvalidBusinessID', '', $iInvalid, "visibility:hidden;", 'sameline');
$input_form->AddTextField('iProcessedBBBID', '', $iProcessedBBBID, "visibility:hidden;", 'sameline');
$input_form->AddTextField('iProcessedBusinessID', '', $iProcessedBusinessID, "visibility:hidden;", 'sameline');
$SortFields = array(
	'ID' => 'b.BusinessID',
	'AB' => 'AB',
	'BBB city' => "BBB.NicknameCity,b.BusinessName",
	'Business name' => 'b.BusinessName',
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
	if ($iInvalidBBBID) {
		$update = "
			UPDATE BadProduct SET
				Invalid = '1',
				DateClicked = GETDATE(),
				WhoClicked = '{$_SESSION['LoweredEmail']}'
			WHERE BBBID = '{$iInvalidBBBID}' and BusinessID = '{$iInvalidBusinessID}' ";
		$r = $conn->execute($update);
	}

	/* special option to mark as processed */
	if ($iProcessedBBBID) {
		$update = "
			UPDATE BadProduct SET
				Processed = '1',
				DateClicked = GETDATE(),
				WhoClicked = '{$_SESSION['LoweredEmail']}'
			WHERE BBBID = '{$iProcessedBBBID}' and BusinessID = '{$iProcessedBusinessID}' ";
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
			LEFT(t.CustomText,150),
			b.BusinessID,
			b.BusinessName,
			b.ReportURL,
			p.Processed,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB
		FROM BadProduct p WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = p.BBBID and b.BusinessID = p.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		inner join {$table_Org} o on o.BureauCode = b.BBBID and o.SourceBusinessId = b.BusinessID
		inner join Core.dbo.datOrgCustomText t WITH (NOLOCK) ON o.OrgId = t.OrgId and t.CustomText > ''
		inner join Core.dbo.refType r WITH (NOLOCK) on r.TypeId = t.SectionTypeId and r.NameFull = 'Products and Services'
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.IsReportable = '1' and
			b.ReportType != 'Local' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			(p.Invalid = '0' or p.Invalid is null) and
			(
				(
					'{$iProcessed}' = '' and
					(p.Processed = '0' or p.Processed is null)
				) or
				(
					'{$iProcessed}' = '1' and
					(p.Processed = '1')
				)
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
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('ID', $SortFields['ID'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Name', $SortFields['Business name'], '', 'left'),
				array('Product Section Text', ''),
				array('Business Profile', $SortFields['Business Profile'], '', 'left'),
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

			$oProcessed = $fields[6];

			$processed_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('The product text for this business has been corrected and " .
				"should be removed from this list?')) { form1.iProcessedBBBID.value = '" . $fields[0] . "'; " .
				"form1.iProcessedBusinessID.value = '" . $fields[3] . "'; " .
				"form1.submit(); } else return false; \">Processed</a>";
			if ($oProcessed == '1') $processed_button = "";

			$invalid_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('The product text for this business has been found to be NOT a problem and " .
				"should be removed from this list?')) { form1.iInvalidBBBID.value = '" . $fields[0] . "'; " .
				"form1.iInvalidBusinessID.value = '" . $fields[3] . "'; " .
				"form1.submit(); } else return false; \">Not A Problem</a>";
			if ($oInvalid == '1') $invalid_button = "Not A Problem";

			$oReportURL = $fields[5];
			if (substr($oReportURL, 0, 4) != "http") $oReportURL = "http://" . $oReportURL;

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . NoApost($fields[1]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[3] .  ">" . NoApost($fields[3]) . "</a>",
					$fields[7],
					NoApost($fields[4]),
					$fields[2] . "...",
					"<a target=detail href={$oReportURL} >Click Here</a>",
					$processed_button,
					$invalid_button
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