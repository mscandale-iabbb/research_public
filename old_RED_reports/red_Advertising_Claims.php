<?php

/*
 * 04/26/17 MJS - new file
 * 05/02/17 MJS - changed column label
 * 05/02/17 MJS - added input option for AB type
 * 05/02/17 MJS - added input option for pattern matched
 * 05/02/17 MJS - exclude businesses with closed ad review cases
 * 05/03/17 MJS - removed Type column
 * 05/04/17 MJS - added BID column
 * 05/04/17 MJS - added input option for whether have closed ad review cases
 * 05/04/17 MJS - added input options for TOB
 * 05/04/17 MJS - changed Processed to Challenged
 * 02/26/18 MJS - added column for AB
 * 03/22/18 MJS - excluded out of business
 * 07/24/18 MJS - added option for business ID
 * 06/27/19 MJS - added IsSearchable
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
$iTextOfClaim = NoApost($_REQUEST['iTextOfClaim']);
$iReviewed = NoApost($_REQUEST['iReviewed']);
if (! $iReviewed) $iReviewed = 'both';
$iTOB = NoApost($_REQUEST['iTOB']);
$iTOBCode = NoApost($_REQUEST['iTOBCode']);
$iDeleteURL = NoApost($_POST['iDeleteURL']);
$iSuppress = NoApost($_POST['iSuppress']);
$iNotChallengeable = NoApost($_POST['iNotChallengeable']);
$iBusinessID = Numeric2($_POST['iBusinessID']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddTextField('iTextOfClaim','Passages containing words', $iTextOfClaim);
$input_form->AddSelectField('iAB','Businesses of AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddRadio('iReviewed', 'Businesses with past ad review cases', $iReviewed, array(
		'Yes' => 'yes',
		'No' => 'no',
		'Both' => 'both',
	)
);
$input_form->AddTextField('iTOB','Business TOB description contains',$iTOB);
$input_form->AddMultipleSelectField('iTOBCode', 'Business TOB code', $iTOBCode, $input_form->BuildTOBsArray(),
	'width:300px;', '', '', 'width:300px;');
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;" );
$input_form->AddTextField('iSuppress', '', $iSuppress, "visibility:hidden;" );
$input_form->AddTextField('iNotChallengeable', '', $iNotChallengeable, "visibility:hidden;" );
$SortFields = array(
	'ID' => 'b.BusinessID',
	'BBB city' => "BBB.NicknameCity,b.BusinessName",
	'Business name' => 'b.BusinessName',
	'AB' => 'AB',
	'Website' => 'c.Website'
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
		$update = "UPDATE BusinessAdClaim SET Suppress = '1' WHERE Website = '{$iSuppress}'";
		$r = $conn->execute($update);
	}

	/* special option to mark as  */
	if ($iNotChallengeable) {
		$update = "UPDATE BusinessAdClaim SET NotChallengeable = '1' WHERE Website = '{$iNotChallengeable}'";
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
			c.Website,
			b.ReportURL,
			c.TextOfClaim,
			case when b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB
		FROM BusinessAdClaim c WITH (NOLOCK)
		inner join CORE.dbo.atrURL u WITH (NOLOCK) on u.URL = c.Website
		inner join CORE.dbo.lnkOrgURL lu on lu.URLID = u.URLID and lu.URLTypeID not in ('717','718','719','721','738','739')
		inner join {$table_Org} o on o.OrgID = lu.OrgID
		inner join Business b WITH (NOLOCK) ON b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
			and b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			('{$iBusinessID}' = '' or b.BusinessID = '{$iBusinessID}' ) and
			('{$iTextOfClaim}' = '' or replace(replace(c.TextOfClaim,'<b>',''),'</b>','') like '%{$iTextOfClaim}%') and
			(
				'{$iReviewed}' = 'both' or
				('{$iReviewed}' = 'yes' and
					(select count(*) from BusinessAdReview a2 WITH (NOLOCK) where a2.BBBID = c.BBBID and a2.BusinessID = c.BusinessID) >= 1
				) or
				('{$iReviewed}' = 'no' and
					(select count(*) from BusinessAdReview a2 WITH (NOLOCK) where a2.BBBID = c.BBBID and a2.BusinessID = c.BusinessID) = 0
				)
			) and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
			('{$iTOBCode}' = '' or b.TOBID IN ('" . str_replace(",", "','", $iTOBCode) . "')) and
			b.IsReportable = '1' and
			u.IsSearchable = '1' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			(c.Suppress = '0' or c.Suppress is null) and
			(c.NotChallengeable = '0' or c.NotChallengeable is null)
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
				//array('Business Profile', $SortFields['Business Profile'], '', 'left'),
				array('Website', $SortFields['Website'], '', 'left'),
				array('Text of Advertising Claims', '', '', 'left'),
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
				"onclick=\" if (confirm('This claim has been challenged and " .
				"should be removed from this list?')) { form1.iSuppress.value = '" . $fields[5] . "'; " .
				"form1.submit(); } else return false; \">Challenged</a>";

			$notchallengeable_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This is not an advertising claim that can be challenged and it " .
				"should be removed from this list?')) { form1.iNotChallengeable.value = '" . $fields[5] . "'; " .
				"form1.submit(); } else return false; \">Skip</a>";

			//$oReportURL = $fields[6];
			//if (substr($oReportURL, 0, 4) != "http") $oReportURL = "http://" . $oReportURL;

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[3]}>" . AddApost($fields[3]) . "</a>",
					AddApost($fields[4]),
					$fields[8],
					"<a target=detail href={$fields[5]} >Click Here</a>",
					AddApost($fields[7]),
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