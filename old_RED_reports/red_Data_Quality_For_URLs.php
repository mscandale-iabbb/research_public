<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 11/26/14 MJS - added ones from BadBusinessURL table which are based on cURL script HTTP response code
 * 09/01/15 MJS - exclude ones where the URL record (not necessarily the business record) is marked as unreportable
 * 09/14/15 MJS - added field for reason
 * 09/14/15 MJS - added option to delete
 * 09/16/15 MJS - changed wording of reasons
 * 01/25/16 MJS - added column to Select to fix sort bug
 * 02/26/16 MJS - fixed bug with sort order
 * 03/03/16 MJS - fixed bug with orange X button
 * 03/03/16 MJS - fixed so that orange X buttons won't show will not relevant
 * 03/07/16 MJS - removed debug message
 * 08/25/16 MJS - aligned column headers
 * 11/10/16 MJS - changed REQUEST to POST
 * 02/26/18 MJS - cleaned up code
 * 03/22/18 MJS - excluded out of business
 * 11/27/18 MJS - allow commas
 * 04/11/19 MJS - changed to use CORE for urls
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
$iDeleteURL = NoApost($_POST['iDeleteURL']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddTextField('iDeleteURL', '', $iDeleteURL, "visibility:hidden;" );
$SortFields = array(
	'ID' => 'b.BusinessID',
	'BBB city' => "BBB.NicknameCity,b.BusinessName",
	'Business name' => 'b.BusinessName',
	'URL' => 'u.URL',
	'Rating' => 'r.BBBRatingSortOrder,b.BusinessName',
	'AB' => 'AB,b.BusinessName',
	'Reason' => 'Reason,b.BusinessName',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	/* special option to delete URL */
	if ($iDeleteURL) {
		$delete = "DELETE FROM BadBusinessURL WHERE URL = '{$iDeleteURL}'";
		$r = $conn->execute($delete);
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
			u.URL,
			case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.BBBRatingGrade,
			bad.URL,
			case when bad.URL is not null then 'Unable to open' else 'Invalid format' end as Reason,
			r.BBBRatingSortOrder /* only needed if Sort is by this column */
		from CORE.dbo.atrURL u
		inner join CORE.dbo.lnkOrgURL lu on lu.URLID = u.URLID and lu.URLTypeID not in ('717','718','719','721','738','739')
		inner join {$table_Org} o on o.OrgID = lu.OrgID
		inner join Business b WITH (NOLOCK) ON b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join BadBusinessURL bad WITH (NOLOCK) on bad.URL = u.URL
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.IsReportable = '1' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			u.IsSearchable = '1' and
			(
				u.URL not like '%.%' or
				/*LTRIM(RTRIM(u.URL)) like '% %' or*/
				bad.URL is not null
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
				array('URL', $SortFields['URL'], '', 'left'),
				array('Reason', $SortFields['Reason'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
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

			/* build test button if bad URL record exists */
			/*
			$test_button = '';
			if ($fields[7]) $test_button = "<a target=_new href=\"{$fields[9]}\">Test</a>";
			*/

			/* build Delete button if bad URL record exists */
			$delete_button = '';
			if ($fields[8]) $delete_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This URL has been checked and confirmed as valid and " .
				"should be removed from this list?')) { form1.iDeleteURL.value = '" . $fields[5] . "'; " .
				"form1.submit(); } else return false; \">x</a>";

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . NoApost($fields[1] . ',' . $fields[2]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[3]}>" . NoApost($fields[3]) . "</a>",
					NoApost($fields[4]),
					$fields[5],
					$fields[9],
					$fields[6],
					$fields[7],
					$delete_button,
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