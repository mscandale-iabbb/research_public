<?php

/*
 * 07/10/15 MJS - new report
 * 07/15/15 MJS - revised for latest logic and format
 * 08/19/15 MJS - use latest Census data (not necessarily current year) for establishments
 * 12/15/15 MJS - restricted access to CBBB only
 * 12/15/15 MJS - ensured Scam Tracker records won't appear
 * 06/07/17 MJS - cleaned up code
 * 03/08/18 MJS - fixed market share calculation
 * 03/16/18 MJS - changed words Business Review to Business Profile
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


$iMonth = ValidMonth( Numeric2( GetInput('iMonth',date('n')) ) );
$iYear = ValidYear( Numeric2( GetInput('iYear',date('Y')) ) );
$iDuesYear = ValidYear( Numeric2( GetInput('iDuesYear',date('Y') - 2) ) );
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iMonth', 'Month', $iMonth, "width:35px;", '', 'month');
$input_form->AddTextField('iYear', ' / ', $iYear, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iDuesYear', 'Most recent dues year', $iDuesYear, "width:50px;", '', 'year');

$input_form->AddExportOptions();
$input_form->AddSourceOption();
//$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @xmonth smallint = '{$iMonth}';
		declare @xyear smallint = '{$iYear}';
		declare @duesyear smallint = '{$iDuesYear}';
		
		/* Calculate from and to dates for retention rate */
		BEGIN
			declare @dateto date;
			set @dateto = CONVERT(datetime, cast(@xmonth as varchar(2)) + '/1/' + cast(@xyear as varchar(4)));
			/* set Date To to the last day of month */
			IF MONTH(@dateto) < 12 BEGIN
				SET @dateto = CONVERT(datetime,
					CAST(MONTH(@dateto) + 1 as varchar(2)) + '/' + '1/' + CAST(YEAR(@dateto) as varchar(4))
					) - 1;
			END
			IF MONTH(@dateto) = 12 BEGIN
				SET @dateto = CONVERT(datetime, '12/31/' + CAST(YEAR(@dateto) as varchar(4))
					) - 1;
			END
			/* subtract a month if running before the 6th day of the month */
			if DAY(GETDATE()) < 6 AND DATEDIFF(day,GETDATE(),@dateto) < 6 SET @dateto = DATEADD(month,-1,@dateto);
			/* don't allow future dates */
			IF @dateto > GETDATE() SET @dateto = GETDATE();
			/* set from date */
			declare @monthfrom int;
			declare @yearfrom int;
			IF MONTH(@dateto) < 12 BEGIN
				SET @monthfrom = MONTH(@dateto) + 1;
				SET @yearfrom = YEAR(@dateto) - 1;
			END
			IF MONTH(@dateto) = 12 BEGIN
				SET @monthfrom = 1;
				SET @yearfrom = YEAR(@dateto);
			END
			declare @datefrom date;
			set @datefrom = CONVERT(datetime,
				cast(@monthfrom as varchar(2)) + '/1/' +
				cast(@yearfrom as varchar(4))	);
		END
		
		SELECT
		
			/*'Run ' +*/ cast(CAST(GETDATE() as date) as varchar(10)) as 'Report Run Date',
			/*'Month ' +*/ cast(@xmonth as varchar(2)) + '/' +
				cast(@xyear as varchar(4)) as 'Selected Month',
			cast(@datefrom as varchar(10)) + ' to ' +
				cast(@dateto as varchar(10)) as 'Selected Retention Period',
			/*'Year ' +*/ cast(@duesyear as varchar(4)) as 'Most Recent Financials Year',
			/*'' as ' ',*/
		
			/* 1st section */
		
			(select sum(f.DuesRevenue) from BBBFinancials f WITH (NOLOCK) where
				f.[Year] = @duesyear
			) as 'Total BBB Dues Revenue, Most Recent Financials Year',
			(
				(
					(SELECT SUM(SnapshotStats.CountOfABs) from SnapshotStats
						WITH (NOLOCK)
						where
						[MonthNumber] = MONTH(@datefrom) and
						[Year] = YEAR(@datefrom)
					) -
					(SELECT SUM(SnapshotStats.CountOfDroppedABs) from SnapshotStats
						WITH (NOLOCK)
						where
						CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
							CAST( [Year] AS VARCHAR(4)) ) >= @datefrom and
						CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' +
							CAST( [Year] AS VARCHAR(4)) ) <= @dateto
					)
				) /
				cast ( (SELECT SUM(SnapshotStats.CountOfABs) from SnapshotStats
					WITH (NOLOCK)
					where
					[MonthNumber] = MONTH(@datefrom) and
					[Year] = YEAR(@datefrom)
					) as decimal(14,2)
				)
			) as 'Total BBB Retention Rate, Selected Retention Period',
			(SELECT sum(SnapshotStats.CountOfNewABs)
				from SnapshotStats WITH (NOLOCK) where
					SnapshotStats.MonthNumber = @xmonth and
					SnapshotStats.[Year] = @xyear
			) as 'New ABs, Selected Month',
			(SELECT sum(SnapshotStats.CountOfBillableABs)
				from SnapshotStats WITH (NOLOCK) where
					SnapshotStats.MonthNumber = @xmonth and
					SnapshotStats.[Year] = @xyear
			) as 'Number of Billable Accredited Businesses, Selected Month',
			(	select AVG( ABS( cast (DATEDIFF(
					year, GETDATE(), BusinessProgramParticipation.DateFrom
					) as decimal) ) )
				from Business WITH (NOLOCK)
				inner join BusinessProgramParticipation WITH (NOLOCK) on
				BusinessProgramParticipation.BBBID = Business.BBBID AND
				BusinessProgramParticipation.BusinessID = Business.BusinessID and
				(BBBProgram = 'Membership' or
				BBBProgram = 'BBB Accredited Business') and
				NOT BusinessProgramParticipation.DateFrom IS NULL
				where (DateTo > GETDATE() OR DateTo IS NULL)
			) as 'Average Years as an AB, Report Run Date',
			(
				(SELECT sum(SnapshotStats.CountOfABs) from SnapshotStats WITH (NOLOCK) where SnapshotStats.MonthNumber = @xmonth and SnapshotStats.[Year] = @xyear)
				/
				cast ((select sum(f.EstabsInArea) from BBBFinancials f WITH (NOLOCK) where f.[Year] = @xyear
					/*
					(select count(*) from BBBFinancials f2 WITH (NOLOCK) WHERE
						f2.BBBID = f.BBBID and f2.BBBBranchID = f.BBBBranchID and
						f2.[Year] > f.[Year]) = 0
					*/
				) as decimal(14,0))
			) as 'Total Market Share, Selected Month',
			(select distinct count(*) from Business WITH (NOLOCK) where NOT ReportURL is NULL
			) as 'Total Business Profiles, Report Run Date',
			(	select COUNT(*) from Business WITH (NOLOCK)
				inner join tblRatingCodes WITH (NOLOCK) on
					tblRatingCodes.BBBRatingCode = Business.BBBRatingGrade
				where
				BBBRatingGrade != 'NR' AND BBBRatingGrade != 'NA' AND
				BBBRatingGrade != '' AND NOT BBBRatingGrade IS NULL
			) as 'With A+ to F rating, Report Run Date',
			(	select COUNT(*) from Business WITH (NOLOCK)
				inner join tblRatingCodes WITH (NOLOCK) on
					tblRatingCodes.BBBRatingCode = Business.BBBRatingGrade
				where
				BBBRatingGrade != 'NA' AND
				BBBRatingGrade != '' AND NOT BBBRatingGrade IS NULL
			) as 'With A+ to F or NR rating, Report Run Date',
			(	select distinct count(*)
				from Business WITH (NOLOCK) where NOT ReportURL is NULL and IsReportable = '1'
			) as 'Exported to Public (CIBR), Report Run Date',
		
			/* 2nd section */
		
			(select count(*) from BusinessComplaint c WITH (NOLOCK) where
				MONTH(c.DateClosed) = @xmonth and YEAR(c.DateClosed) = @xyear and
				c.CloseCode <= 999 and c.ComplaintID not like 'scam%'
			) as 'Total Complaints Processed, Selected Month',
			(
				cast (
					(select count(*) from BusinessComplaint c WITH (NOLOCK)
					inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
					where
					MONTH(c.DateClosed) = @xmonth and YEAR(c.DateClosed) = @xyear and
					c.CloseCode IN ('110','150') and c.ComplaintID not like 'scam%'
					)
				as decimal(14,2) )
				/
				cast (
					(select count(*) from BusinessComplaint c WITH (NOLOCK)
					inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
					where
					MONTH(c.DateClosed) = @xmonth and YEAR(c.DateClosed) = @xyear and
					c.CloseCode <= 200 and c.ComplaintID not like 'scam%'
					)
				as decimal(14,2) )
			) as 'Complaint Resolution Rate Total %, Selected Month',
			(
				cast (
					(select count(*) from BusinessComplaint c WITH (NOLOCK)
					inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
					where
					MONTH(c.DateClosed) = @xmonth and YEAR(c.DateClosed) = @xyear and
					c.CloseCode IN ('500','600','999') and c.ComplaintID not like 'scam%'
					)
				as decimal(14,2) )
				/
				cast (
					(select count(*) from BusinessComplaint c WITH (NOLOCK)
					inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
					where
					MONTH(c.DateClosed) = @xmonth and YEAR(c.DateClosed) = @xyear and
					c.CloseCode IN ('110','120','150','200','300','500','600','999') and
					c.ComplaintID not like 'scam%'
					)
				as decimal(14,2) )
			) as 'Complaint Not Processed Total %, Selected Month',
			( select count(*) from BusinessInvestigation i WITH (NOLOCK)
				where
				MONTH(i.DateOfInvestigation) = @xmonth and YEAR(i.DateOfInvestigation) = @xyear
			) as 'Investigations Conducted, Selected Month',
			( select count(*) from BusinessAdReview a WITH (NOLOCK)
				where
				MONTH(a.DateClosed) = @xmonth and YEAR(a.DateClosed) = @xyear
			) as 'Advertising Reviews Processed, Selected Month',
			( select sum(SnapshotStats.CountOfInquiries) from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.MonthNumber = @xmonth and
				SnapshotStats.[Year] = @xyear
			) as 'Inquiries, Selected Month'
			;

		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Statistic', ''),
				array('Value', ''),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow( array( 'Total BBB Dues Revenue ' . $fields[3], intval(round($fields[4],0)),) );
			$report->WriteReportRow( array( 'Total BBB Retention Rate ' . $fields[2], FormatPercentage($fields[5]),) );
			$report->WriteReportRow( array( 'New ABs ' . $fields[1], $fields[6],) );
			$report->WriteReportRow( array( 'Number of Billable ABs ' . $fields[1], $fields[7],) );
			$report->WriteReportRow( array( 'Average years as an AB ' . $fields[0], round($fields[8],1),) );
			$report->WriteReportRow( array( 'Total Market Share ' . $fields[1], FormatPercentage($fields[9]),) );
			$report->WriteReportRow( array( 'Total Business Profiles ' . $fields[0], $fields[10],) );
			$report->WriteReportRow( array( 'With A+ to F rating ' . $fields[0], $fields[11],) );
			$report->WriteReportRow( array( 'With A+ to F or NR rating ' . $fields[0], $fields[12],) );
			$report->WriteReportRow( array( 'Exported to Public (CIBR) ' . $fields[0], $fields[13],) );
			$report->WriteReportRow( array( 'Total Complaints Processed ' . $fields[1], $fields[14],) );
			$report->WriteReportRow( array( 'Complaint Resolution Rate Total % ' . $fields[1], FormatPercentage($fields[15]),) );
			$report->WriteReportRow( array( 'Complaint Not Processed Total % ' . $fields[1], FormatPercentage($fields[16]),) );
			$report->WriteReportRow( array( 'Investigations Conducted ' . $fields[1], $fields[17],) );
			$report->WriteReportRow( array( 'Advertising Reviews Processed ' . $fields[1], $fields[18],) );
			$report->WriteReportRow( array( 'Inquiries ' . $fields[1], $fields[19],) );

		}
		$report->Close('suppress_msg');
	}
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>