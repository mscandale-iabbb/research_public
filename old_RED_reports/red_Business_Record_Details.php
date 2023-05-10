<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 05/19/15 MJS - aded option for counts only
 * 06/02/15 MJS - added field for Country
 * 08/03/15 MJS - changed "Parent" to "View Parent"
 * 11/17/15 MJS - added jQuery command to reset iSortBy value when change iSearchType
 * 11/28/15 MJS - reduced sizes of fields to less than 320px for responsive
 * 04/07/16 MJS - fixed searching by last name with apostrophes
 * 06/01/16 MJS - modified Searchable column for businesses that are local reviews
 * 08/12/16 MJS - fixed bug with variable named 'protocol' not resetting
 * 08/24/16 MJS - aligned column headers
 * 09/13/16 MJS - fixed another bug with variable named 'protocol' not resetting
 * 11/15/17 MJS - added option for NAICS
 * 03/28/18 MJS - added option for secondary TOBs
 * 03/20/19 MJS - added option for community members
 * 03/27/19 MJS - added option for no duplicates
 * 04/11/19 MJS - changed to use CORE for urls
 * 04/18/19 MJS - added parent company rating grade
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iSearchType = NoApost( GetInput('iSearchType','Names') );
$iBusinessID = Numeric2($_POST['iBusinessID']);

$iBusinessName = $_REQUEST['iBusinessName'];

// note: this section needs to be moved into a function in func.php
$iBusinessName = NoPunc3($iBusinessName);  // first strip all punctuation except spaces
$iBusinessName = str_ireplace('&#39;', '', $iBusinessName);
$iBusinessName = strtolower($iBusinessName);
$iBusinessName = str_ireplace(' and ', ' ', $iBusinessName);
if (substr($iBusinessName,0,4) == 'the ') $iBusinessName = substr($iBusinessName,3);
if (substr($iBusinessName,strlen($iBusinessName) - 4,4) == ' inc')
	$iBusinessName = substr($iBusinessName,0,strlen($iBusinessName) - 4);
if (substr($iBusinessName,strlen($iBusinessName) - 4,4) == ' llc')
	$iBusinessName = substr($iBusinessName,0,strlen($iBusinessName) - 4);
if (substr($iBusinessName,strlen($iBusinessName) - 3,3) == ' co')
	$iBusinessName = substr($iBusinessName,0,strlen($iBusinessName) - 3);
if (substr($iBusinessName,strlen($iBusinessName) - 8,8) == ' company')
	$iBusinessName = substr($iBusinessName,0,strlen($iBusinessName) - 8);
$iBusinessName = NoPunc2($iBusinessName);  // finally strip spaces

$iStreet = NoApost($_REQUEST['iStreet']);
$iLastName = str_replace("'", "''", $_REQUEST['iLastName']);
$iFirstName = NoPunc3($_REQUEST['iFirstName']);
$iPhone = Numeric2($_REQUEST['iPhone']);
$iEmail = NoApost($_REQUEST['iEmail']);
$iURL = NoApost($_REQUEST['iURL']);
$iCity = NoPunc3($_REQUEST['iCity']);
$iState = NoApost($_POST['iState']);
$iZip = NoApost($_POST['iZip']);
$iCountry = NoApost($_POST['iCountry']);
$iNAICS = NoApost($_POST['iNAICS']);
$iTOB = NoApost($_REQUEST['iTOB']);
$iTOBCode = NoApost($_REQUEST['iTOBCode']);
$iTOBType = NoApost($_REQUEST['iTOBType']);
$iReportType = NoApost($_REQUEST['iReportType']);
$iHQ = NoApost($_REQUEST['iHQ']);
$iCountsOnly = NoApost($_REQUEST['iCountsOnly']);
$iPrimaryOnly = NoApost($_REQUEST['iPrimaryOnly']);
$iAB = NoApost($_REQUEST['iAB']);
$iCommunityMember = NoApost($_REQUEST['iCommunityMember']);
$iRating = NoApost($_REQUEST['iRating']);
$iSize = NoApost($_REQUEST['iSize']);
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if ($iSortBy == 'x.BusinessName' && $iSearchType != 'Names') $iSortBy = 'b.BusinessID';
if ($iSortBy == 'x.StreetAddress' && $iSearchType != 'Addresses') $iSortBy = 'b.BusinessID';
if ($iSortBy == 'x.LastName' && $iSearchType != 'Persons') $iSortBy = 'b.BusinessID';
if ($iSortBy == 'x.Phone' && $iSearchType != 'Phones') $iSortBy = 'b.BusinessID';
if ($iSortBy == 'x.Email' && $iSearchType != 'Emails') $iSortBy = 'b.BusinessID';
if ($iSortBy == 'x.URL' && $iSearchType != 'URLs') $iSortBy = 'b.BusinessID';
$iShowSource = $_REQUEST['iShowSource'];

$input_form = new input_form($conn);


// search type field
if ($output_type == '') {
	echo "<tr><td class=labelback>Search type<td class='table_cell'>";
	$search_options = array(
		'Names' => 'iBusinessName',
		'Addresses' => 'iStreet',
		'Persons' => 'iLastName',
		'Phones' => 'iPhone',
		'Emails' => 'iEmail',
		'URLs' => 'iURL'
	);
	foreach ( $search_options as $searchtype => $fieldname) {
		echo "<input type=radio id=iSearchType name=iSearchType ";
		if ($iSearchType == $searchtype) {
			echo " CHECKED ";
		}
		// onclick hide all search fields except the selected one
		echo " onclick=\" ";
		echo " $('#" . $fieldname . "').show(); ";
		echo " $('#iFirstName').show(); ";
		foreach ($search_options as $searchtype_exclude => $fieldname_hide) {
			if ($searchtype_exclude != $searchtype) {
				echo " $('#" . $fieldname_hide . "').hide(); ";
				if ($searchtype_exclude == 'Persons') echo " $('#iFirstName').hide(); ";
			}
		}
		echo " $('#iSortBy').val('b.BusinessID'); ";  // resets Select value to avoid sort fields not in newly-selected table
		echo " \" ";
		//
		echo " value=" . $searchtype . ">" . $searchtype . "</input>";
		echo "&nbsp; &nbsp; ";
	}
}

$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;" );

$input_form->AddTextField('iBusinessName', 'Business name', $iBusinessName, "width:175px;");
$input_form->AddTextField('iStreet', 'Street address', $iStreet, "width:175px;");
$input_form->AddTextField('iLastName', 'Last and first names', str_replace("''", "'", $iLastName), "width:175px;");
$input_form->AddTextField('iFirstName', ' ', $iFirstName, "width:100px;", 'sameline');
$input_form->AddTextField('iPhone', 'Phone number with area code', $iPhone, "width:175px;");
$input_form->AddTextField('iEmail', 'Email', $iEmail, "width:175px;");
$input_form->AddTextField('iURL', 'URL', $iURL, "width:175px;");

$input_form->AddTextField('iCity', 'City', $iCity, "width:175px;");
$input_form->AddMultipleSelectField('iState', 'State', $iState,
	$input_form->BuildStatesArray(''), '', '', '', 'width:300px');
$input_form->AddTextField('iZip', 'Postal code', $iZip, "width:50px;");
$input_form->AddSelectField('iCountry','Country',$iCountry,
	array('' => '', 'United States' => 'USA', 'Canada' => 'CAN', 'Mexico' => 'MEX') );
$input_form->AddSelectField('iNAICS', 'Industry', $iNAICS, $input_form->BuildNAICSGroupArray() );
$input_form->AddTextField('iTOB','TOB description contains',$iTOB);
$input_form->AddMultipleSelectField('iTOBCode', 'TOB code', $iTOBCode, $input_form->BuildTOBsArray(),
	'width:300px;', '', '', 'width:300px;');
$input_form->AddRadio('iTOBType', 'TOB type', $iTOBType, array('Primary' => '', 'All' => 'All'));
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddSelectField('iCommunityMember','Community member status',$iAB, array('Both' => '', 'Community member' => '1', 'Not community member' => '0') );
$input_form->AddMultipleSelectField('iRating', 'Business rating', $iRating,
	$input_form->BuildRatingsArray('all'), '', '', '', 'width:300px');
$input_form->AddMultipleSelectField('iSize', 'Business size', $iSize,
	$input_form->BuildSizesArray('all'), '', '', '', 'width:300px');
$input_form->AddRadio('iReportType', 'Review type', $iReportType, array(
		'ALL' => '',
		'Single' => 'Single',
		'Local' => 'Local',
		'Single and Local' => 'Both'
	)
);
$input_form->AddSelectField('iHQ','HQ status',$iHQ, array('Both' => '', 'HQ' => '1', 'Non-HQ' => '0') );
$input_form->AddRadio('iPrimaryOnly','Primary records only',$iPrimaryOnly, array('Yes' => '1', 'No' => '') );
$input_form->AddRadio('iCountsOnly','Type of results',$iCountsOnly, array('Record details' => '', 'Record counts' => '1') );

$SortFields = array(
	'Business name' => 'b.BusinessName',
	'BBB city' => 'BBB.NicknameCity,b.BusinessName',
	'ID' => 'b.BusinessID',
	'Business city' => 'b.City,b.BusinessName',
	'Business state' => 'b.StateProvince,b.BusinessName',
	'Business postal code' => 'LEFT(b.PostalCode,5),b.BusinessName',
	'TOB code' => 'b.TOBID,b.BusinessName',
	'TOB description' => 'tblYPPA.yppa_text,b.BusinessName',
	'AB' => 'AB,b.BusinessName',
	'Rating' => 'r.BBBRatingSortOrder,b.BusinessName',
	'Size' => 's.SizeOfBusinessSortOrder,b.BusinessName',
	'Report type' => 'b.ReportType,b.BusinessName',
	'Reportable' => 'Reportable,b.BusinessName',
	'Searchable' => 'Searchable,b.BusinessName',
	'HQ' => 'HQ,b.BusinessName',
);
if ($iSearchType == 'Names') $SortFields['BusinessName'] = 'x.BusinessName';
if ($iSearchType == 'Addresses') $SortFields['StreetAddress'] = 'x.StreetAddress';
if ($iSearchType == 'Persons') $SortFields['LastName'] = 'x.LastName,x.FirstName';
if ($iSearchType == 'Phones') $SortFields['Phone'] = 'x.Phone';
if ($iSearchType == 'Emails') $SortFields['Email'] = 'x.Email';
if ($iSearchType == 'URLs') $SortFields['URL'] = 'x.URL';

$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

// hide unselected fields with jquery by default
if ($output_type == '') {
	echo "\n<script type=text/javascript>\n";
	foreach ($search_options as $searchtype_exclude => $fieldname_hide) {
		if ($searchtype_exclude != $iSearchType) {
			echo " $('#" . $fieldname_hide . "').hide(); ";
			if ($searchtype_exclude == 'Persons') echo " $('#iFirstName').hide(); ";
		}
	}
	echo "</script>\n";
}

if ($_POST) {
	if ($iCountsOnly || $iPrimaryOnly) {
		$iTOBType = "";
	}
	$query_data = array(
		'Names' => array('BusinessName', 'BusinessName',
			/*"x.CondensedName like dbo.getCondensedBusinessName('" . $iBusinessName . "') + '%'"),*/
			"x.CondensedName like '" . $iBusinessName . "%'"),
		'Addresses' => array('BusinessAddress', 'StreetAddress', "x.StreetAddress like '" . $iStreet . "%'"),
		'Persons' => array('BusinessContact', 'LastName', "replace(x.LastName,'&#39;','''') like '" . $iLastName .
			"%' and x.FirstName like '" . $iFirstName . "%'"),
		'Phones' => array('BusinessPhone', 'Phone', "x.Phone like '" . $iPhone . "%'"),
		'Emails' => array('BusinessEmail', 'Email', "x.Email like '" . $iEmail . "%'"),
		/*'URLs' => array('BusinessURL', 'URL', "x.URL like '" . $iURL . "%'")*/
		'URLs' => array('CORE.dbo.atrURL', 'URL', "x.URL like '%" . $iURL . "%'")
	);
	// hack for person's first name:
	if ($query_data[$iSearchType][1] == 'LastName') $query_data[$iSearchType][1] = "LastName + ', ' + x.FirstName";
	if ($iCountsOnly) $query =
		"SELECT COUNT(*) ";
	else $query =
		"SELECT TOP " . $iMaxRecs . "
			b.BBBID,
			b.BusinessID,
			x." . $query_data[$iSearchType][1] . ",
			b.BusinessName,
			b.City + ' ' + b.StateProvince + ' ' + b.PostalCode,
			bt.TOBid + ' ' + tblYPPA.yppa_text,
			BBB.NickNameCity + ', ' + BBB.State,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.BBBRatingGrade,
			b.Email,
			b.ReportType,
			Case When b.IsReportable = '1' then 'Yes' else 'No' end as Reportable,
			Case
				When b.ReportType != 'Local' and b.PublishToCIBR = '1' then 'Yes'
				When b.ReportType != 'Local' and (b.PublishToCIBR = '0' or b.PublishToCIBR is null) then 'No'
				else 'No'
			end as Searchable,
			Case When b.IsHQ = '1' then 'Yes' else 'No' end as HQ,
			b.ReportURL,
			SingleReport.ReportURL,
			b.SizeOfBusiness,
			SingleReport.BBBRatingGrade
			/*Case When b.IsCommunityMember = '1' then 'Yes' else 'No' end as CommunityMember*/
			";
	$query .= " from " . $query_data[$iSearchType][0] . " x WITH (NOLOCK) ";
	if ($iSearchType == 'URLs') $query .= "
		inner join CORE.dbo.lnkOrgURL lu on lu.URLID = x.URLID and lu.URLTypeID not in ('717','718','719','721','738','739')
		inner join CORE.dbo.datOrg o on o.OrgID = lu.OrgID
		inner join Business b WITH (NOLOCK) ON b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		";
	if ($iSearchType != 'URLs') $query .= "
		inner join Business b WITH (NOLOCK) ON b.BBBID = x.BBBID AND b.BusinessID = x.BusinessID ";
	$query .= "
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		inner join BusinessTOBID bt WITH (NOLOCK) on bt.BBBID = b.BBBID and bt.BusinessID = b.BusinessID and
			(bt.IsPrimaryTOBID = '1' or '{$iTOBType}' = 'All')
		left outer join tblYPPA WITH (NOLOCK) ON bt.TOBID = tblYPPA.yppa_code
		left outer join Business SingleReport WITH (NOLOCK) ON
			SingleReport.BBBID = b.ReportingBBBID AND SingleReport.BusinessID = b.ReportingBusinessID
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join tblSizesOfBusiness s WITH (NOLOCK) ON s.SizeOfBusiness = b.SizeOfBusiness
		";
	$query .= "
		WHERE
			" . $query_data[$iSearchType][2] . " and
			('" . $iState . "' = '' or b.StateProvince IN ('" . str_replace(",", "','", $iState) . "')) and
			('" . $iCity . "' = '' or b.City = '" . $iCity . "') and
			('" . $iZip . "' = '' or b.PostalCode like '" . $iZip . "%') and
			('" . $iCountry . "' = '' or b.Country = '" . $iCountry . "') and
			('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
			('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
			('{$iTOBCode}' = '' or bt.TOBID IN ('" . str_replace(",", "','", $iTOBCode) . "')) and
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			('{$iBusinessID}' = '' or b.BusinessID = '{$iBusinessID}' ) and
			(
				('" . $iReportType . "' = '') or
				(b.ReportType = '" . $iReportType . "') or
				('" . $iReportType . "' = 'both' and b.ReportType in ('Single','Local') )
			) and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
				('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			) and
			(
				('{$iCommunityMember}' = '') or
				('{$iCommunityMember}' = '1' and b.IsCommunityMember = 1) or
				('{$iCommunityMember}' = '0' and (b.IsCommunityMember = 0 or b.IsCommunityMember is null))
			) and
			(
				('{$iHQ}' = '') or
				('{$iHQ}' = '1' and b.IsHQ = 1) or
				('{$iHQ}' = '0' and (b.IsHQ = 0 or b.IsHQ is null))
			) and
			('" . $iRating . "' = '' or b.BBBRatingGrade IN ('" . str_replace(",", "','", $iRating) . "')) and
			('" . $iSize . "' = '' or b.SizeOfBusiness IN ('" . str_replace(",", "','", $iSize) . "'))
			";
	if ($iCountsOnly || $iPrimaryOnly) {
		if ($iSearchType == 'Names') $query .= " and x.IsPrimaryName = '1'";
		if ($iSearchType == 'Addresses') $query .= " and x.IsPrimaryAddress = '1'";
		if ($iSearchType == 'Persons') $query .= " and x.TypeOfContact = 'President/CEO'";
		if ($iSearchType == 'Phones') $query .= " and x.IsPrimaryPhone = '1'";
		if ($iSearchType == 'Emails') $query .= " and x.IsPrimaryEmail = '1'";
		/*if ($iSearchType == 'URLs') $query .= " and x.IsPrimaryURL = '1'";*/
		if ($iSearchType == 'URLs') $query .= " and lu.isPrimary = '1'";
	}
	if ($iSortBy > '' && ! $iCountsOnly) {
		$query .= " ORDER BY " . $iSortBy;
	}

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) AbortREDReport($query);
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	if ($iCountsOnly) {
		$rsraw->MoveFirst();
		$result_count = AddComma($rsraw->fields[0]);
		echo "
			<div class='main_section roundedborder'>
			<div class='inner_section'>
			<p>{$result_count} rows</p>
			";
		if ($iShowSource) echo "<p>{$query}</p>";
		echo "
			</div>
			</div>
			";
		die();
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		// hack for person's first name:
		if ($query_data[$iSearchType][1] == "LastName + ', ' + x.FirstName") $query_data[$iSearchType][1] = 'LastName';
		$report->WriteHeaderRow(
			array (
				array('#', '', '', 'right'),
				array('ID', $SortFields['ID'], '', 'left'),
				/*array(CamelCaseToSpaces($query_data[$iSearchType][1]), "x." . $query_data[$iSearchType][1] ),*/
				array(CamelCaseToSpaces($query_data[$iSearchType][1]), $SortFields[$query_data[$iSearchType][1]], '', 'left' ),
				array('Name', $SortFields['Business name'], '', 'left'),
				array('Address', $SortFields['Business city'], '', 'left'),
				array('TOB', $SortFields['TOB code'], '', 'left'),
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
				array('Size', $SortFields['Size'], '', 'left'),
				/*'Email',*/
				array('Report', $SortFields['Reportable'], '', 'left'),
				array('Type', $SortFields['Report type'], '', 'left'),
				array('Search', $SortFields['Searchable'], '', 'left'),
				array('HQ', $SortFields['HQ'], '', 'left')
				)
			);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_REQUEST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;

		foreach ($rs as $k => $fields) {
			$xcount++;

			//if ($_REQUEST['output_type'] == '') {
				if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
				if ($xcount > $iPageNumber * $iPageSize) break;
			//}

			/*
			// email
			if (trim($fields[9]) == '') {
				$email = '';
			}
			else {
				$email = '<a href=mailto:' . $fields[9] . '>Email</a>';
			}
			*/

			// reportable
			$reportable = $fields[11];
			$protocol = '';
			if ($reportable == 'Yes') {
				if (substr($fields[14],0,4) != 'http') {
					$protocol = "https://";
				}
				$reportable = "<a target=_new href=" . $protocol . $fields[14] . ">Yes</a>";
			}

			// report type
			$reporttype = $fields[10];
			$protocol = '';
			if ($reporttype == 'Local') {
				if (substr($fields[15],0,4) != 'http') {
					$protocol = "https://";
				}
				$reporttype = "Local <a target=_new href=" . $protocol . $fields[15] . "><br/><u>Parent:{$fields[17]}</u></a>";
			}

			$report->WriteReportRow(
				array (
					$xcount,
					$fields[1],
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[1] .  ">" . $fields[2] . "</a>",
					$fields[3],
					$fields[4],
					$fields[5],
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . $fields[6] . "</a>",
					$fields[7],
					$fields[8],
					$fields[16],
					/*$email,*/
					$reportable,
					$reporttype,
					$fields[12],
					$fields[13]
				)
			);
		}
	}
	$report->Close();

	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

	
$page->write_pagebottom();

?>
