<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 07/26/15 MJS - excluded blank close codes
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


$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iCountry = $_POST['iCountry'];
$iTier = $_POST['iTier'];
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iConsumerBBBID = Numeric2($_REQUEST['iConsumerBBBID']);
$iState = NoApost($_POST['iState']);
$iAB = NoApost($_REQUEST['iAB']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Complaints DESC';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddMultipleSelectField('iTier', 'TOB tier', $iTier,
	$input_form->BuildTOBTiersArray(''), '', '', '', 'width:100px');
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddSelectField('iConsumerBBBID', 'Consumer in BBB area', $iConsumerBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iState', 'Business state', $iState,
	$input_form->BuildStatesArray(''), '', '', '', 'width:300px');
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$SortFields = array(
	'Complaints' => 'Complaints',
	'TOB code' => 'TOBID',
	'TOB description' => 'tblYPPA.yppa_text',
	'TOB tier' => 'tblYPPA.Tier'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT TOP " . $iMaxRecs . "
			count(*) as Complaints,
			b.TOBID,
			tblYPPA.yppa_text,
			cast(tblYPPA.Tier as varchar) + ' ' + tblTiers.TierDescription
		from BusinessComplaint c WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		inner join tblTiers WITH (NOLOCK) on tblYPPA.Tier = tblTiers.TierNumber
		inner join BBB WITH (NOLOCK) on c.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			c.DateClosed >= '" . $iDateFrom . "' and c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode <= 300 and c.CloseCode is not null and c.CloseCode > 0 and
			len(b.TOBID) = 9 and
			('" . $iBBBID . "' = '' or c.BBBID = '" . $iBBBID . "') and
			('" . $iConsumerBBBID . "' = '' or c.ConsumerBBBID = '" . $iConsumerBBBID . "') and
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
	if ($iSortBy > '') {
		$query .= " ORDER BY " . $iSortBy;
	}

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute("$query");
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