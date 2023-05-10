<?php

/*
 * 01/04/15 MJS - new file
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

$iArchived = NoApost($_REQUEST['iArchived']);
if (! $iArchived) $iArchived = 'no';
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'tempPageTitle';
$iShowSource = $_POST['iShowSource'];
$SortFields = array(
	'Page title' => 'tempPageTitle',
	'URL' => 'tempURL',
	'Keywords' => 'tempKeywords'
);
$input_form = new input_form($conn);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		create table #temp (
			tempPageTitle	varchar(1000),
			tempURL			varchar(255),
			tempKeywords	varchar(max)
		);

		declare @PageID as int;
		declare @PageTitle as varchar(100);
		declare @Keyword as varchar(30);
		declare @Priority as varchar(20);
		declare @KeywordList as varchar(max);
		
		declare c cursor for
			select TOP {$iMaxRecs}
				p.PageID,
				p.PageTitle
			from IntranetPage p with (nolock)
			where
				(archived = 0 or archived is null) and
				(suppressed = 0 or suppressed is null) and
				(DateBecomesActive is null or DateBecomesActive <= GETDATE()) and
				(DateExpires is null or DateExpires > GETDATE())
			order by p.PageTitle;
		open c;
		fetch next from c into @PageID, @PageTitle;
		while @@fetch_status = 0
		begin
			set @KeywordList = '';
			declare c2 cursor
			for
				SELECT
					k.Keyword,
					case
						when k.PointsAdded = 1 then 'L'
						when k.PointsAdded = 2 then 'M'
						when k.PointsAdded = 10 then 'H'
					end as Priority
				FROM IntranetPageKeyword k with (nolock)
					where k.PageID = @PageID
				ORDER BY k.Keyword;
			open c2;
			fetch next from c2 into @Keyword, @Priority;
			while @@fetch_status = 0
			begin
				set @KeywordList = @KeywordList + @Keyword + ' (' + @Priority + '), ';
				fetch next from c2 into @Keyword, @Priority;
			end
			close c2;
			deallocate c2;

			insert into #temp (tempPageTitle, tempURL, tempKeywords) values (
				@PageTitle,
				'https://bbb-services.bbb.org/intranet/viewpage.php?PageID=' + cast(@PageID as varchar(6)) + '	',
				@KeywordList
			);
		
			fetch next from c into @PageID, @PageTitle;
		end
		close c;
		deallocate c;
		select * from #temp
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
				array('Link', '', '', 'left'),
				array('Keywords', '', '', 'left'),
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
			if ($fields[1]) $link = "<a target=_new href='{$fields[1]}'>Link</a>";

			$report->WriteReportRow(
				array (
					$fields[0],
					$link,
					$fields[2],
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>