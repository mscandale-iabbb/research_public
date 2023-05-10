<?php

/*
 * 04/14/17 MJS - new file
 * 04/15/17 MJS - fixed ReportURL values without http
 * 04/18/17 MJS - modified layout
 * 05/04/17 MJS - update fields DateClicked and WhoClicked
 * 05/25/17 MJS - added option to filter by ones that are compliant
 * 03/22/18 MJS - excluded out of business
 * 06/27/19 MJS - added IsSearchable
 * 07/09/19 MJS - excluded local reports
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
$iCompliant = NoApost($_POST['iCompliant']);
$iDeleteURL = NoApost($_POST['iDeleteURL']);
$iSuppress = NoApost($_POST['iSuppress']);
$iSiteIsCompliant = NoApost($_POST['iSiteIsCompliant']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddSelectField('iCompliant','Reviewed',$iCompliant, array('Unreviewed' => '', 'Compliant' => '1') );
$input_form->AddTextField('iSuppress', '', $iSuppress, "visibility:hidden;" );
$input_form->AddTextField('iSiteIsCompliant', '', $iSiteIsCompliant, "visibility:hidden;" );
$SortFields = array(
	'ID' => 'b.BusinessID',
	'BBB city' => "BBB.NicknameCity,b.BusinessName",
	'Business name' => 'b.BusinessName',
	'Website' => 'v.Website',
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
		$update = "
			UPDATE BusinessLogoViolation SET
				Suppress = '1',
				DateClicked = GETDATE(),
				WhoClicked = '{$_SESSION['LoweredEmail']}'
			WHERE Website = '{$iSuppress}'";
		$r = $conn->execute($update);
	}

	/* special option to mark as  */
	if ($iSiteIsCompliant) {
		$update = "
			UPDATE BusinessLogoViolation SET
				SiteIsCompliant = '1',
				DateClicked = GETDATE(),
				WhoClicked = '{$_SESSION['LoweredEmail']}'
			WHERE Website = '{$iSiteIsCompliant}'";
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
			v.Website,
			b.ReportURL,
			v.SiteIsCompliant
		FROM BusinessLogoViolation v WITH (NOLOCK)
		inner join CORE.dbo.atrURL u WITH (NOLOCK) on u.URL = v.Website
		inner join CORE.dbo.lnkOrgURL lu on lu.URLID = u.URLID and lu.URLTypeID not in ('717','718','719','721','738','739')
		inner join {$table_Org} o on o.OrgID = lu.OrgID
		inner join Business b WITH (NOLOCK) ON b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId and
			b.BBBID = v.BBBID and b.BusinessID = v.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			(b.IsBBBAccredited = '0' or b.IsBBBAccredited is null) and
			b.IsReportable = '1' and
			b.ReportType != 'Local' and
			u.IsSearchable = '1' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			(v.Suppress = '0' or v.Suppress is null) and
			(
				(
					'{$iCompliant}' = '' and
					(v.SiteIsCompliant = '0' or v.SiteIsCompliant is null)
				) or
				(
					'{$iCompliant}' = '1' and
					(v.SiteIsCompliant = '1')
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
				array('Name', $SortFields['Business name'], '', 'left'),
				array('Business Profile', $SortFields['Business Profile'], '', 'left'),
				array('Website With Possible Logo Violation', $SortFields['Website'], '', 'left'),
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

			$oSiteIsCompliant = $fields[7];

			$suppress_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This website logo violation has been processed and " .
				"should be removed from this list?')) { form1.iSuppress.value = '" . $fields[5] . "'; " .
				"form1.submit(); } else return false; \">Processed</a>";
			if ($oSiteIsCompliant == '1') $suppress_button = "";

			$compliant_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This website has been found to be compliant and " .
				"should be removed from this list?')) { form1.iSiteIsCompliant.value = '" . $fields[5] . "'; " .
				"form1.submit(); } else return false; \">Compliant</a>";
			if ($oSiteIsCompliant == '1') $compliant_button = "Compliant";

			$oReportURL = $fields[6];
			if (substr($oReportURL, 0, 4) != "http") $oReportURL = "http://" . $oReportURL;

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . NoApost($fields[1] . ',' . $fields[2]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[3] .  ">" . NoApost($fields[3]) . "</a>",
					NoApost($fields[4]),
					"<a target=detail href={$oReportURL} >Click Here</a>",
					"<a target=detail href={$fields[5]} >Click Here</a>",
					$suppress_button,
					$compliant_button
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