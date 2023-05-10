<?php

/*
 * 12/01/15 MJS - new file
 * 12/15/15 MJS - fixed bug with iMaxRecs not working
 * 12/15/15 MJS - restricted access to CBBB only
 * 12/16/15 MJS - added option for council/non/both
 * 03/31/16 MJS - added columsn for click-throughs and estimated results
 * 06/20/16 MJS - changed intranet search results count to be called from a custom function
 * 08/25/16 MJS - align column headers
 * 04/12/17 MJS - refactored to use search engine to count results directly
 * 05/07/19 MJS - added iabbb
 */

include '../intranet/common_includes.php';

include 'search-db-func.php';

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
$iClickThroughs = NoApost($_REQUEST['iClickThroughs']);
if (! $iClickThroughs) $iClickThroughs = 'yes';
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Searches DESC';
$iShowSource = $_POST['iShowSource'];

$SortFields = array(
	'Searches' => 'Searches',
	'Keyword' => 'SearchString',
	'Search Results' => 'SearchResults',
	'Click-Throughs' => 'ClickThroughs',
);

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Searches from',$iDateFrom);
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
			s.SearchString,
			COUNT(*) as Searches,
			0 /*CDW.dbo.CountIntranetSearchResults(s.SearchString) as SearchResults*/,
			(
				select count(*) from IntranetPageView v WITH (NOLOCK) where
					v.SearchUsed = s.SearchString and
					v.DateViewed >= '{$iDateFrom}' and v.DateViewed <= '{$iDateTo}'
			) as ClickThroughs
		FROM IntranetSearch s WITH (NOLOCK) WHERE
			s.DateSearched >= '{$iDateFrom}' and s.DateSearched <= '{$iDateTo}' and
			(
				'{$iCouncil}' = 'both' or
				('{$iCouncil}' = 'yes' and (s.WhoSearched like '%council.bbb.org' or s.WhoSearched like '%@iabbb%')) or
				('{$iCouncil}' = 'no' and not s.WhoSearched like '%council.bbb.org' and not s.WhoSearched like '%@iabbb%')
			)
		GROUP BY s.SearchString
		HAVING count(*) > 0
		";
	if ($iSortBy) $query .= " ORDER BY " . $iSortBy;

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) AbortREDReport($query);
		$rs = $rsraw->GetArray();

		// add search counts
		$newarray = array();
		foreach ($rs as $element) {
			$Search = $element[0];
			$search1 = $element[0];
			$search2 = $element[0];
			$searchtype = "title";
			$archived = "0";
			$sortby = "PageTitle";
			$sortby2 = "PageTitle";
			$results = GetSearchResults();
			$numresults = count($results);
			$newarray[] = array(
				$element[0],
				$element[1],
				$numresults,
				$element[3]
			);
		}
		$rs = $newarray;

		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('Keyword', $SortFields['Keyword'], '', 'left'),
				array('Searches', $SortFields['Searches'], '', 'right'),
				array('Available Search Results', $SortFields['Search Results'], '', 'right'),
				array('Estimated Actual Click-Throughs', $SortFields['Click-Throughs'], '', 'right'),
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
					$fields[2],
					$fields[3],
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>