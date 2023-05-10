<?php

/*
 * NOTE: Whenever new Census data for establishments is loaded (probably circa August 2016), this report will no longer be completely backward-compatible.
 * 
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 01/27/15 MJS - added logic to "blend" years
 * 03/13/15 MJS - added close code 150
 * 06/16/15 MJS - added disclaimer language
 * 08/25/15 MJS - changed calculation for establishments for 2016 round - use latest Census data and no blending 
 * 12/15/15 MJS - ensured Scam Tracker records won't appear
 * 08/25/15 MJS - align column headers
 * 11/01/16 MJS - added logic for latest Census data
 * 12/08/16 MJS - changed REQUEST to POST
 * 09/07/17 MJS - added option for All BBBs for CBBB, changed format to tabular for All
 * 09/07/17 MJS - changed "not processed" to "not reportable"
 * 03/15/18 MJS - added points for ad reviews and investigations
 * 04/11/18 MJS - fixed Passing fields to use ad review and invest points
 * 04/12/18 MJS - fixed bug with calculating ad review points
 * 10/26/18 MJS - added column for % unreviewed scam matches
 * 11/01/18 MJS - added column for AB growth
 * 12/06/18 MJS - added AB growth column for ALL option
 * 12/21/18 MJS - removed AB growth column
 * 02/01/19 MJS - changed format of unreviewed scam tracker matches
 * 03/04/19 MJS - added average days to process scam tracker
 * 03/14/19 MJS - corrected tags for BBBAssigned and DaysToProcess
 * 03/22/19 MJS - fixed bug with days to process scam tracker
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBID = Numeric2($_POST['iBBBID']);
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
//else if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
if ($iMonthTo == 0) {
	$iMonthTo = 12;
	$iYearTo--;
	$iMonthFrom = $iMonthTo;
	$iYearFrom = $iYearTo;
}
$iYearThen = $iYearTo - 2;
$iSortBy = $_POST['iSortBy'];
$iShowSource = $_POST['iShowSource'];

$DateFrom = date( 'n/j/Y', strtotime('-1 day', strtotime( date('n') . '/1/' . date('Y') )) );
$DateTo = date( 'n/j/Y', strtotime('-1 day', strtotime( date('n') . '/1/' . date('Y') )) );

$input_form = new input_form($conn);
$whichbbbs = 'yoursonly';
if ($BBBID == '2000') {
	$whichbbbs = 'all';
}
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray($whichbbbs) );
$input_form->AddTextField('iMonthFrom', 'Months', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Firms' => 'Firms',
	'Retention rate' => 'RetentionRate',
	'Resolution rate' => 'ResolutionRate',
	'Not reportable rate' => 'NotReportableRate',
	'Days to open' => 'DaysToOpen',
	'Days to close' => 'DaysToClose',
	'Ad review' => 'AdReview',
	'Ad review required' => 'AdReviewRequired',
	'Investigations' => 'Investigations',
	'Investigations required' => 'InvestigationsRequired'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

function build_query($iBBBID) {
	global $iMonthFrom, $iYearFrom, $iMonthTo, $iYearTo, $iYearThen, $iSortBy;

	$query = "
		declare @xmonthfrom int;
		declare @xyearfrom int;
		declare @xmonthto int;
		declare @xyearto int;
		declare @datefrom date;
		declare @dateto date;
		declare @dayrange decimal(14,2);
		declare @factor decimal(14,2);
		
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

		set @dayrange = DATEDIFF(day, @datefrom, @dateto);
		set @factor = (@dayrange / 365.00);

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

		SELECT
			BBB.NicknameCity + ', ' + BBB.State,
			f.EstabsInArea as Firms,
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
        				CAST(
                				(SELECT CountOfABs from SnapshotStats s WITH (NOLOCK) WHERE
                        				s.BBBID = BBB.BBBID AND
							CountOfABS is not NULL and
							CONVERT(datetime,
								cast(s.MonthNumber as varchar(2)) + '/1/' +
								cast(s.[Year] as varchar(4))
							) = @retentiondatefrom
                				)
					as decimal (14,2) )
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
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
										CloseCode IN ('110','111','112','120','121','122','150','200') and
										c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) )
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
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
                        				CloseCode IN ('110','111','112','120','121','122','150','200','300','500','600','999') and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) )
        			)
			) as NotReportableRate,
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
				SELECT COUNT(*) from BusinessAdReview a WITH (NOLOCK) WHERE
					a.BBBID = BBB.BBBID AND
					a.DateClosed >= @datefrom AND a.DateClosed <= @dateto
			) as AdReviewTotal,
			(
        			SELECT cast(ROUND(.00075 * f.EstabsInArea,0) as int)
			) * @factor as AdReviewRequired,
			(
				SELECT COUNT(*) from BusinessInvestigation i WITH (NOLOCK) WHERE
					i.BBBID = BBB.BBBID AND
					i.DateOfInvestigation >= @datefrom AND i.DateOfInvestigation <= @dateto
			) as InvestigationsTotal,
			(
					SELECT cast(ROUND(.0005 * f.EstabsInArea,0) as int)
			) * @factor as InvestigationsRequired,
			@retentiondatefrom as RetentionRateDateFrom,
			@dateto as RetentionRateDateTo,
			BBB.BBBID,
			(
				SELECT COUNT(*) from BusinessAdReview a WITH (NOLOCK) WHERE
					a.BBBID = BBB.BBBID AND
					a.DateClosed >= @datefrom AND a.DateClosed <= @dateto and
					(a.AdTier = '1' or a.AdTier is null or a.AdTier = '' or a.AdTier = '0')
			) as AdReview1,
			(
				SELECT COUNT(*) from BusinessAdReview a WITH (NOLOCK) WHERE
					a.BBBID = BBB.BBBID AND
					a.DateClosed >= @datefrom AND a.DateClosed <= @dateto and
					a.AdTier = '2'
			) as AdReview2,
			(
				SELECT COUNT(*) from BusinessAdReview a WITH (NOLOCK) WHERE
					a.BBBID = BBB.BBBID AND
					a.DateClosed >= @datefrom AND a.DateClosed <= @dateto and
					a.AdTier = '3'
			) as AdReview3,
			(
				SELECT COUNT(*) from BusinessInvestigation i WITH (NOLOCK) WHERE
					i.BBBID = BBB.BBBID AND
					i.DateOfInvestigation >= @datefrom AND i.DateOfInvestigation <= @dateto and
					(i.InvestTier = '1' or i.InvestTier is null or i.InvestTier = '' or i.InvestTier = '0')
			) as Investigations1,
			(
				SELECT COUNT(*) from BusinessInvestigation i WITH (NOLOCK) WHERE
					i.BBBID = BBB.BBBID AND
					i.DateOfInvestigation >= @datefrom AND i.DateOfInvestigation <= @dateto and
					i.InvestTier = '2'
			) as Investigations2,
			(
				SELECT COUNT(*) from BusinessInvestigation i WITH (NOLOCK) WHERE
					i.BBBID = BBB.BBBID AND
					i.DateOfInvestigation >= @datefrom AND i.DateOfInvestigation <= @dateto and
					i.InvestTier = '3'
			) as Investigations3,
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and ch.ComplaintID is null and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as UnreviewedMatches,
			/*
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as TotalMatches,
			*/
			(
				select
					AVG(
						cast(
							SUBSTRING(
								SUBSTRING(t.DesiredOutcome, CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome) + LEN('DaysToProcess\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome)),
								1,
								CHARINDEX('\"',
									SUBSTRING(t.DesiredOutcome, CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome) + LEN('DaysToProcess\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome)))
									- 1
							)
						as decimal(20,4))
					) as AvgDays
				from BusinessComplaint c WITH (NOLOCK)
				left outer join BusinessComplaintText t WITH (NOLOCK) on c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
				where
					c.ComplaintID like 'scam%' and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					SUBSTRING(
						SUBSTRING(t.DesiredOutcome, CHARINDEX('BBBAssigned\": \"',t.DesiredOutcome) + LEN('BBBAssigned\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('BBBAssigned\": \"',t.DesiredOutcome)),
						1,
						CHARINDEX('\"',
							SUBSTRING(t.DesiredOutcome, CHARINDEX('BBBAssigned\": \"',t.DesiredOutcome) + LEN('BBBAssigned\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('BBBAssigned\": \"',t.DesiredOutcome)))
							- 1
					) = BBB.BBBID and
					SUBSTRING(
						SUBSTRING(t.DesiredOutcome, CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome) + LEN('DaysToProcess\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome)),
						1,
						CHARINDEX('\"',
							SUBSTRING(t.DesiredOutcome, CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome) + LEN('DaysToProcess\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome)))
							- 1
					) > '' and
					SUBSTRING(
						SUBSTRING(t.DesiredOutcome, CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome) + LEN('DaysToProcess\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome)),
						1,
						CHARINDEX('\"',
							SUBSTRING(t.DesiredOutcome, CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome) + LEN('DaysToProcess\": \"'), LEN(t.DesiredOutcome) - CHARINDEX('DaysToProcess\": \"',t.DesiredOutcome)))
							- 1
					) not like '%ictim%'
			) as AvgDaysCloseScams
			/*
			(
				SELECT CountOfABs from MiscStats m where
					m.BBBID = BBB.BBBID and m.[Year] = '{$iYearThen}' and m.MonthNumber = '1'
			) as ABsThen,
			(
				SELECT CountOfABs from MiscStats m where
					m.BBBID = BBB.BBBID and m.[Year] = '{$iYearTo}' and m.MonthNumber = '{$iMonthTo}'
			) as ABsNow
			*/
		FROM BBB BBB WITH (NOLOCK)
		INNER JOIN BBBFinancials f WITH (NOLOCK) ON
			f.BBBID = BBB.BBBID and f.BBBBranchID = 0 and
			(select count(*) from BBBFinancials f2 WITH (NOLOCK) WHERE
				f2.BBBID = f.BBBID and f2.BBBBranchID = f.BBBBranchID and
				f2.[Year] > f.[Year]) = 0
		WHERE
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			BBB.BBBBranchID = 0 and BBB.IsActive = '1'
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}
	return $query;
}

function write_report_rows($rs) {
	global $report, $iBBBID, $iMonthTo, $iYearTo, $iYearThen;

	foreach ($rs as $k => $fields) {
		$oAdReviewsTier1 = $fields[14];
		$oAdReviewsTier2 = $fields[15];
		$oAdReviewsTier3 = $fields[16];
		$oAdReviews = ($oAdReviewsTier1 * 1) + ($oAdReviewsTier2 * 2) + ($oAdReviewsTier3 * 3);

		$oInvestigationsTier1 = $fields[17];
		$oInvestigationsTier2 = $fields[18];
		$oInvestigationsTier3 = $fields[19];
		$oInvestigations = ($oInvestigationsTier1 * 1) + ($oInvestigationsTier2 * 2) + ($oInvestigationsTier3 * 3);

		if (! $fields[21]) {
			$fields[21] = "?";
		}
		else {
			$fields[21] = round($fields[21],1);
		}

		if ($iBBBID == '') { // all bbbs
			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[13]}>" . AddApost($fields[0]) . "</a>",
					$fields[1],
					FormatPercentage($fields[2],1),
					FormatPercentage($fields[3],1),
					FormatPercentage($fields[4],1),
					round($fields[5],1),
					round($fields[6],1),
					round($fields[7],1),
					$oAdReviews,
					round($fields[8],1),
					round($fields[9],1),
					$oInvestigations,
					round($fields[10],1),
					/*FormatPercentage($fields[20] / $fields[21], 0),*/
					$fields[20],
					$fields[21]
					/*AddComma($fields[22]) . " on 1/{$iYearThen} and " . AddComma($fields[23]) . " on {$iMonthTo}/{$iYearTo}",*/
				),
				''
			);
		}
		else { // one bbb
			$report->WriteReportRow( array( 'BBB city', AddApost($fields[0]),
					'', '' ) );
			$report->WriteReportRow( array( 'Establishments in service area', AddComma($fields[1]) . ' establishments',
					'', '' ) );
			$report->WriteReportRow( array( 'Resolution rate', FormatPercentage($fields[3], 1) . ' resolved',
					'66% or more', FormatBit($fields[3] >= 0.66) ) );
			$report->WriteReportRow( array( 'Not reportable rate', FormatPercentage($fields[4], 1) . ' not reported',
					'33% or less', FormatBit($fields[4] <= 0.33) ) );
			$report->WriteReportRow( array( 'Days to open', round($fields[5],1) . ' days',
					'2 or less', FormatBit($fields[5] <= 2) ) );
			$report->WriteReportRow( array( 'Days to close', round($fields[6],1) . ' days',
					'30 or less', FormatBit($fields[6] <= 30) ) );
			$report->WriteReportRow( array( 'Ad review', $fields[7] . ' cases ' . " ({$oAdReviews} points)",
					round($fields[8],1) . ' or more points', FormatBit($oAdReviews >= $fields[8]) ) );
			$report->WriteReportRow( array( 'Investigations', $fields[9] . ' cases' . " ({$oInvestigations} points)",
					round($fields[10],1) . ' or more points', FormatBit($oInvestigations >= $fields[10]) ) );
			$report->WriteReportRow( array( 'Retention rate',
					FormatPercentage($fields[2], 1) . ' retained ' .
					FormatDate($fields[11]) . ' - ' . FormatDate($fields[12]),
					'70% or more', FormatBit($fields[2] >= 0.70) ) );
			/* $report->WriteReportRow( array( 'Unreviewed Scam Tracker matches', FormatPercentage($fields[20] / $fields[21], 0) . ' unreviewed',
					'0% required', FormatBit($fields[20] / $fields[21] == 0.00) ) ); */
			$report->WriteReportRow( array( 'Unreviewed Scam Tracker matches', $fields[20] . ' unreviewed',
					'0 required', FormatBit($fields[20] == 0) ) );
			$report->WriteReportRow( array( 'Average days to process Scam Tracker', $fields[21] . ' days',
					'2 or less', FormatBit($fields[21] <= 2) ) );
			/*
			$report->WriteReportRow( array( 'AB growth',
					AddComma($fields[22]) . " on 1/{$iYearThen} and " . AddComma($fields[23]) . " on {$iMonthTo}/{$iYearTo}",
					'+1 required', FormatBit($fields[23] > $fields[22]) ) );
			*/
		}
	}
}

if ($_POST) {
	$query = build_query($iBBBID);
	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		if ($iBBBID != '') { // one bbbs
			$report->WriteHeaderRow(
				array (
					array('Metric', '', '', 'left'),
					array('Value', '', '', 'left'),
					array('Requirement', '', '', 'left'),
					array('Passing', '', '', 'left')
				)
			);
		}
		else { // all bbbs
			$report->WriteHeaderRow(
				array (
					array('BBB City', $SortFields['BBB city'], '', 'left'),
					array('Firms', $SortFields['Firms'], '', 'right'),
					array('Ret Rate', $SortFields['Retention rate'], '', 'right'),
					array('Res Rate', $SortFields['Resolution rate'], '', 'right'),
					array('Not Rpt Rate', $SortFields['Not reportable rate'], '', 'right'),
					array('Days to Open', $SortFields['Days to open'], '', 'right'),
					array('Days to Close', $SortFields['Days to close'], '', 'right'),
					array('Ad Rev', $SortFields['Ad review'], '', 'right'),
					array('Ad Rev Pts', '', '', 'right'),
					array('Ad Rev Req', $SortFields['Ad review required'], '', 'right'),
					array('Invest', $SortFields['Investigations'], '', 'right'),
					array('Invest Pts', '', '', 'right'),
					array('Invest Req', $SortFields['Investigations required'], '', 'right'),
					array('Scam Unrev', '', '', 'right'),
					array('Scam Proc', '', '', 'right'),
					/*array('AB Growth', '', '', 'left'),*/
				)
			);
		}
		write_report_rows($rs);
	}	
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

?>