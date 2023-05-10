<?php

/*
 * 11/13/17 MJS - new file
 * 11/15/17 MJS - made fixes
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();


$iBBBID = Numeric2($_GET['iBBBID']);
$iField = NoApost($_GET['iField']);
$iNAICS = NoApost($_GET['iNAICS']);
$iMedian = NoApost($_GET['iMedian']);
$iCount = Numeric2($_GET['iCount']);
$iDateFrom = CleanDate($_GET['iDateFrom']);
$iDateTo = CleanDate($_GET['iDateTo']);
$iState = NoApost($_GET['iState']);
$iCstate = NoApost($_GET['iCstate']);
$iCountry = NoApost($_GET['iCountry']);
$iTitle = NoApost($_GET['iTitle']);

echo "
	<div class='main_section roundedborder'>
	<table class='report_table'>
	";
if ($_GET) {

	$iMedian = intval($iMedian);
	$digits = strlen($iMedian) - 1;
	$factor = pow(10, $digits) / 2;

	// get the median absolute deviation

	$query = "
		select
			cast(replace(replace(c.{$iField},',',''),' ','') as decimal(32,2))
		from BusinessComplaint c WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON y.yppa_code = c.BusinessTOBID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = '0'
		where
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			('{$iBBBID}' = '' or c.BBBID = '{$iBBBID}') and
			c.CloseCode != '400' and
			c.ComplaintID not like 'scam%' and
			ISNUMERIC(replace(c.{$iField},',','')) = 1 and
			cast(replace(replace(c.{$iField},',',''),' ','') as decimal(32,2)) > '0' and
			c.BusinessTOBID != '99999-000' and
			y.naics_code = '{$iNAICS}' and
			('{$iState}' = '' or b.StateProvince IN ('" . str_replace(",", "','", $iState) . "')) and
			('{$iCstate}' = '' or c.ConsumerStateProvince IN ('" . str_replace(",", "','", $iCstate) . "')) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		";
	//die($query);
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		$dists = array();
		foreach ($rs as $k => $fields) {
			$dists[] = abs($fields[0] - $iMedian);
		}
		sort($dists);
		$MAD = intval($dists[ intval(count($rs) / 2) ]);
		$oMAD = "$" . AddComma($MAD);
		$oLower = "$" . AddComma(intval($iMedian - $MAD));
		$oUpper = "$" . AddComma(intval($iMedian + $MAD));
	}

	$query = "
		select
			round(cast(replace(replace(c.{$iField},',',''),' ','') as decimal(32,2)) / {$factor}, 0),
			count(*)
		from BusinessComplaint c WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON y.yppa_code = c.BusinessTOBID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = '0'
		where
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			('{$iBBBID}' = '' or c.BBBID = '{$iBBBID}') and
			c.CloseCode != '400' and
			c.ComplaintID not like 'scam%' and
			ISNUMERIC(replace(c.{$iField},',','')) = 1 and
			cast(replace(replace(c.{$iField},',',''),' ','') as decimal(32,2)) > '0' and
			c.BusinessTOBID != '99999-000' and
			y.naics_code = '{$iNAICS}' and
			('{$iState}' = '' or b.StateProvince IN ('" . str_replace(",", "','", $iState) . "')) and
			('{$iCstate}' = '' or c.ConsumerStateProvince IN ('" . str_replace(",", "','", $iCstate) . "')) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		group by round(cast(replace(replace(c.{$iField},',',''),' ','') as decimal(32,2)) / {$factor}, 0)
		";
	//echo "<pre>{$query}</pre>";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {

		// bar chart

		//$report = new report( $conn, count($rs) );
		//$report->Open();
		$iMedian = "$" . AddComma($iMedian);
		$iCount = AddComma($iCount);
		if ($iState == "") {
			$iState = "All";
		}
		if ($iCstate == "") {
			$iCstate = "All";
		}
		echo "
			<table border=0 width=50%>
			<tr>
				<td width=50%>
				<td>{$iField}
			<tr>
				<td width=50%>Industry
				<td>{$iTitle}
			<tr>
				<td width=50%>Business states
				<td>{$iState}
			<tr>
				<td width=50%>Consumer states
				<td>{$iCstate}
			<tr>
				<td width=50%>Number of complaints
				<td>{$iCount}
			<tr>
				<td width=50%>Median
				<td>{$iMedian}
			<tr>
				<td width=50%>Median absolute deviation
				<td>{$oMAD}
			<tr>
				<td width=50%>Range within one deviation
				<td>{$oLower} to {$oUpper}
			<tr>
				<td>
				<td>
			</table>
			";

		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
		$barchart = new barchart($vals, 'framed');
		$barchart->offset_factor = 55;
		$barchart->bar_color = '#43859B';
		$barchart->Open('suppress_average');
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$barchart->DrawBar($xcount, $fields[1], "$" . AddComma($fields[0] * $factor));
		}
		$barchart->DrawTitle("Complaint Values");
		$barchart->Close();


	}
}
echo "
	<tr><td colspan=2 class='column_header thickpadding center'>
	<a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a>
	</table>
	</div>
	";

$page->write_pagebottom();

?>