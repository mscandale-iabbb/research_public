<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 07/26/16 MJS - excluded blank close codes
 * 08/24/16 MJS - aligned column headers
 * 10/16/17 MJS - added option for close code
 * 11/15/17 MJS - added option for NAICS
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iCountry = $_POST['iCountry'];
$iTier = $_POST['iTier'];
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iNAICS = NoApost($_POST['iNAICS']);
$iTOB = NoApost($_POST['iTOB']);
$iCloseCode = NoApost($_POST['iCloseCode']);
$iConsumerBBBID = Numeric2($_REQUEST['iConsumerBBBID']);
$iState = NoApost($_POST['iState']);
$iAB = NoApost($_REQUEST['iAB']);
$iSize = NoApost($_REQUEST['iSize']);
$iRating = NoApost($_REQUEST['iRating']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Complaints DESC';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddSelectField('iNAICS', 'Industry', $iNAICS, $input_form->BuildNAICSGroupArray() );
$input_form->AddTextField('iTOB','TOB contains word/phrase',$iTOB);
$input_form->AddMultipleSelectField('iCloseCode', 'Close code', $iCloseCode,
	$input_form->BuildCloseCodesArray(''), '', '', '', 'width:300px');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddMultipleSelectField('iSize', 'Business size', $iSize,
	$input_form->BuildSizesArray('all'), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iRating', 'Business rating', $iRating,
	$input_form->BuildRatingsArray('all'), '', '', '', 'width:300px');
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$SortFields = array(
	'Complaints' => 'Complaints',
	'Business name' => 'BusinessName',
	'BBB city' => 'BBB.NicknameCity,b.BusinessName',
	'TOB code' => 'b.TOBID,b.BusinessName',
	'TOB description' => 'tblYPPA.yppa_text,b.BusinessName',
	'AB' => 'AB,b.BusinessName',
	'Rating' => 'r.BBBRatingSortOrder,b.BusinessName',
	'Size' => 's.SizeOfBusinessSortOrder,b.BusinessName'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT TOP {$iMaxRecs}
			count(*) as Complaints,
			b.BBBID,
			b.BusinessID,
			REPLACE(b.BusinessName,'&#39;','''') as BusinessName,
			BBB.NickNameCity + ', ' + BBB.State,
			b.TOBID + ' ' + tblYPPA.yppa_text,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.BBBRatingGrade,
			b.SizeOfBusiness
		from BusinessComplaint c WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join BBB WITH (NOLOCK) on c.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		inner join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		--left outer join tblNAICS n WITH (NOLOCK) on n.naics_code = tblYPPA.naics_code
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join tblSizesOfBusiness s WITH (NOLOCK) ON s.SizeOfBusiness = b.SizeOfBusiness
		WHERE
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			('{$iBBBID}' = '' or c.BBBID = '{$iBBBID}') and
			c.CloseCode <= '300' and c.CloseCode is not null and c.CloseCode > '0' and
			('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
			('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
			('{$iCloseCode}' = '' or c.CloseCode IN
				('" . str_replace(",", "','", $iCloseCode) . "')) and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			('{$iRating}' = '' or b.BBBRatingGrade IN ('" . str_replace(",", "','", $iRating) . "')) and
			('{$iSize}' = '' or b.SizeOfBusiness IN ('" . str_replace(",", "','", $iSize) . "')) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		GROUP BY b.BBBID, b.BusinessID, BBB.NickNameCity, BBB.State, b.BusinessName,
			b.TOBID, tblYPPA.yppa_text, b.IsBBBAccredited, b.BBBRatingGrade, r.BBBRatingSortOrder,
			b.SizeOfBusiness, s.SizeOfBusinessSortOrder
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
				array('Reportable Complaints', $SortFields['Complaints'], '', 'right'),
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('TOB', $SortFields['TOB code'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
				array('Size', $SortFields['Size'], '', 'left')
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
					$fields[0],
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[1] .
						"&iBusinessID=" . $fields[2] .  ">" . AddApost($fields[3]) . "</a>",
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[1] .
						">" . AddApost($fields[4]) . "</a>",
					AddApost($fields[5]),
					$fields[6],
					$fields[7],
					$fields[8]
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Total',
				array_sum( get_array_column($rs, 0) ),
				'',
				''
				)
			);
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>