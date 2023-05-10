<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 06/02/15 MJS - added country when looking up by PostalCodesBig to prevent American-Mexican code conflicts
 * 08/25/16 MJS - aligned column headers
 * 11/10/16 MJS - changed REQUEST to POST
 * 06/05/17 MJS - cleaned up code
 * 07/26/17 MJS - changed sql for new schema
 * 01/30/18 MJS - refactored for APICore
 * 02/08/18 MJS - modified query to catch null values for Business.Country
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
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('') );
$SortFields = array(
	'Business name' => 'b.BusinessName',
	'BBB city' => 'BBB.NicknameCity,b.BusinessName',
	'Type' => 'b.ReportType,b.BusinessName',
	'Joined' => 'joined',
	'Business city' => 'b.City,b.BusinessName',
	'Business postal code' => 'b.PostalCode,b.BusinessName'
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
		$table_Bureau = "CORE.dbo.datBureau";
		$table_Org = "CORE.dbo.datOrg";
		$column_OOB = "OutOfBusinessTypeId";
	}
	else {
		$table_BureauZip = "APICore.dbo.BureauZip";
		$table_Bureau = "APICore.dbo.Bureau";
		$table_Org = "APICore.dbo.Organization";
		$column_OOB = "OutOfBusinessStatusTypeId";
	}
	$query = "
		SELECT TOP {$iMaxRecs}
			b.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			b.BusinessID,
			b.BusinessName,
			b.ReportType,
			(select top 1 DateFrom from
				BusinessProgramParticipation bp WITH (NOLOCK) where
				bp.BBBID = b.BBBID and
				bp.BusinessID = b.BusinessID and
				(bp.BBBProgram = 'Membership' or bp.BBBProgram = 'BBB Accredited Business') and
				DateFrom is not null order by DateFrom
			) as joined,
			b.City + ', ' + b.StateProvince as BusinessCity,
			b.PostalCode as Zip
		FROM Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		WHERE
			(select COUNT(*) from {$table_BureauZip} bz WITH (NOLOCK)
				inner join {$table_Bureau} bb WITH (NOLOCK) on bb.bureauid = bz.bureauid
				where
					bz.ZipCode = LEFT(b.PostalCode,5) and
					(b.Country = '' or b.Country is null or bz.CountryCode = b.Country) and
					bb.BureauCode = '{$iBBBID}' and bb.BureauBranchID = '0'
			) > 0 and
			b.BBBID != '{$iBBBID}' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			b.IsReportable = '1' and
			b.IsBBBAccredited = '1'
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
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('Type', $SortFields['Type'], '', 'left'),
				array('Joined', $SortFields['Joined'], '', 'left'),
				array('Business City', $SortFields['Business city'], '', 'left'),
				array('Postal Code', $SortFields['Business postal code'], '', 'left'),
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
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>" .
						NoApost($fields[1]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[2]}>" . NoApost($fields[3]) . "</a>",
					$fields[4],
					FormatDate($fields[5]),
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