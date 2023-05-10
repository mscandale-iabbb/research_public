<?php

/*
 * 12/01/15 MJS - new file
 * 12/15/15 MJS - fixed bug with iMaxRecs not working
 * 12/15/15 MJS - restricted access to CBBB only
 * 12/15/15 MJS - modified to use DateBecomesActive and DateExpires
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

$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iArchived = NoApost($_REQUEST['iArchived']);
if (! $iArchived) $iArchived = 'no';
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'p.PageTitle';
$iShowSource = $_POST['iShowSource'];

$SortFields = array(
	'Page title' => 'p.PageTitle',
	'Date created' => 'p.DateCreated',
	'Archived' => 'Archived',
	'Last viewed' => 'LastViewed',
	'Total views' => 'TotalViews',
);

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Activity from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddRadio('iArchived', 'Archived', $iArchived, array(
	'Archived' => 'yes',
	'Not archived' => 'no',
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
			p.PageTitle,
			p.DateCreated,
			case when p.Archived = '1' then 'Yes' else 'No' end as Archived,
			'viewpage.php?PageID=' + cast(p.PageID as varchar(10)) as Link,
			(select top 1 v.DateViewed from IntranetPageView v WITH (NOLOCK) WHERE
				v.PageID = p.PageID and
				v.DateViewed >= '{$iDateFrom}' and v.DateViewed <= '{$iDateTo}'
				order by v.DateViewed DESC) as LastViewed,
			(select count(*) from IntranetPageView v WITH (NOLOCK) WHERE
				v.PageID = p.PageID and
				v.DateViewed >= '{$iDateFrom}' and v.DateViewed <= '{$iDateTo}'
				) as TotalViews
		FROM IntranetPage p WITH (NOLOCK)
		WHERE
			p.DateCreated <= '{$iDateFrom}' and
			(
				'{$iArchived}' = 'both' or
				(
					'{$iArchived}' = 'yes' and
					(
						p.Archived = 1 or
						(DateBecomesActive is not null or DateBecomesActive > GETDATE()) or
						(DateExpires is not null or DateExpires <= GETDATE())
					)
				) or
				(
					'{$iArchived}' = 'no' and
					(p.Archived = 0 or p.Archived is null) and
					(DateBecomesActive is null or DateBecomesActive <= GETDATE()) and
					(DateExpires is null or DateExpires > GETDATE())
				)
			) and
			(p.Suppressed = 0 or p.Suppressed is null)
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
				array('Page Title', $SortFields['Page title'], '', 'left'),
				array('Date Created', $SortFields['Date created'], '', 'left'),
				array('Archived', '', '', 'left'),
				array('Link', '', '', 'left'),
				array('Last Viewed', $SortFields['Last viewed'], '', 'left'),
				array('Total Views', $SortFields['Total views'], '', 'right'),
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
					FormatDate($fields[1]),
					$fields[2],
					$link,
					FormatDate($fields[4]),
					$fields[5],
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>