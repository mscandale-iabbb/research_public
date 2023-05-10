<?php

/*
 * 06/12/17 MJS - new file
 * 06/13/17 MJS - fixed bug in sorting
 * 06/13/17 MJS - fixed bug in totals row
 * 11/22/19 MJS - removed rows for positive, negative, and neutral
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
if ($iMonthTo == 0) {
	$iMonthTo = 12;
	$iYearTo--;
	$iMonthFrom = $iMonthTo;
	$iYearFrom = $iYearTo;
}
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iMonthFrom', 'Months', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'Customer Reviews Submitted' => 'CustomerReviewsSubmitted', 
	'Customer Reviews Verified' => 'CustomerReviewsVerified', 
	'Customer Reviews Approved' => 'CustomerReviewsApproved', 
	'Customer Reviews Published' => 'CustomerReviewsPublished'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @datefrom date;
		set @datefrom = CONVERT(datetime, '{$iMonthFrom}' + '/1/' + '{$iYearFrom}');

		declare @dateto date;
		declare @tomonth int;
		declare @toyear int;
		set @tomonth = '{$iMonthTo}';
		set @toyear = '{$iYearTo}';
		if @tomonth = 12 BEGIN
			set @tomonth = 1;
			set @toyear = @toyear + 1;
		END
		else set @tomonth = @tomonth + 1;
		set @dateto = CONVERT(datetime, cast(@tomonth as varchar(2)) + '/1/' + cast(@toyear as varchar(4)) ) - 1;

		SELECT
			NickNameCity + ', ' + BBB.State,
			f.EstabsInArea,
			SalesCategory,
			tblRegions.RegionAbbreviation,
			BBB.BBBID,

			(
				coalesce(( select SUM(CountOfCustomerReviewsPositiveSubmitted) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsPositiveSubmitted is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNegativeSubmitted) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNegativeSubmitted is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNeutralSubmitted) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNeutralSubmitted is not null
				),0)
			) as CustomerReviewsSubmitted,

			(
				coalesce(( select SUM(CountOfCustomerReviewsPositiveVerified) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsPositiveVerified is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNegativeVerified) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNegativeVerified is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNeutralVerified) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNeutralVerified is not null
				),0)
			) as CustomerReviewsVerified,

			(
				coalesce(( select SUM(CountOfCustomerReviewsPositiveApproved) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsPositiveApproved is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNegativeApproved) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNegativeApproved is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNeutralApproved) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNeutralApproved is not null
				),0)
			) as CustomerReviewsApproved,

			(
				coalesce(( select SUM(CountOfCustomerReviewsPositivePublished) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsPositivePublished is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNegativePublished) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNegativePublished is not null
				),0) +
				coalesce(( select SUM(CountOfCustomerReviewsNeutralPublished) from MiscStats WITH (NOLOCK)
					where MiscStats.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
						CAST( [Year] AS VARCHAR) ) <= @dateto and
					CountOfCustomerReviewsNeutralPublished is not null
				),0)
			) as CustomerReviewsPublished

		from BBB WITH (NOLOCK)
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		inner join BBBFinancials f WITH (NOLOCK) on
			f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and /*f.[Year] = YEAR(GETDATE())*/
				(select count(*) from BBBFinancials f2 WITH (NOLOCK) WHERE
				f2.BBBID = f.BBBID and f2.BBBBranchID = f.BBBBranchID and
				f2.[Year] > f.[Year]) = 0
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('#', '', '', 'right'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('Submitted', $SortFields['Customer Reviews Submitted'], '', 'right'),
				array('Verified', $SortFields['Customer Reviews Verified'], '', 'right'),
				array('Approved', $SortFields['Customer Reviews Approved'], '', 'right'),
				array('Published', $SortFields['Customer Reviews Published'], '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[13] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[4] .
						"><span class='{$class}'>" . AddApost($fields[0]) . "</span></a>",
					$fields[2],
					$fields[3],
					$fields[5],
					$fields[6],
					$fields[7],
					$fields[8],
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'',
				'Totals',
				'',
				'',
				array_sum( get_array_column($rs, 5) ),
				array_sum( get_array_column($rs, 6) ),
				array_sum( get_array_column($rs, 7) ),
				array_sum( get_array_column($rs, 8) ),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Averages',
				'',
				'',
				intval(array_sum( get_array_column($rs, 5) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 6) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 7) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 8) ) / count($rs)),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Medians',
				'',
				'',
				intval(GetMedian( get_array_column($rs, 5) ) ),
				intval(GetMedian( get_array_column($rs, 6) ) ),
				intval(GetMedian( get_array_column($rs, 7) ) ),
				intval(GetMedian( get_array_column($rs, 8) ) ),
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