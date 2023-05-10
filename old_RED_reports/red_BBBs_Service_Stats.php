<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 08/19/15 MJS - use latest Census data (not necessarily current year) for establishments
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 08/24/16 MJS - aligned column headers
 * 06/12/17 MJS - added customer reviews
 * 06/13/17 MJS - fixed bug in sorting
 * 03/16/18 MJS - added option to select by BBB
 * 04/02/18 MJS - cleaned up code
 * 04/09/18 MJS - added customer reviews submitted
 * 02/01/19 MJS - added columns for Businesses and ReportableBusinesses
 * 06/05/19 MJS - changed invests and adrevs to points
 * 11/06/19 MJS - added column for persons in area
 * 01/16/20 MJS - added column for NR Businesses
 * 01/17/20 MJS - modified calculation for NR Businesses
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
$iBBBID = NoApost($_POST['iBBBID']);
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
$input_form->AddMultipleSelectField('iBBBID', 'BBBs', $iBBBID,
	$input_form->BuildBBBCitiesArray('all'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'Estabs in area' => 'EstabsInArea',
	'Persons in area' => 'PersonsInArea',
	'Inquiries' => 'Inquiries',
	'Complaints' => 'Complaints',
	'Customer reviews published' => 'CustomerReviewsPublished',
	'Customer reviews received' => 'CustomerReviewsSubmitted',
	'Reportable complaints' => 'ReportableComplaints',
	'Mediations' => 'Mediations',
	'Arbitrations non-autoline' => 'ArbsNonAutoline',
	'Arbitrations offered non-autoline' => 'ArbsOffered',
	'Investigations' => 'Investigations',
	'Ad review' => 'AdReview',
	'Inquiries referred' => 'InqsReferred',
	'Businesses' => 'Businesses',
	'Reportable businesses' => 'ReportableBusinesses',
	'NR businesses' => 'BusinessesWithNR'
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
			( select sum(SnapshotStats.CountOfInquiries) from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @dateto
			) as Inquiries,
			( select sum(SnapshotStats.CountOfComplaints) from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @dateto
			) as Complaints,
			( select sum(SnapshotStats.CountOfReportableComplaints) from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @dateto
			) as ReportableComplaints,
			( select sum(CountOfMediationsFormal) from MiscStats WITH (NOLOCK)
				where MiscStats.BBBID = BBB.BBBID AND
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @dateto
			) as Mediations,
			( select sum(CountOfArbitrationsNonAutoline) from MiscStats WITH (NOLOCK)
				where MiscStats.BBBID = BBB.BBBID AND
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @dateto
			) as ArbsNonAutoline,
			( select sum(CountOfArbitrationsOfferedNonAutoline) from MiscStats WITH (NOLOCK)
				where MiscStats.BBBID = BBB.BBBID AND
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @dateto
			) as ArbsOffered,
			( select sum(case when InvestTier is null then '1' else InvestTier end) from BusinessInvestigation WITH (NOLOCK)
				where BusinessInvestigation.BBBID = BBB.BBBID and
				DateOfInvestigation >= @datefrom and
				DateOfInvestigation <= @dateto
			) as Investigations,
			( select sum(case when AdTier is null then '1' else AdTier end) from BusinessAdReview WITH (NOLOCK)
				where BusinessAdReview.BBBID = BBB.BBBID and
				DateClosed >= @datefrom and
				DateClosed <= @dateto
			) as AdReview,
			( select SUM(CountOfInquiriesReferred) from MiscStats WITH (NOLOCK)
				where MiscStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @dateto
			) as InqsReferred,
			BBB.BBBID,
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
			) as CustomerReviewsPublished,
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
				select count(*) from Business WITH (NOLOCK) where
				Business.BBBID = BBB.BBBID
			) as Businesses,
			(
				select count(*) from Business WITH (NOLOCK) where
				Business.BBBID = BBB.BBBID and Business.IsReportable = '1'
			) as ReportableBusinesses,
			f.PersonsInArea,
			(
				(
					select count(*) from Business WITH (NOLOCK) where
					Business.BBBID = BBB.BBBID and Business.BBBRatingGrade = 'NR' and Business.IsReportable = '1'
				) /
				((
					select count(*) from Business WITH (NOLOCK) where
					Business.BBBID = BBB.BBBID and Business.IsReportable = '1'
				) * 1.00)
			) as BusinessesWithNR
		from BBB WITH (NOLOCK)
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		inner join BBBFinancials f WITH (NOLOCK) on
			f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and /*f.[Year] = YEAR(GETDATE())*/
				(select count(*) from BBBFinancials f2 WITH (NOLOCK) WHERE
				f2.BBBID = f.BBBID and f2.BBBBranchID = f.BBBBranchID and
				f2.[Year] > f.[Year]) = 0
		where
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iBBBID}' = '' or BBB.BBBID IN ('" . str_replace(",", "','", $iBBBID) . "')) and
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
				array('Estabs in Area', $SortFields['Estabs in area'], '', 'right'),
				array('Persons in Area', $SortFields['Persons in area'], '', 'right'),
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('Inquiries', $SortFields['Inquiries'], '', 'right'),
				array('Total Complaints', $SortFields['Complaints'], '', 'right'),
				array('Reportable Complaints', $SortFields['Reportable complaints'], '', 'right'),
				array('Customer Reviews Received', $SortFields['Customer reviews received'], '', 'right'),
				array('Customer Reviews Published', $SortFields['Customer reviews published'], '', 'right'),
				array('Mediations', $SortFields['Mediations'], '', 'right'),
				array('Arbitrations Non-Auto', $SortFields['Arbitrations non-autoline'], '', 'right'),
				array('Arbitrations Offered Non-Auto', $SortFields['Arbitrations offered non-autoline'], '', 'right'),
				array('Investigations Points', $SortFields['Investigations'], '', 'right'),
				array('Ad Review Points', $SortFields['Ad review'], '', 'right'),
				array('Inquiries Referred', $SortFields['Inquiries referred'], '', 'right'),
				array('Businesses', $SortFields['Businesses'], '', 'right'),
				array('Reportable Businesses', $SortFields['Reportable businesses'], '', 'right'),
				array('NR Businesses', $SortFields['NR businesses'], '', 'right'),
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
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[13] .
						"><span class='{$class}'>" . AddApost($fields[0]) . "</span></a>",
					$fields[1],
					$fields[18],
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[15],
					$fields[14],
					$fields[7],
					$fields[8],
					$fields[9],
					$fields[10],
					$fields[11],
					$fields[12],
					$fields[16],
					$fields[17],
					FormatPercentage($fields[19],0),
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'',
				'Totals',
				array_sum( get_array_column($rs, 1) ),
				array_sum( get_array_column($rs, 18) ),
				'',
				'',
				array_sum( get_array_column($rs, 4) ),
				array_sum( get_array_column($rs, 5) ),
				array_sum( get_array_column($rs, 6) ),
				array_sum( get_array_column($rs, 15) ),
				array_sum( get_array_column($rs, 14) ),
				array_sum( get_array_column($rs, 7) ),
				array_sum( get_array_column($rs, 8) ),
				array_sum( get_array_column($rs, 9) ),
				array_sum( get_array_column($rs, 10) ),
				array_sum( get_array_column($rs, 11) ),
				array_sum( get_array_column($rs, 12) ),
				array_sum( get_array_column($rs, 16) ),
				array_sum( get_array_column($rs, 17) ),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Averages',
				intval(array_sum( get_array_column($rs, 1) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 18) ) / count($rs)),
				'',
				'',
				intval(array_sum( get_array_column($rs, 4) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 5) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 6) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 15) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 14) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 7) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 8) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 9) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 10) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 11) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 12) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 16) ) / count($rs)),
				intval(array_sum( get_array_column($rs, 17) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 19) ) / count($rs),0),
			)
		);
		$report->WriteTotalsRow(
			array (
				'',
				'Medians',
				intval(GetMedian( get_array_column($rs, 1) ) ),
				intval(GetMedian( get_array_column($rs, 18) ) ),
				'',
				'',
				intval(GetMedian( get_array_column($rs, 4) ) ),
				intval(GetMedian( get_array_column($rs, 5) ) ),
				intval(GetMedian( get_array_column($rs, 6) ) ),
				intval(GetMedian( get_array_column($rs, 15) ) ),
				intval(GetMedian( get_array_column($rs, 14) ) ),
				intval(GetMedian( get_array_column($rs, 7) ) ),
				intval(GetMedian( get_array_column($rs, 8) ) ),
				intval(GetMedian( get_array_column($rs, 9) ) ),
				intval(GetMedian( get_array_column($rs, 10) ) ),
				intval(GetMedian( get_array_column($rs, 11) ) ),
				intval(GetMedian( get_array_column($rs, 12) ) ),
				intval(GetMedian( get_array_column($rs, 16) ) ),
				intval(GetMedian( get_array_column($rs, 17) ) ),
				FormatPercentage(GetMedian( get_array_column($rs, 19) ),0),
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