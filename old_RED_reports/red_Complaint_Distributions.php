<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 03/13/15 MJS - added 150 close code
 * 12/16/15 MJS - ensured Scam Tracker records won't appear
 * 02/10/16 MJS - added section for No Records Found
 * 03/24/16 MJS - added option for TOB
 * 03/25/16 MJS - added option for TOB to all queries
 * 07/26/16 MJS - made sure complaints with blank close codes aren't counted
 * 08/25/16 MJS - align column headers
 * 11/09/17 MJS - changed sector to naics
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iTOBCode = NoApost($_REQUEST['iTOBCode']);
$iCountry = $_POST['iCountry'];
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iTOBCode', 'TOB code', $iTOBCode, $input_form->BuildTOBsArray(),
		'width:300px;', '', '', 'width:300px;');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	// //////////////////////////////////////////////////////////////////////////////////
	// Complaints by Industry
	// //////////////////////////////////////////////////////////////////////////////////

	$query = "
		declare @levels int;
		set @levels = 2;
		SELECT			
			n.naics_description,
			COUNT(*)
		from BusinessComplaint c WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = c.BBBID and BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			(BBB.BBBID = '{$iBBBID}' or '{$iBBBID}' = '') and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			c.CloseCode IN ('110','111','112','120','121','122','150','200','300') and
			c.ClassificationID1 IN ('1','2','3','4','5','6','7','8','9','10','11') and
			('{$iTOBCode}' = '' or c.BusinessTOBID IN ('" . str_replace(",", "','", $iTOBCode) . "')) and
			(BBB.Country = '{$iCountry}' or '{$iCountry}' = '') and
			c.ComplaintID not like 'scam%'
		group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
		order by n.naics_description
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();

	// tabular report
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Industry', '', '', 'left'),
				array('Reportable Complaints', '', '', 'right'),
				array('%', '', '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					FormatPercentage(
						$fields[1] / array_sum( get_array_column($rs, 1) ),
						0),
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Total',
				array_sum( get_array_column($rs, 1) ),
				'',
			)
		);
		if ($iShowSource > '') {
			$report->WriteSource($query);
		}
		$report->Close('suppress');
	}

	reset($rs);

	// bar chart

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
	
		$barchart = new barchart($vals);
		$barchart->gridline_indent = 40;
		$barchart->label_position = 9;
		$barchart->offset_factor = 78;
		$barchart->bar_width = 50;
		$barchart->Open();
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$fields[0] = GetFirstWord($fields[0]);
			$barchart->DrawBar($xcount, $fields[1], $fields[0]);
		}
		$barchart->DrawTitle('Complaints by Industry');
		$barchart->Close();
	}

	reset($rs);
	$vals = null;

	// pie chart

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}

		$totalval = array_sum($vals);

		$piechart = new piechart();
		$piechart->Open();

		$piechart->position = 0;
		foreach ($rs as $k => $fields) {
			$fields[0] = GetFirstWord($fields[0]);
			$newposition = $piechart->position + ($fields[1] / $totalval);
			if ( ($fields[1] / $totalval) >= 0.01) {
				$piechart->DrawSlice($piechart->position, $newposition,
					$fields[0] . ' ' . AddComma($fields[1])
				);
			}
			$piechart->position = $newposition;
		}
		$piechart->DrawTitle('Complaints by Industry');
		$piechart->Close();
	}


	// //////////////////////////////////////////////////////////////////////////////////
	// Complaints by Classification
	// //////////////////////////////////////////////////////////////////////////////////

	reset($rs);
	$vals = null;

	$query = "
		SELECT
			CAST(c.ClassificationID1 as varchar) + ' ' +
				SUBSTRING(cl.ClassificationDescription, 1,
					CHARINDEX(' ', cl.ClassificationDescription)
				)
				as Classification,
			COUNT(*)
		from BusinessComplaint c WITH (NOLOCK)
		inner join tblClassification cl WITH (NOLOCK) on
			c.ClassificationID1 = cl.ClassificationCode
		inner join BBB WITH (NOLOCK) on BBB.BBBID = c.BBBID AND
			BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			c.DateClosed >= '{$iDateFrom}' and
			c.DateClosed <= '{$iDateTo}' and
			(BBB.BBBID = '{$iBBBID}' or '{$iBBBID}' = '') and
			CloseCode IN ('110','111','112','120','121','122','150','200','300') and
			c.ClassificationID1 IN ('1','2','3','4','5','6','7','8','9','10','11') and
			('{$iTOBCode}' = '' or c.BusinessTOBID IN ('" . str_replace(",", "','", $iTOBCode) . "')) and
			(BBB.Country = '{$iCountry}' or '{$iCountry}' = '') and
			c.ComplaintID not like 'scam%'
		group by c.ClassificationID1, cl.ClassificationDescription
		order by c.ClassificationID1
		";

	$rsraw = $conn->execute($query);

	$rs = $rsraw->GetArray();

	// tabular report
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open('suppress_frame');
		$report->WriteHeaderRow(
			array (
				array('Classification', '', '', 'left'),
				array('Reportable Complaints', '', '', 'right'),
				array('%', '', '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					FormatPercentage(
						$fields[1] / array_sum( get_array_column($rs, 1) ),
						0),
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Total',
				array_sum( get_array_column($rs, 1) ),
				'',
			)
		);
		if ($iShowSource > '') {
			$report->WriteSource($query);
		}
		$report->Close('suppress');
	}

	reset($rs);

	// bar chart

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
	
		$barchart = new barchart($vals);
		$barchart->gridline_indent = 40;
		$barchart->label_position = 9;
		$barchart->offset_factor = 75;
		$barchart->bar_width = 50;
		$barchart->Open();
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[1], $fields[0]);
		}
		$barchart->DrawTitle('Complaints by Classification');
		$barchart->Close();
	}

	reset($rs);
	$vals = null;

	// pie chart

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}

		$totalval = array_sum($vals);

		$piechart = new piechart();
		$piechart->Open();

		$piechart->position = 0;
		foreach ($rs as $k => $fields) {
			$newposition = $piechart->position + ($fields[1] / $totalval);
			if ( ($fields[1] / $totalval) >= 0.01) {
				$piechart->DrawSlice($piechart->position, $newposition,
					$fields[0] . ' ' . AddComma($fields[1])
				);
			}
			$piechart->position = $newposition;
		}
		$piechart->DrawTitle('Complaints by Classification');
		$piechart->Close();
	}


	// //////////////////////////////////////////////////////////////////////////////////
	// Complaints by Close Code
	// //////////////////////////////////////////////////////////////////////////////////

	reset($rs);
	$vals = null;

	$query = "
		SELECT
			c.CloseCode,
			COUNT(*),
			cl.ResolutionCodeDescription
		from BusinessComplaint c WITH (NOLOCK)
		inner join tblResolutionCode cl WITH (NOLOCK) on
			c.CloseCode = cl.ResolutionCodeID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = c.BBBID AND
			BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			c.DateClosed >= '{$iDateFrom}' and
			c.DateClosed <= '{$iDateTo}' and
			(BBB.BBBID = '{$iBBBID}' or '{$iBBBID}' = '') and
			CloseCode IN ('110','111','112','120','121','122','150','200','300') and
			c.ClassificationID1 IN ('1','2','3','4','5','6','7','8','9','10','11') and
			('{$iTOBCode}' = '' or c.BusinessTOBID IN ('" . str_replace(",", "','", $iTOBCode) . "')) and
			(BBB.Country = '{$iCountry}' or '{$iCountry}' = '') and
			c.ComplaintID not like 'scam%'
		group by c.CloseCode, cl.ResolutionCodeDescription
		order by c.CloseCode
		";

	$rsraw = $conn->execute($query);

	$rs = $rsraw->GetArray();

	// tabular report
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open('suppress_frame');
		$report->WriteHeaderRow(
			array (
				array('Close Code', '', '', 'left'),
				array('Complaints', '', '', 'right'),
				array('%', '', '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0] . ' - ' . $fields[2],
					$fields[1],
					FormatPercentage(
						$fields[1] / array_sum( get_array_column($rs, 1) ),
						0),
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Total',
				array_sum( get_array_column($rs, 1) ),
				'',
			)
		);
		if ($iShowSource) {
			$report->WriteSource($query);
		}
		$report->Close('suppress');
	}

	reset($rs);

	// bar chart

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
	
		$barchart = new barchart($vals);
		$barchart->gridline_indent = 40;
		$barchart->label_position = 9;
		$barchart->offset_factor = 72;
		$barchart->bar_width = 50;
		$barchart->Open();
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[1], $fields[0]);
		}
		$barchart->DrawTitle('Complaints by Close Code');
		$barchart->Close();
	}

	reset($rs);
	$vals = null;

	// pie chart

	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}

		$totalval = array_sum($vals);

		$piechart = new piechart();
		$piechart->Open();

		$piechart->position = 0;
		foreach ($rs as $k => $fields) {
			$newposition = $piechart->position + ($fields[1] / $totalval);
			if ( ($fields[1] / $totalval) >= 0.01) {
				$piechart->DrawSlice($piechart->position, $newposition,
					$fields[0] . ': ' . AddComma($fields[1])
				);
			}
			$piechart->position = $newposition;
		}
		$piechart->DrawTitle('Complaints by Close Code');
		$piechart->Close();
	}

	if (count($rs) == 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteReportRow(array ('No records found'));
		$report->Close('suppress');
	}

}

$page->write_pagebottom();

?>