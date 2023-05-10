<?php

/*
 * 11/15/17 MJS - new file
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
$iState = NoApost($_POST['iState']);
$iAB = NoApost($_REQUEST['iAB']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Inquiries DESC';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddMultipleSelectField('iTier', 'TOB tier', $iTier,
	$input_form->BuildTOBTiersArray(''), '', '', '', 'width:100px');
$input_form->AddSelectField('iBBBID', 'Received by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iState', 'Business state', $iState,
	$input_form->BuildStatesArray(''), '', '', '', 'width:300px');
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$SortFields = array(
	'Inquiries' => 'Inquiries',
	'NAICS code' => 'substring(cast(y.naics_code as varchar(6)),1,@levels)',
	'NAICS description' => 'n.naics_description',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @levels int;
		set @levels = 6;
		SELECT TOP {$iMaxRecs}
			sum(i.CountTotal) as Inquiries,
			substring(cast(y.naics_code as varchar(6)),1,@levels),
			n.naics_description
		from BusinessInquiry i WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = i.BBBID and b.BusinessID = i.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on i.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			i.DateOfInquiry >= '{$iDateFrom}' and i.DateOfInquiry <= '{$iDateTo}' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iBBBID}' = '' or i.BBBID = '{$iBBBID}') and
			b.TOBID != '99999-000' and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = '1') or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}') and
			('{$iState}' = '' or b.StateProvince IN
				('" . str_replace(",", "','", $iState) . "'))
		GROUP BY substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
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
				array('Inquiries', $SortFields['Inquiries'], '', 'left'),
				array('NAICS Code', $SortFields['NAICS code'], '', 'left'),
				array('NAICS Description', $SortFields['NAICS description'], '', 'left'),
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
					$fields[1],
					AddApost($fields[2])
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Total',
				array_sum( get_array_column($rs, 0) ),
				'',
				'',
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