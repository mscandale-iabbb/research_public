<?php

/*
 * 08/19/15 MJS - new file
 * 08/25/15 MJS - added totals for all BBBs
 * 08/26/15 MJS - removed totals for all BBBs - ran too slowly
 * 12/15/15 MJS - ensured Scam Tracker records won't appear
 * 12/08/16 MJS - changed REQUEST to POST
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


$iBBBID = Numeric2($_POST['iBBBID']);
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
else if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
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
$iShowSource = $_POST['iShowSource'];

$DateFrom = date( 'n/j/Y', strtotime('-1 day', strtotime( date('n') . '/1/' . date('Y') )) );
$DateTo = date( 'n/j/Y', strtotime('-1 day', strtotime( date('n') . '/1/' . date('Y') )) );

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

		declare @StatYear smallint = YEAR(@DateTo);
		declare @EstabsYear smallint =
			(select top 1 [Year] from BBBFinancials WITH (NOLOCK) ORDER BY [Year] DESC);

		declare @firms int;
		set @firms = (select EstabsInArea from BBBFinancials f WITH (NOLOCK) where
			f.BBBID = '{$iBBBID}' and f.BBBBranchID = 0 and f.[Year] = @EstabsYear);

		declare @firmsALL int;
		set @firmsALL = (select SUM(EstabsInArea) from BBBFinancials f WITH (NOLOCK) where
			f.BBBBranchID = 0 and f.[Year] = @EstabsYear);

		SELECT
			BBB.NicknameCity + ', ' + BBB.State,
			@firms as Firms,
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
        			SELECT
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
                        				CloseCode = '600' and
                        				c.ComplaintID not like 'scam%'
                				)
        				as decimal(14,2) )
        				/
        				CAST(
                				(SELECT count(*) from BusinessComplaint c WITH (NOLOCK) WHERE
                        				c.BBBID = BBB.BBBID AND
                        				DateClosed >= @datefrom AND DateClosed <= @dateto AND
										CloseCode <= '999' and
										c.ComplaintID not like 'scam%'
                				)
        				as decimal (14,2) )
			) as Processed600,
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
			( SELECT
				(
					CAST(
							(SELECT SUM(CountTotal) from BusinessInquiry i WITH (NOLOCK) WHERE
									i.BBBID = BBB.BBBID AND
									i.DateOfInquiry >= @datefrom AND i.DateOfInquiry <= @dateto
							)
					as decimal (14,2) )
					/
					@firms
				)
			)
			as InquiryRate
		FROM BBB BBB WITH (NOLOCK)
		WHERE
			BBB.BBBID = '{$iBBBID}' and BBB.BBBBranchID = 0
		";

	$rsraw = $conn->execute("$query");
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('Criteria', ''),
				array('Value', ''),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow( array(
				'BBB city',
				AddApost($fields[0]),
			) );
			$report->WriteReportRow( array(
				'Percentage of resolved complaints',
				FormatPercentage($fields[2], 1) . ' resolved',
			) ); 
			$report->WriteReportRow( array(
				'Percentage of complaints closed as 600',
				FormatPercentage($fields[3], 1) . ' ',
			) );
			$report->WriteReportRow( array(
				'Days to open',
				round($fields[4],1) . ' days',
			) );
			$report->WriteReportRow( array(
				'Days to close',
				round($fields[5],1) . ' days',
			) );
			$report->WriteReportRow( array(
				'Ratio of business Profiles accessed per establishments in service area',
				round($fields[6], 1) . ' inquiries',
			) );
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>