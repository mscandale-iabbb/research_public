<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 05/04/15 MJS - rewrote to use summary table - much faster
 * 05/05/15 MJS - fixed bug with sort fields
 * 06/04/15 MJS - fixed query to use tblTiers.TierNumber instead of CountOfInquiriesByYPPA_CodeByDateOfInquiry.Tier
 * 08/27/15 MJS - removed option for "Received By BBB" and "Business State"
 * 08/25/16 MJS - align column headers
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
/*
$input_form->AddSelectField('iBBBID', 'Received by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iState', 'Business state', $iState,
	$input_form->BuildStatesArray(''), '', '', '', 'width:300px');
*/
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$SortFields = array(
	'Inquiries' => 'Inquiries',
	'TOB code' => 'i2.yppa_code',
	'TOB description' => 'y.yppa_text',
	'TOB tier' => 'i2.Tier'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	/*
	$query = "SELECT TOP " . $iMaxRecs . "
			sum(i.CountTotal) as Inquiries,
			b.TOBID,
			tblYPPA.yppa_text,
			cast(tblYPPA.Tier as varchar) + ' ' + tblTiers.TierDescription
		from BusinessInquiry i WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = i.BBBID and b.BusinessID = i.BusinessID
		inner join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		inner join tblTiers WITH (NOLOCK) on tblYPPA.Tier = tblTiers.TierNumber
		inner join BBB WITH (NOLOCK) on i.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			i.DateOfInquiry >= '" . $iDateFrom . "' and i.DateOfInquiry <= '" . $iDateTo . "' and
			('" . $iBBBID . "' = '' or i.BBBID = '" . $iBBBID . "') and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			('" . $iTier . "' = '' or tblYPPA.Tier IN
				('" . str_replace(",", "','", $iTier) . "')) and
			('" . $iCountry . "' = '' or BBB.Country = '" . $iCountry . "') and
			('" . $iState . "' = '' or b.StateProvince IN
				('" . str_replace(",", "','", $iState) . "'))
		GROUP BY b.TOBID, tblYPPA.yppa_text, tblYPPA.Tier, tblTiers.TierDescription
		";
	*/
	$query = "SELECT TOP " . $iMaxRecs . "
			sum(i2.CountOfInquiries) as Inquiries,
			i2.yppa_code,
			y.yppa_text,
			cast(tblTiers.TierNumber as varchar) + ' ' + tblTiers.TierDescription
		FROM CountOfInquiriesByYPPA_CodeByDateOfInquiry i2 WITH (NOLOCK)
		INNER JOIN tblYPPA y WITH (NOLOCK) ON i2.yppa_code = y.yppa_code
		INNER JOIN tblTiers WITH (NOLOCK) on y.Tier = tblTiers.TierNumber
		WHERE
			i2.DateOfInquiry >= '" . $iDateFrom . "' and i2.DateOfInquiry <= '" . $iDateTo . "' and
			/*('" . $iBBBID . "' = '' or i2.BBBID = '" . $iBBBID . "') and*/
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and i2.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (i2.IsBBBAccredited = 0 or i2.IsBBBAccredited is null))
			) and
			('" . $iTier . "' = '' or i2.Tier IN
				('" . str_replace(",", "','", $iTier) . "')) and
			('" . $iCountry . "' = '' or i2.Country = '" . $iCountry . "') /*and
			('" . $iState . "' = '' or i2.[State] IN
				('" . str_replace(",", "','", $iState) . "'))*/
		GROUP BY i2.yppa_code, y.yppa_text, tblTiers.TierNumber, tblTiers.TierDescription
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
				array('Inquiries', $SortFields['Inquiries'], '', 'left'),
				array('TOB Code', $SortFields['TOB code'], '', 'left'),
				array('TOB Description', $SortFields['TOB description'], '', 'left'),
				array('TOB Tier', $SortFields['TOB tier'], '', 'left')
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
					AddApost($fields[2]),
					$fields[3]
					)
				);
		}
		$report->WriteTotalsRow(
			array (
				'Total',
				array_sum( get_array_column($rs, 0) ),
				'',
				'',
				''
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