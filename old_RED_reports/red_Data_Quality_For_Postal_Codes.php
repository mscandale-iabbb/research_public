<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 06/02/15 MJS - added country when looking up by PostalCodesBig to prevent American-Mexican code conflicts
 * 09/01/15 MJS - fixed order of fields
 * 09/01/15 MJS - fixed error with sorting by rating
 * 09/01/15 MJS - added filter for searchable field in address record
 * 11/19/15 MJS - fixed bug with sorting by postal code column
 * 08/25/16 MJS - aligned column headers
 * 11/10/16 MJS - changed REQUEST to POST
 * 07/26/17 MJS - changed sql for new schema
 * 11/08/17 MJS - modified query to catch null address countries
 * 01/30/18 MJS - refactored for APICore
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
	'ID' => 'a.BusinessID',
	'BBB city' => 'BBB.NicknameCity,b.BusinessName',
	'Business name' => 'b.BusinessName',
	'Business postal code' => 'a.PostalCode,b.BusinessName',
	'Rating' => 'r.BBBRatingSortOrder,b.BusinessName',
	'AB' => 'AB,b.BusinessName',
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
		$table_BureauZip = "CORE.dbo.lnkBureauZip";
		$table_Org = "CORE.dbo.datOrg";
		$column_OOB = "OutOfBusinessTypeId";
	}
	else {
		$table_BureauZip = "APICore.dbo.BureauZip";
		$table_Org = "APICore.dbo.Organization";
		$column_OOB = "OutOfBusinessStatusTypeId";
	}
	$query = "
		SELECT DISTINCT TOP {$iMaxRecs}
			a.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			a.BusinessID,
			b.BusinessName,
			a.PostalCode,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.BBBRatingGrade,
			r.BBBRatingSortOrder /* this is included only to allow sorting by it */
		FROM BusinessAddress a WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = a.BBBID AND b.BusinessID = a.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		WHERE
			('{$iBBBID}' = '' or a.BBBID = '{$iBBBID}') and
			b.IsReportable = 1 and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			a.PublishToCIBR = 1 and
			(
				/* no matching entry in validation table - US */
				(
					(	LEN(a.PostalCode) = 5 OR
						LEN(a.PostalCode) = 9 OR
						LEN(a.PostalCode) = 10
					) and
					(select COUNT(*) from {$table_BureauZip} bz WITH (NOLOCK) where
							bz.ZipCode = LEFT(a.PostalCode,5) and
							(a.Country = '' or a.country is null or bz.CountryCode = a.Country)
					) = 0
				) OR
				/* no matching entry in validation table - Canada */
				(
					(	LEN(a.PostalCode) = 6 OR
						LEN(a.PostalCode) = 7
					) and
					SUBSTRING(a.PostalCode,6,1) != '-' and
					(select COUNT(*) from {$table_BureauZip} bz WITH (NOLOCK) where
						bz.ZipCode = LEFT(a.PostalCode,3) + ' ' + RIGHT(a.PostalCode,3)
					) = 0
				) OR
				/* invalid characters/formats */
				(
					a.PostalCode IS NULL or
					LEN(LTRIM(RTRIM(a.PostalCode))) < 5 or
					LEN(LTRIM(RTRIM(a.PostalCode))) = 8 or
					LEN(LTRIM(RTRIM(a.PostalCode))) > 10 or
					(
						LEN(LTRIM(RTRIM(a.PostalCode))) = 5 and
                    				NOT SUBSTRING(a.PostalCode,1,1)
						IN (NULL,'',' ','1','2','3','4','5','6','7','8','9','0')
            				) or
            				(
						LEN(LTRIM(RTRIM(a.PostalCode))) = 7 AND
                    				NOT SUBSTRING(a.PostalCode,4,1) < '0'
            				) or
            				(
						LEN(LTRIM(RTRIM(a.PostalCode))) = 9 and
                    				NOT SUBSTRING(a.PostalCode,1,1)
						IN (NULL,'',' ','1','2','3','4','5','6','7','8','9','0')
            				) or
            				(
						LEN(LTRIM(RTRIM(a.PostalCode))) = 10 and
                    				NOT SUBSTRING(a.PostalCode,1,1)
						IN (NULL,'',' ','1','2','3','4','5','6','7','8','9','0')
            				)
				)
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
				array('Postal Code', $SortFields['Business postal code'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
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
						">" . $fields[1] . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[2] .  ">" . $fields[2] . "</a>",
					NoApost($fields[3]),
					$fields[4],
					$fields[5],
					$fields[6],
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