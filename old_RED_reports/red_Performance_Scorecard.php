<?php

/*
 * 10/19/15 MJS - new file
 * 10/20/15 MJS - added more
 * 10/21/15 MJS - modified renewals
 * 10/28/15 MJS - tweaked labels
 * 12/15/15 MJS - ensured Scam Tracker records won't appear
 * 05/24/16 MJS - changed "ABs 2015" to "ABs 1/2015", etc.
 * 08/26/16 MJS - align column headers
 * 12/08/16 MJS - changed REQUEST to POST
 * 12/08/16 MJS - fixed division by zero
 * 12/19/16 MJS - added inquiries, complaints, meds, arbs, dropped abs
 * 12/21/16 MJS - added Not Reportable Rate and other columns
 * 01/10/17 MJS - changed calls to define links and tabs
 * 01/10/17 MJS - fixed typo
 * 01/10/17 MJS - fixed EstabsInArea
 * 01/10/17 MJS - fixed division by zero error
 * 02/08/17 MJS - fixed another division by zero error
 * 03/08/17 MJS - fixed market penetration calculations
 * 11/09/17 MJS - fixed bug in calculating NotProcessedRateLastYear
 * 11/13/17 MJS - fixed another bug in calculating NotProcessedRateLastYear
 * 03/06/18 MJS - changed YTD fields to be based on year To, not year From
 * 03/07/18 MJS - fixed division by zero errors
 * 03/07/18 MJS - changed column labels to month ranges instead of "month", "ytd", etc.
 * 04/12/18 MJS - fixed ad revs and invests to count points based on tiers
 * 07/05/18 MJS - excluded inactive BBBs
 * 07/11/18 MJS - changed Jan ABs to current month ABs 
 * 03/25/19 MJS - changed estabs to use year to instead of year from
 * 05/16/19 MJS - used SETTINGS for org name
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = Numeric2($_POST['iBBBID']);
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
else if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',date('n') - 1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
$iLastYearTo = $iYearTo - 1;
if ($iMonthFrom == 0) {
	$iMonthFrom = 12;
	$iYearFrom--;
}
if ($iMonthTo == 0) {
	$iMonthTo = 12;
	$iYearTo--;
	$iMonthFrom = $iMonthTo;
	$iYearFrom = $iYearTo;
}
$iShowSource = $_POST['iShowSource'];

$DateFrom = date( 'n/j/Y', strtotime('-1 day', strtotime( date('n') . '/1/' . date('Y') )) );
$DateTo = date( 'n/j/Y', strtotime('-1 day', strtotime( date('n') . '/1/' . date('Y') )) );

$not_sent_to_RED = "<span class=gray01>n/a</span>";

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('yoursonly') );
$input_form->AddTextField('iMonthFrom', 'Months', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "

		declare @xmonthfrom int;
		declare @xyearfrom int;
		declare @xmonthto int;
		declare @xyearto int;
		declare @datefrom date;
		declare @dateto date;
		declare @dayrange decimal(14,2);
		declare @factor decimal(14,2);
		declare @firstofyear date;
		declare @datefromlastyear date;
		declare @datetolastyear date;
		declare @firstofyearlastyear date;

		set @xmonthfrom = CAST('{$iMonthFrom}' as int);
		set @xyearfrom = CAST('{$iYearFrom}' as int);
		
		set @xmonthto = CAST('{$iMonthTo}' as int) + 1;
		set @xyearto = CAST('{$iYearTo}' as int);
		if @xmonthto = 13 set @xyearto = @xyearto + 1;
		if @xmonthto = 13 set @xmonthto = 1;

		set @datefrom = CONVERT(datetime,
			'{$iMonthFrom}' + '/1/' + cast(@xyearfrom as varchar(4)) );
		set @dateto = CONVERT(datetime,
			cast(@xmonthto as varchar(2)) + '/1/' + cast(@xyearto as varchar(4))
			) - 1;
		set @firstofyear = CONVERT(datetime,
			'1/1/' + cast(@xyearto as varchar(4)) );

		set @dayrange = DATEDIFF(day, @datefrom, @dateto);
		set @factor = (@dayrange / 365.00);

		set @datefromlastyear = CONVERT(datetime,
			'{$iMonthFrom}' + '/1/' + cast((@xyearto - 1) as varchar(4)) );
		set @datetolastyear = CONVERT(datetime,
			cast(@xmonthto as varchar(2)) + '/1/' + cast((@xyearto - 1) as varchar(4))
			) - 1;
		set @firstofyearlastyear = CONVERT(datetime,
			'1/1/' + cast((@xyearto - 1) as varchar(4)) );

		/* retention date from */
		declare @retentionmonthfrom int;
		declare @retentionyearfrom int;
		IF MONTH(@dateto) < 12 BEGIN
			SET @retentionmonthfrom = MONTH(@dateto) + 1;
			SET @retentionyearfrom = YEAR(@dateto) - 1;
		END
		IF MONTH(@dateto) = 12 BEGIN
			SET @retentionmonthfrom = 1;
			SET @retentionyearfrom = YEAR(@dateto);
		END
		declare @retentiondatefrom date;
		set @retentiondatefrom = CONVERT(datetime,
			cast(@retentionmonthfrom as varchar(2)) + '/1/' +
			cast(@retentionyearfrom as varchar(4))	);
		declare @retentiondatefromlastyear date;
		set @retentiondatefromlastyear = CONVERT(datetime,
			cast(@retentionmonthfrom as varchar(2)) + '/1/' +
			cast((@retentionyearfrom - 1) as varchar(4))	);

		declare @firms int;
		set @firms = (select EstabsInArea from BBBFinancials f WITH (NOLOCK) where
			f.BBBID = '{$iBBBID}' and f.BBBBranchID = 0 and f.[Year] = @xyearto);

		SELECT
			BBB.NicknameCity + ', ' + BBB.State,
			@firms as Firms,
			(
				SELECT 1.00 - (
        				CAST(
                				(SELECT SUM(CountOfDroppedABs) from SnapshotStats s WITH (NOLOCK) WHERE
                        				s.BBBID = BBB.BBBID AND
							CountOfABS is not NULL and
							CONVERT(datetime,
								cast(s.MonthNumber as varchar(2)) + '/1/' +
								cast(s.[Year] as varchar(4))
							) >= @retentiondatefrom AND
							CONVERT(datetime,
								cast(s.MonthNumber as varchar(2)) + '/28/' +
								cast(s.[Year] as varchar(4))
							) <= @dateto
                				)
					as decimal (14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT CountOfABs from SnapshotStats s WITH (NOLOCK) WHERE
                        				s.BBBID = BBB.BBBID AND
							CountOfABS is not NULL and
							CONVERT(datetime,
								cast(s.MonthNumber as varchar(2)) + '/1/' +
								cast(s.[Year] as varchar(4))
							) = @retentiondatefrom
                				)
					as decimal (14,2) ))
				)
			) as RetentionRate,
			(
        			SELECT 1.00 - (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
                        				CloseCode IN ('120','122','200') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
										CloseCode IN ('110','111','112','120','121','122','150','200') and
										c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as ResolutionRate,
			(
        			SELECT (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
                        				CloseCode IN ('500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
                        				CloseCode IN ('110','111','112','120','121','122','150','200','300','500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as NotProcessedRate,
			(
        			SELECT
                			AVG( cast(
                        			dbo.CDW_WEEKDAYDIFF(DateComplaintFiledWithBBB, DateComplaintOpenedByBBB)
                				as decimal(14,2) )
					)
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @datefrom AND DateClosed <= @dateto AND
                			DateComplaintFiledWithBBB IS NOT NULL AND
							DateComplaintOpenedByBBB IS NOT NULL AND
                			DateComplaintFiledWithBBB <= DateComplaintOpenedByBBB and
                			c.ComplaintID not like 'scam%'
			) as DaysToOpen,
			(
        			SELECT AVG( cast(CountOfDaysToProcessComplaint as decimal(14,2) ) )
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @datefrom AND DateClosed <= @dateto AND
                			CountOfDaysToProcessComplaint >= 0 and
                			CloseCode != '400' and
                			c.ComplaintID not like 'scam%'
			) as DaysToClose,
			(
        			SELECT /*COUNT(*)*/
						SUM(case when AdTier = '1' or AdTier is null or AdTier = '0' or AdTier = '' then 1 else AdTier end)
        			from BusinessAdReview a WITH (NOLOCK) WHERE
                		a.BBBID = BBB.BBBID AND
                		a.DateClosed >= @datefrom AND a.DateClosed <= @dateto
				) as AdReview,
			(
        			SELECT cast(ROUND(.00075 * @firms,0) as int)
			) * @factor as AdReviewRequired,
			(
        			SELECT SUM(case when InvestTier = '1' or InvestTier is null or InvestTier = '0' or InvestTier = '' then 1 else InvestTier end)
        			from BusinessInvestigation i WITH (NOLOCK) WHERE
                		i.BBBID = BBB.BBBID AND
                		i.DateOfInvestigation >= @datefrom AND i.DateOfInvestigation <= @dateto
			) as Investigations,
			(
					SELECT cast(ROUND(.0005 * @firms,0) as int)
			) * @factor as InvestigationsRequired,
			@retentiondatefrom as RetentionRateDateFrom,
			@dateto as RetentionRateDateTo,
			(SELECT sum(SnapshotStats.CountOfNewABs)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@datefrom and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@dateto
				) as NewMembers,
			(SELECT sum(SnapshotStats.CountOfRenewals)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@datefrom and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@dateto
				) as RenewingMembers,
			(SELECT sum(SnapshotStats.CountOfNewABs)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@firstofyear and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@dateto
				) as NewMembersYTD,
			(SELECT sum(SnapshotStats.CountOfRenewals)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@firstofyear and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@dateto
				) as RenewingMembersYTD,
			(SELECT sum(SnapshotStats.CountOfNewABs)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@datefromlastyear and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@datetolastyear
				) as NewMembersLastYear,
			(SELECT sum(SnapshotStats.CountOfRenewals)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@datefromlastyear and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@datetolastyear
				) as RenewingMembersLastYear,
			(SELECT sum(SnapshotStats.CountOfNewABs)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@firstofyearlastyear and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@datetolastyear
				) as NewMembersYTDLastYear,
			(SELECT sum(SnapshotStats.CountOfRenewals)
				from SnapshotStats WITH (NOLOCK) where
				SnapshotStats.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) >=
					@firstofyearlastyear and
				CONVERT(datetime, CAST( [MonthNumber] as VARCHAR(2)) + '/1/' + CAST( [Year] AS VARCHAR(4)) ) <=
					@datetolastyear
				) as RenewingMembersYTDLastYear,
			(
        			SELECT 1.00 - (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyear AND DateClosed <= @dateto AND
                        				CloseCode IN ('120','122','200') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyear AND DateClosed <= @dateto AND
										CloseCode IN ('110','111','112','120','121','122','150','200') and
										c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as ResolutionRateYTD,
			(
        			SELECT 1.00 - (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefromlastyear AND DateClosed <= @datetolastyear AND
                        				CloseCode IN ('120','122','200') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefromlastyear AND DateClosed <= @datetolastyear AND
										CloseCode IN ('110','111','112','120','121','122','150','200') and
										c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as ResolutionRateLastYear,
			(
        			SELECT 1.00 - (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyearlastyear AND DateClosed <= @datetolastyear AND
                        				CloseCode IN ('120','122','200') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyearlastyear AND DateClosed <= @datetolastyear AND
										CloseCode IN ('110','111','112','120','121','122','150','200') and
										c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as ResolutionRateYTDLastYear,
			(
        			SELECT SUM(case when InvestTier = '1' or InvestTier is null or InvestTier = '0' or InvestTier = '' then 1 else InvestTier end)
        			from BusinessInvestigation i WITH (NOLOCK) WHERE
                		i.BBBID = BBB.BBBID AND
                		i.DateOfInvestigation >= @firstofyear AND i.DateOfInvestigation <= @dateto
			) as InvestigationsYTD,
			(
        			SELECT SUM(case when InvestTier = '1' or InvestTier is null or InvestTier = '0' or InvestTier = '' then 1 else InvestTier end)
        			from BusinessInvestigation i WITH (NOLOCK) WHERE
                		i.BBBID = BBB.BBBID AND
                		i.DateOfInvestigation >= @datefromlastyear AND i.DateOfInvestigation <= @datetolastyear
			) as InvestigationsLastYear,
			(
        			SELECT SUM(case when InvestTier = '1' or InvestTier is null or InvestTier = '0' or InvestTier = '' then 1 else InvestTier end)
        			from BusinessInvestigation i WITH (NOLOCK) WHERE
                		i.BBBID = BBB.BBBID AND
                		i.DateOfInvestigation >= @firstofyearlastyear AND i.DateOfInvestigation <= @datetolastyear
			) as InvestigationsLastYearYTD,
			(
        			SELECT AVG( cast(CountOfDaysToProcessComplaint as decimal(14,2) ) )
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @firstofyear AND DateClosed <= @dateto AND
                			CountOfDaysToProcessComplaint >= 0 and
                			CloseCode != '400' and
                			c.ComplaintID not like 'scam%'
			) as DaysToCloseYTD,
			(
        			SELECT AVG( cast(CountOfDaysToProcessComplaint as decimal(14,2) ) )
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @datefromlastyear AND DateClosed <= @datetolastyear AND
                			CountOfDaysToProcessComplaint >= 0 and
                			CloseCode != '400' and
                			c.ComplaintID not like 'scam%'
			) as DaysToCloseLastYear,
			(
        			SELECT AVG( cast(CountOfDaysToProcessComplaint as decimal(14,2) ) )
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @firstofyearlastyear AND DateClosed <= @datetolastyear AND
                			CountOfDaysToProcessComplaint >= 0 and
                			CloseCode != '400' and
                			c.ComplaintID not like 'scam%'
			) as DaysToCloseLastYearYTD,
			(
        			SELECT
                			AVG( cast(
                        			dbo.CDW_WEEKDAYDIFF(DateComplaintFiledWithBBB, DateComplaintOpenedByBBB)
                				as decimal(14,2) )
					)
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @firstofyear AND DateClosed <= @dateto AND
                			DateComplaintFiledWithBBB IS NOT NULL AND
							DateComplaintOpenedByBBB IS NOT NULL AND
                			DateComplaintFiledWithBBB <= DateComplaintOpenedByBBB and
                			c.ComplaintID not like 'scam%'
			) as DaysToOpenYTD,
			(
        			SELECT
                			AVG( cast(
                        			dbo.CDW_WEEKDAYDIFF(DateComplaintFiledWithBBB, DateComplaintOpenedByBBB)
                				as decimal(14,2) )
					)
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @datefromlastyear AND DateClosed <= @datetolastyear AND
                			DateComplaintFiledWithBBB IS NOT NULL AND
							DateComplaintOpenedByBBB IS NOT NULL AND
                			DateComplaintFiledWithBBB <= DateComplaintOpenedByBBB and
                			c.ComplaintID not like 'scam%'
			) as DaysToOpenLastYear,
			(
        			SELECT
                			AVG( cast(
                        			dbo.CDW_WEEKDAYDIFF(DateComplaintFiledWithBBB, DateComplaintOpenedByBBB)
                				as decimal(14,2) )
					)
                			FROM BusinessComplaint c WITH (NOLOCK) WHERE
                			c.BBBID = BBB.BBBID AND
                			DateClosed >= @firstofyearlastyear AND DateClosed <= @datetolastyear AND
                			DateComplaintFiledWithBBB IS NOT NULL AND
							DateComplaintOpenedByBBB IS NOT NULL AND
                			DateComplaintFiledWithBBB <= DateComplaintOpenedByBBB and
                			c.ComplaintID not like 'scam%'
			) as DaysToOpenLastYearYTD,
			(
        			SELECT SUM(case when AdTier = '1' or AdTier is null or AdTier = '0' or AdTier = '' then 1 else AdTier end)
        			from BusinessAdReview a WITH (NOLOCK) WHERE
                		a.BBBID = BBB.BBBID AND
                		a.DateClosed >= @firstofyear AND a.DateClosed <= @dateto
				) as AdReviewYTD,
			(
        			SELECT SUM(case when AdTier = '1' or AdTier is null or AdTier = '0' or AdTier = '' then 1 else AdTier end)
        			from BusinessAdReview a WITH (NOLOCK) WHERE
                		a.BBBID = BBB.BBBID AND
                		a.DateClosed >= @datefromlastyear AND a.DateClosed <= @datetolastyear
				) as AdReviewLastYear,
			(
        			SELECT SUM(case when AdTier = '1' or AdTier is null or AdTier = '0' or AdTier = '' then 1 else AdTier end)
        			from BusinessAdReview a WITH (NOLOCK) WHERE
                		a.BBBID = BBB.BBBID AND
                		a.DateClosed >= @firstofyearlastyear AND a.DateClosed <= @datetolastyear
				) as AdReviewLastYearYTD,
			(
				SELECT 1.00 - (
        				CAST(
                				(SELECT SUM(CountOfDroppedABs) from SnapshotStats s WITH (NOLOCK) WHERE
                        				s.BBBID = BBB.BBBID AND
							CountOfABS is not NULL and
							CONVERT(datetime,
								cast(s.MonthNumber as varchar(2)) + '/1/' +
								cast(s.[Year] as varchar(4))
							) >= @retentiondatefromlastyear AND
							CONVERT(datetime,
								cast(s.MonthNumber as varchar(2)) + '/28/' +
								cast(s.[Year] as varchar(4))
							) <= @datetolastyear
                				)
					as decimal (14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT CountOfABs from SnapshotStats s WITH (NOLOCK) WHERE
                        				s.BBBID = BBB.BBBID AND
							CountOfABS is not NULL and
							CONVERT(datetime,
								cast(s.MonthNumber as varchar(2)) + '/1/' +
								cast(s.[Year] as varchar(4))
							) = @retentiondatefromlastyear
                				)
					as decimal (14,2) ))
				)
			) as RetentionRateLastYear,
			@retentiondatefromlastyear as RetentionRateDateFromLastYear,
			@datetolastyear as RetentionRateDateToLastYear,
			(
        			SELECT 1.00 - (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK)
                					inner join BBB bbb2 WITH (NOLOCK) on
                						bbb2.SalesCategory = BBB.SalesCategory and bbb2.BBBBranchID = 0 and bbb2.IsActive = '1'
                					WHERE
                        				c.BBBID = bbb2.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
                        				CloseCode IN ('120','122','200') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK)
                					inner join BBB bbb2 WITH (NOLOCK) on
                						bbb2.SalesCategory = BBB.SalesCategory and bbb2.BBBBranchID = 0 and bbb2.IsActive = '1'
                					WHERE
                        				c.BBBID = bbb2.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
										CloseCode IN ('110','111','112','120','121','122','150','200') and
										c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as ResolutionRateCategory,
			(
				(
	        			SELECT SUM(case when InvestTier = '1' or InvestTier is null or InvestTier = '0' or InvestTier = '' then 1 else InvestTier end)
	        				from BusinessInvestigation i WITH (NOLOCK)
							inner join BBB bbb2 WITH (NOLOCK) on
								bbb2.SalesCategory = BBB.SalesCategory and bbb2.BBBBranchID = 0 and bbb2.IsActive = '1'
		        			WHERE
	                			i.BBBID = bbb2.BBBID AND
	                			i.DateOfInvestigation >= @datefrom AND i.DateOfInvestigation <= @dateto
				) /
				(1 + ( -- add 1 to avoid division by zero errors
	        			SELECT COUNT(*) from BBB bbb3 WITH (NOLOCK) where
								bbb3.SalesCategory = BBB.SalesCategory and bbb3.BBBBranchID = 0 and bbb3.IsActive = '1'
				))
			) as InvestigationsCategory,
			(
        			SELECT AVG( cast(CountOfDaysToProcessComplaint as decimal(14,2) ) )
                			FROM BusinessComplaint c WITH (NOLOCK)
							inner join BBB bbb4 WITH (NOLOCK) on
								bbb4.SalesCategory = BBB.SalesCategory and bbb4.BBBBranchID = 0 and bbb4.IsActive = '1'
                		WHERE
                			c.BBBID = bbb4.BBBID AND
                			DateClosed >= @datefrom AND DateClosed <= @dateto AND
                			CountOfDaysToProcessComplaint >= 0 and
                			CloseCode != '400' and
                			c.ComplaintID not like 'scam%'
			) as DaysToCloseCategory,
			(
        			SELECT
                			AVG( cast(
                        			dbo.CDW_WEEKDAYDIFF(DateComplaintFiledWithBBB, DateComplaintOpenedByBBB)
                				as decimal(14,2) )
							)
                			FROM BusinessComplaint c WITH (NOLOCK)
							inner join BBB bbb5 WITH (NOLOCK) on
								bbb5.SalesCategory = BBB.SalesCategory and bbb5.BBBBranchID = 0 and bbb5.IsActive = '1'
                			WHERE
	                			c.BBBID = bbb5.BBBID AND
	                			DateClosed >= @datefrom AND DateClosed <= @dateto AND
	                			DateComplaintFiledWithBBB IS NOT NULL AND
								DateComplaintOpenedByBBB IS NOT NULL AND
	                			DateComplaintFiledWithBBB <= DateComplaintOpenedByBBB and
	                			c.ComplaintID not like 'scam%'
			) as DaysToOpenCategory,
			(
				(
        			SELECT SUM(case when AdTier = '1' or AdTier is null or AdTier = '0' or AdTier = '' then 1 else AdTier end)
        				from BusinessAdReview a WITH (NOLOCK)
						inner join BBB bbb6 WITH (NOLOCK) on
							bbb6.SalesCategory = BBB.SalesCategory and bbb6.BBBBranchID = 0 and bbb6.IsActive = '1'
        				WHERE
                			a.BBBID = bbb6.BBBID AND
                			a.DateClosed >= @datefrom AND a.DateClosed <= @dateto
                ) /
				(1 + ( -- add 1 to avoid division by zero errors
	        			SELECT COUNT(*) from BBB bbb7 WITH (NOLOCK) where
								bbb7.SalesCategory = BBB.SalesCategory and bbb7.BBBBranchID = 0 and bbb7.IsActive = '1'
				))
			) as AdReviewCategory,
			(
				SELECT 1.00 - (
        				CAST(
                				(SELECT SUM(CountOfDroppedABs) from SnapshotStats s WITH (NOLOCK)
									inner join BBB bbb8 WITH (NOLOCK) on
										bbb8.SalesCategory = BBB.SalesCategory and bbb8.BBBBranchID = 0 and bbb8.IsActive = '1'
                					WHERE
                        				s.BBBID = bbb8.BBBID AND
										CountOfABS is not NULL and
										CONVERT(datetime,
											cast(s.MonthNumber as varchar(2)) + '/1/' +
											cast(s.[Year] as varchar(4))
										) >= @retentiondatefrom AND
										CONVERT(datetime,
											cast(s.MonthNumber as varchar(2)) + '/28/' +
											cast(s.[Year] as varchar(4))
										) <= @dateto
                				)
						as decimal (14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT SUM(CountOfABs) from SnapshotStats s WITH (NOLOCK)
									inner join BBB bbb9 WITH (NOLOCK) on
										bbb9.SalesCategory = BBB.SalesCategory and bbb9.BBBBranchID = 0 and bbb9.IsActive = '1'
                					WHERE
                        				s.BBBID = bbb9.BBBID AND
										CountOfABS is not NULL and
										CONVERT(datetime,
											cast(s.MonthNumber as varchar(2)) + '/1/' +
											cast(s.[Year] as varchar(4))
										) = @retentiondatefrom
                				)
						as decimal (14,2) ))
				)
			) as RetentionRateCategory,
			(
				CAST(
					(SELECT CountOfABs from SnapshotStats s WITH (NOLOCK) WHERE s.BBBID = BBB.BBBID AND
							CountOfABS is not NULL and
							s.MonthNumber = month(@dateto) and
							s.[Year] = year(@dateto)
					) as decimal (14,2) )
				/
				(1 + cast(@firms as decimal(14,2))) -- add 1 to avoid division by zero errors
			) as MarketPenetration,
			(
				CAST(
					(SELECT SUM(CountOfABs) from SnapshotStats s WITH (NOLOCK)
						inner join BBB bbb10 WITH (NOLOCK) on
							bbb10.SalesCategory = BBB.SalesCategory and bbb10.BBBBranchID = 0 and bbb10.IsActive = '1'
						WHERE s.BBBID = bbb10.BBBID AND CountOfABS is not NULL and
							s.MonthNumber = month(@dateto) and
							s.[Year] = year(@dateto)
					) as decimal (14,2) )
				/
				(1 + cast( -- add 1 to avoid division by zero errors
					(select SUM(EstabsInArea) from BBBFinancials f WITH (NOLOCK)
						inner join BBB bbb11 WITH (NOLOCK) on
							bbb11.SalesCategory = BBB.SalesCategory and bbb11.BBBBranchID = 0 and bbb11.IsActive = '1'
						where
							f.BBBID = bbb11.BBBID and f.BBBBranchID = 0 and f.[Year] = @xyearfrom
					) as decimal(14,2) ))
			) as MarketPenetrationCategory,
			(
				select top 1
						(
							CAST(
								(SELECT SUM(CountOfABs) from SnapshotStats s WITH (NOLOCK)
									WHERE s.BBBID = bbb99.BBBID AND CountOfABS is not NULL and
										s.MonthNumber = month(@dateto) and
										s.[Year] = year(@dateto)
								) as decimal (14,2) )
							/
							(1 + cast( -- add 1 to avoid division by zero errors
								(select SUM(EstabsInArea) from BBBFinancials f WITH (NOLOCK)
									where
										f.BBBID = bbb99.BBBID and f.BBBBranchID = 0 and f.[Year] = @xyearfrom and
										 bbb99.IsActive = '1'
								) as decimal(14,2) ))
						) as MarketPenetrationCategory
				from BBB bbb99 WITH (NOLOCK) where
					bbb99.SalesCategory = BBB.SalesCategory and bbb99.BBBBranchID = 0 and bbb99.IsActive = '1'
				order by MarketPenetrationCategory desc
			) as MarketPenetrationCategoryHighest,
			(
				select top 1
						(
							CAST(
								(SELECT SUM(CountOfABs) from SnapshotStats s WITH (NOLOCK)
									WHERE s.BBBID = bbb99.BBBID AND CountOfABS is not NULL and
										s.MonthNumber = month(@dateto) and
										s.[Year] = year(@dateto)
								) as decimal (14,2) )
							/
							(1 + cast( -- add 1 to avoid division by zero errors
								(select SUM(EstabsInArea) from BBBFinancials f WITH (NOLOCK)
									where
										f.BBBID = bbb99.BBBID and f.BBBBranchID = 0 and f.[Year] = @xyearfrom and
										bbb99.IsActive = '1'
								) as decimal(14,2) ))
						) as MarketPenetrationCategory
				from BBB bbb99 WITH (NOLOCK) where
					bbb99.SalesCategory = BBB.SalesCategory and bbb99.BBBBranchID = '0' and bbb99.IsActive = '1'
				order by MarketPenetrationCategory
			) as MarketPenetrationCategoryLowest,
			(
				SELECT CountOfABs from SnapshotStats s WITH (NOLOCK)
					WHERE s.BBBID = BBB.BBBID AND CountOfABS is not NULL and
						CONVERT(datetime,
							cast(s.MonthNumber as varchar(2)) + '/1/' +
							cast(s.[Year] as varchar(4))
						) = /*@firstofyear*/ '{$iMonthTo}/1/{$iYearTo}'
			) as ThisYearABs,
			(
				SELECT CountOfABs from SnapshotStats s WITH (NOLOCK)
					WHERE s.BBBID = BBB.BBBID AND CountOfABS is not NULL and
						CONVERT(datetime,
							cast(s.MonthNumber as varchar(2)) + '/1/' +
							cast(s.[Year] as varchar(4))
						) = /*@firstofyearlastyear*/ '{$iMonthTo}/1/{$iLastYearTo}'
			) as LastYearABs,
			YEAR(@firstofyear),
			YEAR(@firstofyearlastyear),
			BBB.SalesCategory,
			(
				select sum(s.CountOfInquiries) from SnapshotStats s WITH (NOLOCK) where
					s.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' +
						CAST( s.[Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' +
						CAST( s.[Year] AS VARCHAR) ) <= @dateto
			) as Inquiries,
			(
				select sum(s.CountOfComplaints) from SnapshotStats s WITH (NOLOCK) where
					s.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' +
						CAST( s.[Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' +
						CAST( s.[Year] AS VARCHAR) ) <= @dateto
			) as Complaints,
			(
				select sum(CountOfMediationsFormal) from MiscStats m WITH (NOLOCK)
					where m.BBBID = BBB.BBBID AND
					CONVERT(datetime, CAST( m.MonthNumber as VARCHAR) + '/1/' +
						CAST( m.[Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( m.MonthNumber as VARCHAR) + '/1/' +
						CAST( m.[Year] AS VARCHAR) ) <= @dateto
			) as Mediations,
			(
				select sum(CountOfArbitrationsNonAutoline) from MiscStats m WITH (NOLOCK)
					where m.BBBID = BBB.BBBID AND
					CONVERT(datetime, CAST( m.MonthNumber as VARCHAR) + '/1/' +
						CAST( m.[Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( m.MonthNumber as VARCHAR) + '/1/' +
						CAST( m.[Year] AS VARCHAR) ) <= @dateto
			) as Arbitrations,
			(
				select sum(s.CountOfDroppedABs) from SnapshotStats s WITH (NOLOCK) where
					s.BBBID = BBB.BBBID and
					CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' +
						CAST( s.[Year] AS VARCHAR) ) >= @datefrom and
					CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' +
						CAST( s.[Year] AS VARCHAR) ) <= @dateto
			) as DroppedABs,
			(
        			SELECT (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyear AND DateClosed <= @dateto AND
                        				CloseCode IN ('500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyear AND DateClosed <= @dateto AND
                        				CloseCode IN ('110','111','112','120','121','122','150','200','300','500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as NotProcessedRateYTD,
			(
        			SELECT (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefromlastyear AND DateClosed <= @datetolastyear AND
                        				CloseCode IN ('500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        					as decimal(14,2) )
        				/
        				(1 + CAST(  -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefromlastyear AND DateClosed <= @datetolastyear AND
                        				CloseCode IN ('110','111','112','120','121','122','150','200','300','500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        					as decimal (14,2) ))
        			)
			) as NotProcessedRateLastYear,
			(
        			SELECT (
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyearlastyear AND DateClosed <= @datetolastyear AND
                        				CloseCode IN ('500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				(1 + CAST( -- add 1 to avoid division by zero errors
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @firstofyearlastyear AND DateClosed <= @datetolastyear AND
                        				CloseCode IN ('110','111','112','120','121','122','150','200','300','500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) ))
        			)
			) as NotProcessedRateYTDLastYear

		FROM BBB BBB WITH (NOLOCK)
		WHERE
			BBB.BBBID = '{$iBBBID}' and BBB.BBBBranchID = 0
		";
	//die("<pre>" . $query . "</pre>");

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();

	$report = new report( $conn, count($rs) );
	$report->Open();
	$report->WriteHeaderRow(
		array (
			array('Finance', '', '', 'left'),
			array('Actual', '', '', 'left'),
			array('YTD Actual', '', '', 'left'),
			array('YTD Budget', '', '', 'left'),
			array('Prev Yr', '', '', 'left'),
			array('Prev Yr YTD', '', '', 'left'),
		)
	);
	$report->WriteReportRow( array( 'Revenue', $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED ) );
	$report->WriteReportRow( array( 'Expenses', $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED ) );
	$report->WriteReportRow( array( 'Net Income', $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED ) );
	$report->WriteReportRow( array( '', 'Cash Balance', $not_sent_to_RED, '', '', '' ) );
	$report->WriteReportRow( array( '', 'Days of reserves', $not_sent_to_RED, '', '', '' ) );
	$report->Close('suppress_records_message');

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$iPrevYearFrom = $iYearFrom - 1; 
		$iPrevYearTo = $iYearTo - 1;
		$report->WriteHeaderRow(
			array (
				array('Accreditation', '', '', 'left'),
				array("{$iMonthFrom}/{$iYearFrom}-{$iMonthTo}/{$iYearTo} ABs", '', '', 'right'),
				array('$', '', '', 'left'),
				array("1/{$iYearTo}-{$iMonthTo}/{$iYearTo} ABs", '', '', 'right'),
				array('$', '', '', 'left'),
				array("{$iMonthFrom}/{$iPrevYearFrom}-{$iMonthTo}/{$iPrevYearTo} ABs", '', '', 'right'),
				array('$', '', '', 'left'),
				array("1/{$iPrevYearTo}-{$iMonthTo}/{$iPrevYearTo} ABs", '', '', 'right'),
				array('$', '', '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			$Category = $fields[53];
			$report->WriteReportRow( array(
				'New',
				$fields[13],
				$not_sent_to_RED,
				$fields[15],
				$not_sent_to_RED,
				$fields[17],
				$not_sent_to_RED,
				$fields[19],
				$not_sent_to_RED,
				) );
			$report->WriteReportRow( array(
				'Renewal',
				$fields[14],
				$not_sent_to_RED,
				$fields[16],
				$not_sent_to_RED,
				$fields[18],
				$not_sent_to_RED,
				$fields[20],
				$not_sent_to_RED,
			) );
			$report->WriteReportRow( array(
				'Market Penetration as of ' . FormatDate($fields[12]),
				FormatPercentage($fields[45],1),
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			) );
			$report->WriteReportRow( array(
				'Market Penetration Category ' . $Category . ' BBBs Total as of ' . FormatDate($fields[12]),
				FormatPercentage($fields[46],1),
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			) );
			$report->WriteReportRow( array(
				'Market Penetration Category ' . $Category . ' Highest BBB as of ' . FormatDate($fields[12]),
				FormatPercentage($fields[47],1),
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			) );
			$report->WriteReportRow( array(
				'Market Penetration Category ' . $Category . ' Lowest BBB as of ' . FormatDate($fields[12]),
				FormatPercentage($fields[48],1),
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			) );
			$report->WriteReportRow( array(
				/*'ABs 1/'*/ 'ABs ' . $iMonthTo . '/' . $fields[51],
				$fields[49],
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			) );
			$report->WriteReportRow( array(
				/*'ABs 1/'*/ 'ABs ' . $iMonthTo . '/' . $fields[52],
				$fields[50],
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			) );
			$report->WriteReportRow( array(
				'Change in ABs ' . $fields[52] . ' - ' . $fields[51],
				($fields[49] - $fields[50]),
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			) );
			$perc = ($fields[49] - $fields[50]) / $fields[49];
			if ($fields[49] == 0) $perc = 0;
			$report->WriteReportRow( array(
					'Change in ABs ' . $fields[52] . ' - ' . $fields[51],
					FormatPercentage($perc, 0),
					'',
					'',
					'',
					'',
					'',
					'',
					'',
			) );
		}
	}
	$report->Close('suppress_records_message');

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('Evaluation Metrics', '', '', 'left'),
				array("{$iMonthFrom}/{$iYearFrom}-{$iMonthTo}/{$iYearTo}", '', '', 'left'),
				array("1/{$iYearTo}-{$iMonthTo}/{$iYearTo}", '', '', 'left'),
				array("{$iMonthFrom}/{$iPrevYearFrom}-{$iMonthTo}/{$iPrevYearTo}", '', '', 'left'),
				array("1/{$iPrevYearTo}-{$iMonthTo}/{$iPrevYearTo}", '', '', 'left'),
				array($SETTINGS['ORG_NAME'] . ' Standard', '', '', 'left'),
				array('Category ' . $Category . ' BBBs Avg', '', '', 'left')
			)
		);
		foreach ($rs as $k => $fields) {
			/*$report->WriteReportRow( array( 'BBB city', AddApost($fields[0]),
				'', '' ) );*/
			/*$report->WriteReportRow( array( 'Establishments in service area', AddComma($fields[1]) . ' establishments',
				'', '' ) );*/
			$report->WriteReportRow( array(
				'Resolution rate',
				FormatPercentage($fields[3], 1) . ' resolved',
				FormatPercentage($fields[21], 1) . ' resolved',
				FormatPercentage($fields[22], 1) . ' resolved',
				FormatPercentage($fields[23], 1) . ' resolved',
				'66% or more',
				FormatPercentage($fields[39], 1) . ' resolved',
				/*FormatBit($fields[3] >= 0.66)*/
				) );
			$report->WriteReportRow( array(
				'Not reportable rate',
				FormatPercentage($fields[4], 1) . ' not reported',
				FormatPercentage($fields[59], 1) . ' not reported',
				FormatPercentage($fields[60], 1) . ' not reported',
				FormatPercentage($fields[61], 1) . ' not reported',
				'33% or less',
				'----'
				) );
			$report->WriteReportRow( array(
				'Days to close',
				round($fields[6],1) . ' days',
				round($fields[27],1) . ' days',
				round($fields[28],1) . ' days',
				round($fields[29],1) . ' days',
				'30 or less',
				round($fields[41],1) . ' days',
				/*FormatBit($fields[6] <= 30)*/ ) );
			$report->WriteReportRow( array(
				'Days to open',
				round($fields[5],1) . ' days',
				round($fields[30],1) . ' days',
				round($fields[31],1) . ' days',
				round($fields[32],1) . ' days',
				'2 or less',
				round($fields[42],1) . ' days',
				/*FormatBit($fields[5] <= 2)*/ ) );
			$report->WriteReportRow( array(
				'Ad review',
				$fields[7] . ' points',
				$fields[33] . ' points',
				$fields[34] . ' points',
				$fields[35] . ' points',
				round($fields[8],1) . ' or more',
				$fields[43] . ' points',
				/*FormatBit($fields[7] >= $fields[8])*/ ) );
			$report->WriteReportRow( array(
				'Investigations',
				$fields[9] . ' points',
				$fields[24] . ' points',
				$fields[25] . ' points',
				$fields[26] . ' points',
				round($fields[10],1) . ' or more',
				$fields[40] . ' points',
				/*FormatBit($fields[9] >= $fields[10])*/ ) );
			$report->WriteReportRow( array(
				'Retention rate',
				FormatPercentage($fields[2], 1) . ' retained ' .
					FormatDate($fields[11]) . ' - ' . FormatDate($fields[12]),
				'(N/A)',
				FormatPercentage($fields[36], 1) . ' retained ' .
					FormatDate($fields[37]) . ' - ' . FormatDate($fields[38]),
				'(N/A)',
				'70% or more',
				FormatPercentage($fields[44], 1) . ' retained ' .
					FormatDate($fields[11]) . ' - ' . FormatDate($fields[12]),
				/*FormatBit($fields[2] >= 0.70)*/ ) );
			/*
			$report->WriteReportRow( array( 'Inquiry rate',
				round($fields[13], 1) . ' inquiries per establishment in area',
				'(no requirement)', '-' ) );
			*/
		}
	}
	$report->Close('suppress_records_message');


	$report = new report( $conn, count($rs) );
	$report->Open();
	$report->WriteHeaderRow(
		array (
			array('Operations', '', '', 'left'),
			array("{$iMonthFrom}/{$iYearFrom}-{$iMonthTo}/{$iYearTo}", '', '', 'right'),
		)
	);
	foreach ($rs as $k => $fields) {
		$report->WriteReportRow( array(
			'Inquiries',
			$fields[54],
		) );
		$report->WriteReportRow( array(
			'Complaints',
			$fields[55],
		) );
		$report->WriteReportRow( array(
			'Mediations',
			$fields[56],
		) );
		$report->WriteReportRow( array(
			'Arbitrations',
			$fields[57],
		) );
		$report->WriteReportRow( array(
			'Dropped ABs',
			$fields[58],
		) );
		$report->WriteReportRow( array(
			'Denied ABs',
			$not_sent_to_RED,
		) );
	}
	$report->Close('suppress_records_message');


	$report = new report( $conn, count($rs) );
	$report->Open();
	$report->WriteHeaderRow(
		array (
			array('Outreach', '', '', 'left'),
			array('Month/Quarter', '', '', 'left'),
			array('YTD', '', '', 'left'),
			array('Prev Yr', '', '', 'left'),
			array('Prev Yr YTD', '', '', 'left'),
		)
	);
	$report->WriteReportRow( array( 'Media', $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED ) );
	$report->WriteReportRow( array( 'Web Unique Users', $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED ) );
	$report->WriteReportRow( array( 'Presentations', $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED ) );
	$report->WriteReportRow( array( 'Social Media', $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED, $not_sent_to_RED ) );
	$report->Close('suppress_records_message');

	/*
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
	*/
}

$page->write_pagebottom();

?>