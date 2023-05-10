<?php

/*
 * 08/07/17 MJS - new file
 * 08/09/17 MJS - fixed y-axis max
 * 06/15/18 MJS - removed facebook
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = $_REQUEST['iBBBID'];
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
//$iPeriod = NoApost($_REQUEST['iPeriod']);
$iSalesCategory = TrimTrailingComma(NoApost($_POST['iSalesCategory']));
$iShowSource = $_POST['iShowSource'];

if (! $_POST && $iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
if (! $iPeriod) $iPeriod = 'months';

$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddDateField('iDateFrom','Date range',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
//$input_form->AddRadio('iPeriod', 'Period', $iPeriod, array( 'Months' => 'months', 'Years' => 'years', ) );
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		select
			/*SUM(s.CountOfFacebookLikes),*/
			SUM(s.CountOfTwitterFollowers),
			SUM(s.CountOfYouTubeViews),
			(CONVERT(varchar(7), CAST( [MonthNumber] as VARCHAR) + '/' + CAST( [Year] AS VARCHAR) )) AS MonthYearText
		from BBBSocialMediaStats s WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = s.BBBID AND BBB.BBBBranchID = 0 /*AND BBB.IsActive = '1'*/
		where
			('{$iBBBID}' = '' or s.BBBID = '{$iBBBID}') and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' + CAST( s.[Year] AS VARCHAR) ) >= '{$iDateFrom}' and
			CONVERT(datetime, CAST( s.MonthNumber as VARCHAR) + '/1/' + CAST( s.[Year] AS VARCHAR) ) <= '{$iDateTo}'
		GROUP BY s.[Year], s.[MonthNumber]
		ORDER BY s.[Year], s.[MonthNumber]
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	
	if (count($rs) > 0 && $output_type == "") {
		$report = new report( $conn, count($rs) );
	
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[0] /*max($fields[0], $fields[1])*/ ;
		}

		$barchart = new barchart($vals, 'framed');
		$barchart->bar_width = 27;
		$barchart->offset_factor = 90;
		$barchart->Open('suppress_average');

		/*
		$barchart->bar_color = '#6F9F39';
		$barchart->cap_color = '#87B157';
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[0], '');
		}
		*/

		$barchart->bar_color = '#BF2D19';
		$barchart->cap_color = '#9D2B1D';
		$xcount = 0.30;
		foreach ($rs as $k => $fields) {
			$xcount++;
			//$barchart->DrawBar($xcount, $fields[1], $fields[3]);
			$barchart->DrawBar($xcount, $fields[0], $fields[2]);
		}

		/*
		$barchart->bar_color = '#635F5B';
		$barchart->cap_color = '#E3DFDB';
		$xcount = 0.60;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[2], '');
		}
		*/

		$barchart->DrawTitle('Social Media Over Time');
		//$barchart->DrawTrendLine($vals);
		$barchart->legend_offset = 95;
		//$barchart->DrawLegendItem(1, '#635F5B', 'YouTube Views');
		$barchart->DrawLegendItem(2, '#BF2D19', 'Twitter Followers');
		/*$barchart->DrawLegendItem(3, '#6F9F39', 'Facebook Likes');*/
		$barchart->Close();
	}
	else if (count($rs) > 0 && $output_type > "") {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				/*array('Facebook Likes', ''),*/
				array('Twitter Followers', ''),
				array('YouTube Views', ''),
				array('Month/Year', ''),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					$fields[2],
					//$fields[3],
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
}
	
$page->write_pagebottom();

?>