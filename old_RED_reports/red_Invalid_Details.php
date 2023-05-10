<?php

/*
 * 11/02/17 MJS - new file
 * 11/06/17 MJS - modified for new array keys
 * 11/07/17 MJS - added more fields
 * 01/30/18 MJS - refactored for APICore
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();


$iBBBID = $BBBID;
$iField = NoApost($_REQUEST['iField']);
$iDescription = NoApost($_REQUEST['iDescription']);
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
if (! $iMaxRecs) {
	$iMaxRecs = 100;
}

if (! $iField) {
	die("No field selected");
}

echo "
	<div class='main_section roundedborder'>
	<table class='report_table'>

	<form id=form1 method=post>

	<tr>
	<td colspan=2 class='column_header center largefont'>
	List Records for BBB {$NickNameCity}<br/><br/>
	Failing criteria: {$iDescription}

	<!--
	<tr>
	<td class='labelback' width=15%>
	Field
	<td class='table_cell'>
	<input type=text id=iField name=iField style='width:2%' value='{$iField}' />
	-->

	<tr>
	<td class='labelback' width=15%>
	Return a maximum of
	<td class='table_cell'>
	<input type=text id=iMaxRecs name=iMaxRecs style='width:3%' value='{$iMaxRecs}' /> records

	<tr>
	<td class='labelback' width=15%>
	&nbsp;
	<td class='table_cell'>
	<input type=hidden id=iBBBID name=iBBBID value='{$iBBBID}' />
	<input type=hidden id=iComplaintID name=iComplaintID value='{$iComplaintID}' />
	<input type=submit class='submit_button' style='color:white' value=' Search ' />

	</form>	
	";

if ($_POST) {
	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$column_IncorporationState = "IncorpStateCode";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$column_IncorporationState = "IncorporationStateCode";
	}
	$query = "
		select top {$iMaxRecs}
			b.BusinessID,
			b.BusinessName
		from Business b WITH (NOLOCK)
		left outer join {$table_Org} o WITH (NOLOCK) on o.BureauCode = b.BBBID and o.SourceBusinessId = b.BusinessID
		left outer join BusinessContact p WITH (NOLOCK) on p.BBBID = b.BBBID and p.BusinessID = b.BusinessID and
			(select count(*) from BusinessContact p2 WITH (NOLOCK) where p2.BBBID = b.BBBID and
			p2.BusinessID = b.BusinessID and p2.PersonID < p.PersonID) = 0
		left outer join tblStates s WITH (NOLOCK) on s.StateAbbreviation = b.StateProvince
		where
			b.BBBID = '{$iBBBID}' and
			b.IsReportable = '1' and b.PublishToCIBR = '1' and b.ReportType != 'Local' and
			(b.BOConlyIsOutOfBusiness = '0' or b.BOConlyIsOutOfBusiness is null) and
			(
				('{$iField}' = 'Street' and (b.StreetAddress is null or LEN(b.StreetAddress) <= 3)) or
				('{$iField}' = 'City' and (b.City is null or LEN(b.City) <= 3)) or
				('{$iField}' = 'State' and (b.StateProvince is null or b.StateProvince = '' or (s.StateAbbreviation is null and b.Country in ('usa','can')))) or
				('{$iField}' = 'Zip' and (b.PostalCode is null or LEN(b.PostalCode) < 5)) or
				('{$iField}' = 'Phone' and (b.Phone is null or LEN(b.Phone) < 7)) or
				('{$iField}' = 'Website' and (b.Website is null or not b.Website like '%.%' or LEN(b.Website) <= 4)) or
				('{$iField}' = 'Email' and (b.Email is null or LEN(b.Email) <= 7 or not(b.Email like '%@%'))) or
				('{$iField}' = 'TOB' and (b.TOBID like '99999%' or b.TOBID like '60987%' or b.TOBID like '60984%' or b.TOBID like '60989%' or b.TOBID like '61016%' or b.TOBID like '20087%' or b.TOBID like '50308%' or b.TOBID like '60784%' or b.TOBID like '61047%' or b.TOBID like '80342%')) or
				('{$iField}' = 'Rating' and (b.BBBRatingGrade like 'N%')) or
				('{$iField}' = 'Started' and (b.DateBusinessStarted is null)) or
				('{$iField}' = 'Empl' and (b.NumberOfEmployees is null or b.NumberOfEmployees = '0')) or
				('{$iField}' = 'First' and (p.FirstName is null or LEN(p.FirstName) <= 0)) or
				('{$iField}' = 'Last' and (p.LastName is null or LEN(p.LastName) <= 1)) or
				('{$iField}' = 'Title' and (p.Title is null or LEN(p.Title) <= 1)) or
				('{$iField}' = 'IncState' and (o.{$column_IncorporationState} is null or len(o.{$column_IncorporationState}) <= 0)) or
				('{$iField}' = 'ServArea' and (o.ServingArea is null or len(o.ServingArea) <= 1)) or
				('{$iField}' = 'Revenue' and (b.BOConlyGrossRevenue is null or b.BOConlyGrossRevenue < 1000 or b.BOConlyGrossRevenue > 500000000000))
			)
		order by b.BusinessName
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			$oBusinessID = $fields[0];
			$oBusinessName = $fields[1];
			echo "
				<tr>
				<td class='table_cell right'>
				<a target=_moredetails href=red_Business_Details.php?iBBBID={$iBBBID}&iBusinessID={$oBusinessID}>{$oBusinessID}</a>
				<td class='table_cell left'>
				{$oBusinessName}
				";
		}
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