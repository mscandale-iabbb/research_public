<?php

/*
 * 12/28/15 MJS - new file
 * 08/26/16 MJS - align column headers
 * 05/07/19 MJS - added iabbb
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckCouncilOnly($BBBID);


// input

$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iCouncil = NoApost($_REQUEST['iCouncil']);
if (! $iCouncil) $iCouncil = 'both';
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'hits DESC';
$iShowSource = $_POST['iShowSource'];

$SortFields = array(
	'Report name' => 'LogReportName',
	'Hits' => 'hits',
);

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Activity from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddRadio('iCouncil', 'IABBB', $iCouncil, array(
		'IABBB' => 'yes',
		'Non-IABBB' => 'no',
		'Both' => 'both',
	)
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
			REPLACE(LogReportName,'_',' ') as LogReportName,
			COUNT(*) as hits
		FROM tblDataWarehouseLog WITH (NOLOCK)
		WHERE
			LogRunDate >= '{$iDateFrom}' and LogRunDate <= '{$iDateTo}' and
			LogSearchForm = 'False' and
			LogReportName not like '%.%' and
			(
				'{$iCouncil}' = 'both' or
				('{$iCouncil}' = 'yes' and (LogUserName like '%council.bbb.org' or LogUserName like '%@iabbb%')) or
				('{$iCouncil}' = 'no' and not LogUserName like '%council.bbb.org' and not LogUserName like '%@iabbb%')
			) and
			LogReportName NOT in ('BBBMainMenu', 'BusinessDetails', 'Instructions', 'BBBDetails', 'ComplaintDetails', 'MapBBB') and
			LogReportName NOT like 'BBBsRanked%'
		GROUP BY LogReportName
		";
	if ($iSortBy) $query .= " ORDER BY " . $iSortBy;

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
				array('Report Name', $SortFields['Report name'], '', 'left'),
				array('Hits', $SortFields['Hits'], '', 'right'),
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
					$fields[0],
					$fields[1],
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>