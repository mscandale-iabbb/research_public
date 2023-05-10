<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 08/24/16 MJS - aligned column headers
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iCountry = $_POST['iCountry'];
$iAB = NoApost($_REQUEST['iAB']);
if (! $_POST) $iAB = 1;
$iSize = NoApost($_REQUEST['iSize']);
$iMaxRecs = Numeric2($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'avgyears DESC';
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddSelectField('iSize', 'Business size', $iSize, $input_form->BuildSizesArray('all') );
$SortFields = array(
	'Industry code' => 'industrycode',
	'Industry description' => 'industrydescription',
	'ABs' => 'ABs',
	'Average years accredited' => 'avgyears',
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
		declare @threshold int;
		set @levels = 6;
		set @threshold = 20;
		if '{$iBBBID}' = '' AND '{$iSize}' = '' set @threshold = 385;
		SELECT TOP {$iMaxRecs}
			substring(cast(n.naics_code as varchar(6)),1,@levels) as industrycode,
			REPLACE(n.naics_description,'`','''') as industrydescription,
			AVG( ABS( cast (DATEDIFF( year, GETDATE(), p.DateFrom ) as decimal) ) ) as avgyears,
			COUNT(*) as ABs,
			SUM( ABS( cast (DATEDIFF( year, GETDATE(), p.DateFrom ) as decimal) ) ) as sumyears
		from Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on b.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		inner join tblYPPA y WITH (NOLOCK) ON y.yppa_code = b.TOBID
		inner join tblNAICS n WITH (NOLOCK) ON
			substring(cast(y.naics_code as varchar(6)),1,@levels) = n.naics_code
		inner join BusinessProgramParticipation p WITH (NOLOCK) on p.BBBID = b.BBBID AND p.BusinessID = b.BusinessID and
			(p.BBBProgram = 'Membership' or p.BBBProgram = 'BBB Accredited Business') and
			NOT p.DateFrom IS NULL
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			(p.DateTo > GETDATE() OR p.DateTo IS NULL) AND
			LEN(rtrim(cast(n.naics_code as varchar(6)))) >= @levels AND
			y.yppa_code is not null AND y.yppa_text is not null AND
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "')) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}') and
			('{$iSize}' = '' or b.SizeOfBusiness = '{$iSize}')
		GROUP BY n.naics_code, n.naics_description
		HAVING COUNT(*) >= @threshold
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
				array('Industry Code', $SortFields['Industry code'], '', 'left'),
				array('Industry Description', $SortFields['Industry description'], '', 'left'),
				array('Average Years Accredited', $SortFields['Average years accredited'], '', 'right'),
				array('ABs', $SortFields['ABs'], '', 'right'),
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
					AddApost($fields[1]),
					round($fields[2], 1),
					$fields[3],
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				'',
				'',
				round(
					array_sum( get_array_column($rs, 4) ) /
					array_sum( get_array_column($rs, 3) ),
				1),
				'' /*array_sum( get_array_column($rs, 3) )*/
			)
		);
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>