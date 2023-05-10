<?php

/*
 * 02/09/17 MJS - new file
 * 03/20/17 MJS - fixed bug with link to BBB detail
 * 03/20/17 MJS - added links to more detail reports
 * 11/21/17 MJS - rewrote into graph
 * 11/21/17 MJS - removed payments of type IP
 * 11/22/17 MJS - added second graph
 * 11/22/17 MJS - fixed graph height
 * 11/29/17 MJS - fixed partial payment field (not used yet)
 * 01/30/18 MJS - refactored for APICore
 * 02/06/18 MJS - fixed bug in APICore query
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = NoApost($_REQUEST['iBBBID']);
if (! $_POST && $iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',date('n') - 1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
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
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
if ($iMonthTo == 0) {
	$iMonthTo = 12;
	$iYearTo--;
	$iMonthFrom = $iMonthTo;
	$iYearFrom = $iYearTo;
}
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iMonthFrom', 'Months', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_BusinessPayment = "CORE.dbo.datOrgBusinessPayment";
		$column_BBBID = "BBBID";
	}
	else {
		$table_BusinessPayment = "APICore.dbo.BusinessPayment";
		$column_BBBID = "BureauCode";
	}
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
		
		/* subtract a month if running before the 6th day of the month */
		if DAY(GETDATE()) < 6 AND DATEDIFF(day,GETDATE(),@dateto) < 6 SET @dateto = DATEADD(month,-1,@dateto);

		/* don't allow future dates */
		IF @dateto > GETDATE() SET @dateto = GETDATE();

		SELECT
			SUM(cast(p.IsPaidInFull as int)) as Paid,
			COUNT(*) as Billed,
			SUM(case when p.HasPayment = '1' and cast(p.IsPaidInFull as int) = 0 then 1 else 0 end) as Partial, /* NOT USED YET */
			(CONVERT(varchar(7), CAST( MONTH(p.DateOfBilling) as VARCHAR) + '/' + CAST( YEAR(p.DateOfBilling) AS VARCHAR) )) AS MonthYearText
		from {$table_BusinessPayment} p WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = p.{$column_BBBID} and BBB.BBBBranchID = '0' and BBB.IsActive = '1'
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		where
			p.DateOfBilling >= @datefrom and p.DateOfBilling <= @dateto and
			p.PlanType != 'IP' and /* ??? */
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			('{$iRegion}' = '' or BBB.Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or BBB.SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or BBB.State IN ('" . str_replace(",", "','", $iState) . "'))
		GROUP BY YEAR(p.DateOfBilling), MONTH(p.DateOfBilling)
		ORDER BY YEAR(p.DateOfBilling), MONTH(p.DateOfBilling)
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();

	if (count($rs) > 0 && $output_type == "") {
		$report = new report( $conn, count($rs) );
	
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}

		// barchart 1 of 2

		$barchart = new barchart($vals, 'framed');
		$barchart->bar_width = 19;
		$barchart->offset_factor = 60;
		$barchart->Open('suppress_average');
	
		$barchart->bar_color = '#6F9F39';
		$barchart->cap_color = '#87B157';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[0], '');
		}

		$barchart->bar_color = '#FFFFFF';
		//$barchart->cap_color = '#AAAAAA';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($iBBBID) {
				$tmp = explode('/',$fields[3]);
				$xmonth = $tmp[0];
				$xyear = $tmp[1];
				$label = "<a target=_new href=red_BBBs_Tracking_Details.php?" .
						"iBBBID={$iBBBID}&iMonthTo={$xmonth}&iYearTo={$xyear}" .
						">{$fields[3]}</a>"; // &uArr;
			}
			else {
				$label = $fields[3];
			}
			$tmpval = round($fields[0] / $fields[1], 2);
			$tmpval = sprintf("%.2f", $tmpval);
			$tmpstr = "&nbsp; " . substr($tmpval,2) . "%";
			$barchart->DrawBar($xcount, $tmpstr, $label);
		}

		$barchart->bar_color = '#BF2D19';
		$barchart->cap_color = '#9D2B1D';
		$xcount = 0.30;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[1], '');
		}

		$barchart->bar_color = '#FFFFFF';
		//$barchart->cap_color = '#9D2B1D';
		$xcount = 0.30;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, '&nbsp;', '');
		}

		$barchart->DrawTitle('BBB Payments Tracking');
		$barchart->legend_offset = 60;
		$barchart->DrawLegendItem(1, '#BF2D19', 'Billed');
		$barchart->DrawLegendItem(2, '#6F9F39', 'Paid');
		$barchart->Close();


		// barchart 2 of 2

		$vals = array();

		foreach ($rs as $k => $fields) {
			$vals[] = round($fields[0] / $fields[1], 3) * 100;
		}

		$barchart2 = new barchart($vals, 'framed');
		$barchart2->highest = 100;
		$barchart2->bar_width = 38;
		$barchart2->offset_factor = 60;
		$barchart2->Open('suppress_average');
		
		$barchart2->bar_color = '#6F9F39';
		$barchart2->cap_color = '#87B157';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$tmpval = round($fields[0] / $fields[1], 3) * 100;
			$tmpval = sprintf("%.1f", $tmpval) . "%";
			$barchart2->DrawBar($xcount, $tmpval, $fields[3]);
		}

		$barchart2->bar_color = '#FFFFFF';
		//$barchart2->cap_color = '#AAAAAA';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($iBBBID) {
				$tmp = explode('/',$fields[3]);
				$xmonth = $tmp[0];
				$xyear = $tmp[1];
				$label = "<a target=_new href=red_BBBs_Tracking_Details.php?" .
						"iBBBID={$iBBBID}&iMonthTo={$xmonth}&iYearTo={$xyear}" .
						">{$fields[3]}</a>"; // &uArr;
			}
			else {
				$label = $fields[3];
			}
			$tmpval = "&nbsp;" . $fields[0] . "/" . $fields[1];
			$barchart2->DrawBar($xcount, $tmpval, $label);
		}

		$barchart2->DrawTitle('BBB Payments Tracking');
		$barchart2->legend_offset = 60;
		$barchart2->DrawLegendItem(1, '#6F9F39', 'Paid');
		$barchart2->Close();

	}
	else if (count($rs) > 0 && $output_type > "") {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Paid', ''),
				array('Billed', ''),
				array('Month/Year', ''),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					$fields[3],
				)
			);
		}
		$report->Close('suppress');
	}
	else {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteReportRow(array ('No records found'));
		$report->Close('suppress');
	}
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>