<?php

/*
 * 12/01/15 MJS - new file
 * 12/15/15 MJS - fixed bug with iMaxRecs not working
 * 12/15/15 MJS - restricted access to CBBB only
 * 12/29/15 MJS - fixed to omit archived and suppressed pages
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
$page->CheckCouncilOnly($BBBID);


// input

$iKeyword = NoAPost($_POST['iKeyword']);
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Views DESC';
$iShowSource = $_POST['iShowSource'];

$SortFields = array(
	'Views' => 'Views',
	'Page title' => 'p.PageTitle',
);

$input_form = new input_form($conn);
$input_form->AddTextField('iKeyword', 'Search term', $iKeyword, "width:175px;", '', '', 'required');
$input_form->AddDateField('iDateFrom','Views from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT TOP {$iMaxRecs}
			v.SearchUsed,
			v.PageID,
			p.PageTitle,
			'viewpage.php?PageID=' + cast(v.PageID as varchar(10)) as Link,
			COUNT(*) as Views
		FROM IntranetPageView v WITH (NOLOCK)
		INNER JOIN IntranetPage p WITH (NOLOCK) ON p.PageID = v.PageID
		WHERE
			v.SearchUsed = '{$iKeyword}' AND
			v.DateViewed >= '{$iDateFrom}' and v.DateViewed <= '{$iDateTo}' AND
			(p.Archived = 0 or p.Archived is null) and
			(DateBecomesActive is null or DateBecomesActive <= GETDATE()) and
			(DateExpires is null or DateExpires > GETDATE()) and
			(p.Suppressed = 0 or p.Suppressed is null)
		GROUP BY v.SearchUsed, v.PageID, p.PageTitle
		HAVING COUNT(*) > 0
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
				array('Search Term', '', '', 'left'),
				array('Page Title', $SortFields['Page title'], '', 'left'),
				array('Link', '', '', 'left'),
				array('Views', $SortFields['Views'], '', 'right'),
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

			$link = '';
			if ($fields[3]) $link = "<a target=_new href='" . $fields[3] . "'>Link</a>";

			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[2],
					$link,
					$fields[4],
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>