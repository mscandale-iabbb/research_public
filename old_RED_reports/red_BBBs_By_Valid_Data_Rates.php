<?php

/*
 * 10/26/17 MJS - new file
 * 10/30/17 MJS - rewrote query, split query, removed MoE column, refactored
 * 11/02/17 MJS - added hyperlink to detail report
 * 11/06/17 MJS - added more fields, modified array keys
 * 11/07/17 MJS - added more fields, descriptions re-worded
 * 12/20/17 MJS - rewrote with input option for each data field, added more data fields, changed layout
 * 01/30/18 MJS - refactored for APICore
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);

$iFields = NoApost($_POST['iFields']);
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
if ($iMonthTo == 0) {
	$iMonthTo = 12;
	$iYearTo--;
	$iMonthFrom = $iMonthTo;
	$iYearFrom = $iYearTo;
}
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddRadio('iFields[Street]', 'Check whether street address is blank?', $iFields['Street'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[City]', 'Check whether city is blank?', $iFields['City'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[State]', 'Check whether state/province is blank?', $iFields['State'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Zip]', 'Check whether postal code is blank or has too few characters?', $iFields['Zip'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Country]', 'Check whether country is blank?', $iFields['Country'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Started]', 'Check whether start date is blank?', $iFields['Started'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[IncState]', 'Check whether state established is blank?', $iFields['IncState'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[TOB]', 'Check whether TOB is 99999-000, 60987, 60984, 60989, 61016, or other vague one?', $iFields['TOB'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Phone]', 'Check whether phone is blank or has too few characters?', $iFields['Phone'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Website1]', 'Check whether website is blank?', $iFields['Website1'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Website2]', 'Check whether website has too few characters or is missing a period?', $iFields['Website2'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Email1]', 'Check whether email is blank?', $iFields['Email1'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Email2]', 'Check whether email has too few characters or is missing an @ symbol or a period?', $iFields['Email2'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Rating]', 'Check whether rating is NR or NA?', $iFields['Rating'], array('Yes' => 'yes', 'No' => ''));
//$input_form->AddRadio('iFields[Hours]', 'Check whether hours of operation is blank?', $iFields['Hours'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[First]', 'Check whether contact first name is blank?', $iFields['First'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Last]', 'Check whether contact last name is blank?', $iFields['Last'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Title]', 'Check whether contact title is blank?', $iFields['Title'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Revenue1]', 'Check whether revenue is 0?', $iFields['Revenue1'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Revenue2]', 'Check whether revenue is less than $10,000 or more than $1 trillion?', $iFields['Revenue2'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[Empl]', 'Check whether employees is 0?', $iFields['Empl'], array('Yes' => 'yes', 'No' => ''));
$input_form->AddRadio('iFields[ServArea]', 'Check whether service area text is blank?', $iFields['ServArea'], array('Yes' => 'yes', 'No' => ''));

$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Total Companies' => 'Companies',
	'Valid Rate' => 'ValidRate',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

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
		SELECT
			BBB.NickNameCity + ', ' + BBB.State,
			BBB.BBBID,
			(
				select count(*) from Business b WITH (NOLOCK)
				left outer join {$table_Org} o WITH (NOLOCK) on o.BureauCode = b.BBBID and o.SourceBusinessId = b.BusinessID
				left outer join BusinessContact p WITH (NOLOCK) on p.BBBID = b.BBBID and p.BusinessID = b.BusinessID and
					p.TypeOfContact = 'President/CEO'
				where
					b.BBBID = BBB.BBBID
					/*b.IsReportable = '1' and b.PublishToCIBR = '1' and b.ReportType != 'Local' and*/
					/*(b.BOConlyIsOutOfBusiness = '0' or b.BOConlyIsOutOfBusiness is null)*/
			) as Companies,
			(
				1 - (
					select count(*) from Business b WITH (NOLOCK)
					left outer join {$table_Org} o WITH (NOLOCK) on o.BureauCode = b.BBBID and o.SourceBusinessId = b.BusinessID
					left outer join BusinessContact p WITH (NOLOCK) on p.BBBID = b.BBBID and p.BusinessID = b.BusinessID and
						(select count(*) from BusinessContact p2 WITH (NOLOCK) where p2.BBBID = b.BBBID and
							p2.BusinessID = b.BusinessID and p2.PersonID < p.PersonID) = 0
					left outer join tblStates s WITH (NOLOCK) on s.StateAbbreviation = b.StateProvince
					where
						b.BBBID = BBB.BBBID and
						/*b.IsReportable = '1' and b.PublishToCIBR = '1' and b.ReportType != 'Local' and*/
						/*(b.BOConlyIsOutOfBusiness = '0' or b.BOConlyIsOutOfBusiness is null) and*/
						(
							('{$iFields['Street']}' = 'yes' and (b.StreetAddress is null or LEN(b.StreetAddress) <= 3)) or
							('{$iFields['City']}' = 'yes' and (b.City is null or LEN(b.City) <= 3)) or
							('{$iFields['State']}' = 'yes' and (b.StateProvince is null or b.StateProvince = '' or (s.StateAbbreviation is null and b.Country in ('usa','can')))) or
							('{$iFields['Zip']}' = 'yes' and (b.PostalCode is null or LEN(b.PostalCode) < 5)) or
							('{$iFields['Country']}' = 'yes' and (b.Country is null or LEN(b.Country) = 0)) or
							('{$iFields['Phone']}' = 'yes' and (b.Phone is null or LEN(b.Phone) < 7)) or
							('{$iFields['Website1']}' = 'yes' and (b.Website is null or LEN(b.Website) = 0)) or
							('{$iFields['Website2']}' = 'yes' and (b.Website not like '%.%' or LEN(b.Website) <= 4)) or
							('{$iFields['Email1']}' = 'yes' and (b.Email is null or LEN(b.Email) = 0)) or
							('{$iFields['Email2']}' = 'yes' and (LEN(b.Email) <= 7 or b.Email not like '%@%' or b.Email not like '%.%')) or
							('{$iFields['TOB']}' = 'yes' and (b.TOBID like '99999%' or b.TOBID like '60987%' or b.TOBID like '60984%' or b.TOBID like '60989%' or b.TOBID like '61016%' or b.TOBID like '20087%' or b.TOBID like '50308%' or b.TOBID like '60784%' or b.TOBID like '61047%' or b.TOBID like '80342%')) or
							('{$iFields['Rating']}' = 'yes' and (b.BBBRatingGrade like 'N%')) or
							('{$iFields['Started']}' = 'yes' and (b.DateBusinessStarted is null)) or
							('{$iFields['Empl']}' = 'yes' and (b.NumberOfEmployees is null or b.NumberOfEmployees = '0')) or
							('{$iFields['First']}' = 'yes' and (p.FirstName is null or LEN(p.FirstName) <= 0)) or
							('{$iFields['Last']}' = 'yes' and (p.LastName is null or LEN(p.LastName) <= 1)) or
							('{$iFields['Title']}' = 'yes' and (p.Title is null or LEN(p.Title) <= 1)) or
							('{$iFields['IncState']}' = 'yes' and (o.{$column_IncorporationState} is null or len(o.{$column_IncorporationState}) <= 0)) or
							('{$iFields['ServArea']}' = 'yes' and (o.ServingArea is null or len(o.ServingArea) <= 1)) or
							('{$iFields['Revenue1']}' = 'yes' and (b.BOConlyGrossRevenue is null or b.BOConlyGrossRevenue = 0)) or
							('{$iFields['Revenue2']}' = 'yes' and (b.BOConlyGrossRevenue <= 10000 or b.BOConlyGrossRevenue >= 1000000000000))
						)
				) /
				cast ((
					select count(*) from Business b WITH (NOLOCK)
					left outer join {$table_Org} o WITH (NOLOCK) on o.BureauCode = b.BBBID and o.SourceBusinessId = b.BusinessID
					left outer join BusinessContact p WITH (NOLOCK) on p.BBBID = b.BBBID and p.BusinessID = b.BusinessID and
						(select count(*) from BusinessContact p2 WITH (NOLOCK) where p2.BBBID = b.BBBID and
							p2.BusinessID = b.BusinessID and p2.PersonID < p.PersonID) = 0
					where
						b.BBBID = BBB.BBBID
						/*b.IsReportable = '1' and b.PublishToCIBR = '1' and b.ReportType != 'Local' and*/
						/*(b.BOConlyIsOutOfBusiness = '0' or b.BOConlyIsOutOfBusiness is null)*/
				) as decimal(14,2))
			) as ValidRate
		from BBB WITH (NOLOCK)
		where
			BBB.BBBBranchID = 0 and BBB.IsActive = '1' and
			('{$iRegion}' = '' or BBB.Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				BBB.SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or BBB.State IN ('" . str_replace(",", "','", $iState) . "'))
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Invalid Businesses', '', '', 'right'),
				array('Total Businesses', $SortFields['Total Companies'], '', 'right'),
				array('Valid Rate', $SortFields['Valid Rate'], '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[1] == $BBBID) $class = "bold darkgreen";
			else $class = "";

			$oDataField = "";
			foreach ($iFields as $k => $v) {
				if ($v == "yes") {
					$oDataField .= $k . " ";
				}
			}
			/*
			// make data field into hyperlink for own BBB
			if ($fields[1] == $BBBID && $iFields > '') {
				$oDataField .= " &nbsp; <a target=_details href='red_Invalid_Details.php?iField={$iFields}&iDescription={$oDataField}'>View Details</a>";
			}
			*/

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[1] .
						"><span class='{$class}'>" . AddApost($fields[0]) . "</span></a>",
					intval($fields[2] * (1.00 - $fields[3])),
					$fields[2],
					FormatPercentage($fields[3], 1),
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'Averages',
				'',
				intval(array_sum( get_array_column($rs, 2) ) / count($rs)),
				FormatPercentage(array_sum( get_array_column($rs, 3) ) / count($rs)),
			)
		);
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

?>