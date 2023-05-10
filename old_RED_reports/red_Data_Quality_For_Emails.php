<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 01/26/16 MJS - added option for ALL for CBBB users
 * 08/25/16 MJS - aligned column headers
 * 11/10/16 MJS - changed REQUEST to POST
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


$userBBBID = $BBBID;

$iBBBID = Numeric2($_POST['iBBBID']);
if (! $_POST && $userBBBID != '2000') $iBBBID = $userBBBID;
else if (! $_POST && $userBBBID == '2000') $iBBBID = '1066';
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$SortFields = array(
	'ID' => 'BusinessID',
	'BBB city' => 'BBBCity,BusinessName',
	'Business name' => 'BusinessName',
	'Email' => 'Email',
	'Person' => 'Person',
	'Rating' => 'BBBRatingSortOrder,BusinessName',
	'AB' => 'AB,BusinessName',
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
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State as BBBCity,
			b.BusinessID as BusinessID,
			REPLACE(b.BusinessName,'&#39;','''') as BusinessName,
			'General' as Person,
			b.Email as Email,
			b.BBBRatingGrade,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			r.BBBRatingSortOrder as BBBRatingSortOrder
		from Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		where
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.IsReportable = 1 and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			LEN(RTRIM(LTRIM(b.Email))) > 1 and
			b.Email != 'no' and
			b.Email != 'na' and
			b.Email != 'none' and
			b.Email != 'no email' and
			(
				b.Email not like '%@%' or
				(b.Email like '%/%' and b.Email not like '%[%' and b.Email not like '%<%' and b.Email not like '%;%') or
				(b.Email like '%\%' and b.Email not like '%[%' and b.Email not like '%<%' and b.Email not like '%;%') or
				(b.Email like '%(%' and b.Email not like '%[%' and b.Email not like '%<%' and b.Email not like '%;%') or
				(b.Email like '%,%' and b.Email not like '%[%' and b.Email not like '%<%' and b.Email not like '%;%' and
					b.Email not like '%@%.%@%.%') or
				b.Email like '%..%' or
				b.Email like '%\"%' or
				b.Email not like '%.%' or
				(RTRIM(LTRIM(b.Email)) like '% %' and b.Email not like '%[%' and b.Email not like '%<%' and b.Email not like '%;%' and
					b.Email not like '%@%.% %@%.%')
			)
		UNION
		select
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State as BBBCity,
			b2.BusinessID as BusinessID,
			REPLACE(b2.BusinessName,'&#39;','''') as BusinessName,
			p.FirstName + ' ' + p.LastName as Person,
			p.Email as Email,
			b2.BBBRatingGrade,
			Case When b2.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			r.BBBRatingSortOrder as BBBRatingSortOrder
		from BusinessContact p WITH (NOLOCK)
		inner join Business b2 WITH (NOLOCK) on
			b2.BBBID = p.BBBID and b2.BusinessID = p.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b2.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b2.BBBRatingGrade
		where
			('{$iBBBID}' = '' or b2.BBBID = '{$iBBBID}') and
			b2.IsReportable = 1 and b2.PublishToCIBR = 1 and
			LEN(RTRIM(LTRIM(p.Email))) > 1 and
			p.Email != 'no' and
			p.Email != 'na' and
			p.Email != 'none' and
			p.Email != 'no email' and
			(
				p.Email not like '%@%' or
				(p.Email like '%/%' and p.Email not like '%[%' and p.Email not like '%<%' and p.Email not like '%;%') or
				(p.Email like '%\%' and p.Email not like '%[%' and p.Email not like '%<%' and p.Email not like '%;%') or
				(p.Email like '%(%' and p.Email not like '%[%' and p.Email not like '%<%' and p.Email not like '%;%') or
				(p.Email like '%,%' and p.Email not like '%[%' and p.Email not like '%<%' and p.Email not like '%;%' and
					p.Email not like '%@%.%@%.%') or
				p.Email like '%..%' or
				p.Email like '%\"%' or
				p.Email not like '%.%' or
				(RTRIM(LTRIM(p.Email)) like '% %' and p.Email not like '%[%' and p.Email not like '%<%' and p.Email not like '%;%' and
					p.Email not like '%@%.% %@%.%')
			)		
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
				array('ID', $SortFields['ID'], '', 'left'),
				array('Name', $SortFields['Business name'], '', 'left'),
				array('Person', $SortFields['Person'], '', 'left'),
				array('Email', $SortFields['Email'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
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

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . NoApost($fields[1]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[2] .  ">" . NoApost($fields[2]) . "</a>",
					NoApost($fields[3]),
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
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