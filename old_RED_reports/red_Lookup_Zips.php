<?php

/*
 * 08/07/17 MJS - new file
 * 08/08/17 MJS - added address
 * 08/08/17 MJS - suppressed form when export to spreadsheet
 * 01/30/18 MJS - refactored for APICore
 * 03/22/18 MJS - modified to NOT sort or remove duplicates
 * 03/20/19 MJS - trimmed zip+4
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);

$iZips = NoApost($_POST['iZips']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
if ($output_type == "") {
	echo "
		<tr>
		<td class=labelback width=66%>
			Enter or paste a list postal codes, <b>each on a separate line</b>.<br/>
			<br/>
			U.S. postal codes must be 5 digits.<br/>
			Canadian postal codes must be 6 or 7 characters.<br/>
			<br/>
			For example:<br/>
			&nbsp; &nbsp; 12345<br/>
			&nbsp; &nbsp; T6J 0T8<br/>
			&nbsp; &nbsp; 23456<br/>
			&nbsp; &nbsp; 90215<br/>
			&nbsp; &nbsp; etc.
		<td class='table_cell'>
			<textarea class='roundedborder' name=iZips id=iZips
				rows=15 style='width:100%;' autocomplete=off>{$iZips}</textarea>
		";
}
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$iZips = str_replace("\n", ",", $iZips);
	$iZips = str_replace("\r", "", $iZips);
	$iZips = str_replace("\j", "", $iZips);
	$iZips = str_replace("\t", "", $iZips);
	//$iZips = str_replace(" ", "", $iZips);
	$tmpzips = explode(",", $iZips);
	$newzips = array();
	foreach ($tmpzips as $tmpzip) {
		$tmpzip = trim($tmpzip);
		if (strlen($tmpzip) == 10) {
			$tmpzip = substr($tmpzip, 0, 5);
		}
		if (strlen($tmpzip) != 5 and strlen($tmpzip) != 6 and strlen($tmpzip) != 7) {
			continue;
		}
		if (strlen($tmpzip) == 6) {
			$tmpzip = substr($tmpzip, 0, 3) . " " . substr($tmpzip, 3, 3);
		}
		$newzips[] = $tmpzip;
	}
	$iZips = implode(",", $newzips);

	$query_create_zips = "";
	foreach ($newzips as $newzip) {
		$query_create_zips .= "INSERT INTO #yourzip VALUES('{$newzip}'); ";
	}

	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_BureauZip = "CORE.dbo.lnkBureauZip";
		$table_Bureau = "CORE.dbo.datBureau";
	}
	else {
		$table_BureauZip = "APICore.dbo.BureauZip";
		$table_Bureau = "APICore.dbo.Bureau";
	}

	$query = "
		create table #yourzip ( zip varchar(10) );
		{$query_create_zips}

		SELECT
			#yourzip.zip,
			BBB.NicknameCity,
			case when bp.Email > '' then bp.Email else '' end as Email,
			case when BBB.MailingAddress > '' then BBB.MailingAddress else BBB.Address end,
			case when BBB.MailingAddress2 > '' then BBB.MailingAddress2 else BBB.Address2 end,
			BBB.City,
			BBB.State,
			BBB.Zip
		FROM #yourzip
		LEFT OUTER JOIN {$table_BureauZip} bz WITH (NOLOCK) ON bz.ZIPCode = #yourzip.zip and bz.CountryCode in ('USA','CAN')
		LEFT OUTER JOIN {$table_Bureau} bb WITH (NOLOCK) on bb.bureauid = bz.bureauid and bb.BureauBranchId = '0'
		LEFT OUTER JOIN BBB WITH (NOLOCK) ON BBB.BBBBranchID = '0' and BBB.BBBID = bb.BureauCode
		LEFT OUTER JOIN BBBPerson bp WITH (NOLOCK) ON bp.BBBID = BBB.BBBID and bp.BBBBranchID = BBB.BBBBranchID and
			bp.OperationsDirector = '1'
		WHERE
			LEN(#yourzip.zip) > 0
		";

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) AbortREDReport($query);
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('Postal Code', '', '', 'left'),
				array('BBB Office', '', '', 'left'),
				array('BBB Email', '', '', 'left'),
				array('BBB Address', '', '', 'left'),
				array('BBB City', '', '', 'left'),
				array('ST', '', '', 'left'),
				array('Postal Code', '', '', 'left'),
			)
		);
		$xcount = 0;

		foreach ($rs as $k => $fields) {
			$xcount++;
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					$fields[2],
					$fields[3] . " " . $fields[4],
					$fields[5],
					$fields[6],
					$fields[7]
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}
	
$page->write_pagebottom();

?>