<?php

/*
 * 10/02/14 MJS - added consumer email and consumer phone to Excel export
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 11/17/14 MJS - split street/city/state/zip/email/phone into separate fields in Excel export
 * 01/08/14 MJS - made tweak that might fix bug for business person search
 * 11/18/15 MJS - changed Like to Contains for searching narrative text
 * 12/16/15 MJS - ensured Scam Tracker records won't appear
 * 06/16/16 MJS - strip quotes from iText
 * 08/24/16 MJS - aligned column headers
 * 09/06/16 MJS - combined Narrative and Outcome columns in web mode
 * 09/07/16 MJS - removed word Outcome
 * 12/02/16 MJS - added industry sector option
 * 05/15/17 MJS - added options for Consumer Email and Business Email
 * 07/21/17 MJS - added option for pending complaints
 * 10/16/17 MJS - added options for business address
 * 11/09/17 MJS - removed industry sector option
 * 11/15/17 MJS - added option for NAICS
 * 03/28/18 MJS - added option for secondary TOBs
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
$iPending = NoApost($_REQUEST['iPending']);
if (! $iPending) $iPending = 'no';
$iBusinessName = NoApost($_REQUEST['iBusinessName']);
$iConsumerLastName = NoApost($_REQUEST['iConsumerLastName']);
$iConsumerFirstName = NoApost($_REQUEST['iConsumerFirstName']);
$iBusinessLastName = NoApost($_REQUEST['iBusinessLastName']);
$iNAICS = NoApost($_POST['iNAICS']);
$iTOB = NoApost($_REQUEST['iTOB']);
$iTOBCode = NoApost($_REQUEST['iTOBCode']);
$iTOBType = NoApost($_REQUEST['iTOBType']);
/*$iIndustry = NoApost($_REQUEST['iIndustry']);*/
$iAB = NoApost($_REQUEST['iAB']);
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iNotBBBID = Numeric2($_REQUEST['iNotBBBID']);
$iConsumerBBBID = Numeric2($_REQUEST['iConsumerBBBID']);
$iState = NoApost($_POST['iState']);
$iCloseCode = NoApost($_POST['iCloseCode']);
$iText = NoApost(NoQuote($_REQUEST['iText']));
$iComplaintID = Numeric2($_POST['iComplaintID']);
$iBusinessID = Numeric2($_POST['iBusinessID']);
$iBusinessEmail = NoApost($_REQUEST['iBusinessEmail']);
$iConsumerEmail = NoApost($_REQUEST['iConsumerEmail']);
$iStreet = NoPunc2($_REQUEST['iStreet']);
$iZip = NoApost($_REQUEST['iZip']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddRadio('iPending','Include pending complaints',$iPending, array('Yes' => 'yes', 'No' => 'no'));
$input_form->AddTextField('iBusinessName', 'Business name', $iBusinessName, "width:175px;");
$input_form->AddTextField('iConsumerLastName', 'Consumer last name', $iConsumerLastName, "width:175px;");
$input_form->AddTextField('iConsumerFirstName', 'Consumer first name', $iConsumerFirstName, "width:175px;");
$input_form->AddTextField('iBusinessLastName', 'Business person last name', $iBusinessLastName, "width:175px;");
$input_form->AddSelectField('iNAICS', 'Industry', $iNAICS, $input_form->BuildNAICSGroupArray() );
$input_form->AddTextField('iTOB','TOB contains word/phrase',$iTOB);
$input_form->AddMultipleSelectField('iTOBCode', 'TOB code', $iTOBCode, $input_form->BuildTOBsArray(),
	'width:300px;', '', '', 'width:375px;');
$input_form->AddRadio('iTOBType', 'TOB type', $iTOBType, array('Primary' => '', 'All' => 'All'));
/*$input_form->AddMultipleSelectField('iIndustry', 'Industry sector', $iIndustry, $input_form->BuildIndustriesArray(),
		'width:300px;', '', '', 'width:375px;');*/
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddSelectField('iNotBBBID', 'Not processed by BBB', $iNotBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddSelectField('iConsumerBBBID', 'Consumer in BBB area', $iConsumerBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iState', 'Consumer state', $iState,
	$input_form->BuildStatesArray(''), '', '', '', 'width:300px');
$input_form->AddMultipleSelectField('iCloseCode', 'Close code', $iCloseCode,
	$input_form->BuildCloseCodesArray(''), '', '', '', 'width:300px');
$input_form->AddTextField('iText', 'Narrative contains word or phrase', $iText, "width:20%;");
$input_form->AddTextField('iComplaintID', 'Complaint ID', $iComplaintID, "width:100px;" );
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;" );
$input_form->AddTextField('iConsumerEmail', 'Consumer email', $iConsumerEmail, "width:20%;");
$input_form->AddTextField('iBusinessEmail', 'Business email', $iBusinessEmail, "width:20%;");
$input_form->AddTextField('iStreet', 'Business street address', $iStreet, "width:20%;");
$input_form->AddTextField('iZip', 'Business postal code', $iZip, "width:5%;");
$SortFields = array(
	'Consumer last name' => 'ConsumerLastName',
	'Consumer first name' => 'ConsumerFirstName',
	'BBB city' => 'NicknameCity',
	'Business name' => 'b.BusinessName',
	'Business postal code' => 'b.PostalCode',
	'Classification' => 'ClassificationID1',
	'Close code' => 'CloseCode',
	'Consumer street address' => 'ConsumerStreetAddress',
	'Consumer city' => 'ConsumerCity',
	'Consumer state' => 'ConsumerStateProvince',
	'Consumer postal code' => 'ConsumerPostalCode',
	'Business street address' => 'b.StreetAddress',
	'Business city' => 'b.City',
	'Business state' => 'b.StateProvince',
	'Business postal code' => 'b.PostalCode',
	'TOB code' => 'TOBID',
	'TOB description' => 'tblYPPA.yppa_text',
	'Date closed' => 'c.DateClosed',
	'Close code' => 'c.CloseCode',
	'Classification' => 'c.ClassificationID1'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT TOP {$iMaxRecs}
			c.BBBID,
			c.ComplaintID,
			c.BusinessID,
			BBB.NickNameCity + ', ' + BBB.State,
			c.ConsumerFirstName,
			c.ConsumerLastName,
			c.ConsumerStreetAddress,
			c.ConsumerCity,
			c.ConsumerStateProvince,
			c.ConsumerPostalCode,
			c.ConsumerEmail,
			b.BusinessName,
			b.StreetAddress,
			b.City,
			b.StateProvince,
			b.PostalCode, 
			bt.TOBID + ' ' + tblYPPA.yppa_text,
			c.DateClosed,
			cast(c.CloseCode as varchar(3)) + ' ' + tblResolutionCode.ResolutionCodeDescription,
			cast(c.ClassificationID1 as varchar(2)) + ' ' + tblClassification.ClassificationDescription,
			t.ConsumerComplaint,
			t.DesiredOutcome,
			c.ConsumerPhone,
			tblSectors.SectorDescription
		FROM BusinessComplaint c WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on c.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblResolutionCode WITH (NOLOCK) ON c.CloseCode = tblResolutionCode.ResolutionCodeID
		inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join BusinessAddress ba WITH (NOLOCK) on ba.BBBID = b.BBBID and ba.BusinessID = b.BusinessID and
			ba.IsPrimaryAddress = '1'
		inner join BusinessTOBID bt WITH (NOLOCK) on bt.BBBID = b.BBBID and bt.BusinessID = b.BusinessID and
			(bt.IsPrimaryTOBID = '1' or '{$iTOBType}' = 'All')
		left outer join tblClassification WITH (NOLOCK) on
			tblClassification.ClassificationCode = c.ClassificationID1
		left outer join tblYPPA WITH (NOLOCK) ON tblYPPA.yppa_code = bt.TOBID
		left outer join tblSectors WITH (NOLOCK) ON tblSectors.SectorCode = tblYPPA.sector_id
		left outer join BusinessComplaintText t WITH (NOLOCK) ON
			t.BBBID = c.BBBID AND t.ComplaintID = c.ComplaintID
		WHERE
			('{$iComplaintID}' > '' and c.ComplaintID = '{$iComplaintID}') or
			(
				'{$iComplaintID}' = '' and
				(
					(c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}') or
					(
						'{$iPending}' = 'yes' and c.DateClosed is null and
						(c.CloseCode is null or c.CloseCode = '' or c.CloseCode = '0')
					)
				) and
				c.ComplaintID not like 'scam%' and
				b.BusinessName LIKE '{$iBusinessName}%' and
				('{$iBusinessLastName}' = '' or
					(select top 1 count(*) from BusinessContact p WITH (NOLOCK) where
					p.BBBID = c.BBBID and p.BusinessID = c.BusinessID and
					p.LastName like '{$iBusinessLastName}%') > 0
				) and
				c.ConsumerLastName LIKE '{$iConsumerLastName}%' and
				c.ConsumerFirstName LIKE '{$iConsumerFirstName}%' and
				('' = '{$iStreet}' or ba.StreetAddressCondensed like '{$iStreet}%') and
				('' = '{$iZip}' or ba.PostalCode like '{$iZip}%') and
				('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
				('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
				('{$iTOBCode}' = '' or tblYPPA.yppa_code IN
					('" . str_replace(",", "','", $iTOBCode) . "')) and
				/*('{$iIndustry}' = '' or tblSectors.SectorCode IN
					('" . str_replace(",", "','", $iIndustry) . "')) and*/
				('{$iBBBID}' = '' or c.BBBID = '{$iBBBID}') and
				('{$iNotBBBID}' = '' or c.BBBID <> '{$iNotBBBID}') and
				('{$iConsumerBBBID}' = '' or c.ConsumerBBBID = '{$iConsumerBBBID}') and
				('{$iState}' = '' or c.ConsumerStateProvince IN
					('" . str_replace(",", "','", $iState) . "')) and
				('{$iCloseCode}' = '' or c.CloseCode IN
					('" . str_replace(",", "','", $iCloseCode) . "')) and
				(
					('{$iAB}' = '') or
					('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
					('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) and
				('{$iBusinessID}' = '' or c.BusinessID = '{$iBusinessID}' ) and
				/*('{$iText}' = '' or t.ConsumerComplaint like '%{$iText}%')*/
				('{$iText}' = '' or contains(t.ConsumerComplaint, '\"*{$iText}*\"')) and
				('{$iConsumerEmail}' = '' or c.ConsumerEmail = '{$iConsumerEmail}' ) and
				('{$iBusinessEmail}' = '' or b.Email = '{$iBusinessEmail}' )
			)
			";
	if ($iSortBy) {
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

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		if ($output_type > "") {  // excel or word
			$report->WriteHeaderRow(
				array (
					array('BBB', ''),
					array('Consumer Name', ''),
					array('Consumer Email', ''),
					array('Consumer Phone', ''),
					array('Consumer Street', ''),
					array('Consumer City', ''),
					array('Consumer State', ''),
					array('Consumer Postal Code', ''),
					array('Business Name', ''),
					array('Business Street', ''),
					array('Business City', ''),
					array('Business State', ''),
					array('Business Postal Code', ''),
					array('Business TOB', ''),
					array('Industry', ''),
					array('Date Closed', ''),
					array('Close Code', ''),
					array('Classification', ''),
					array('Narrative', ''),
					array('Outcome', '')
				)
			);
		}
		else {  // web
			$report->WriteHeaderRow(
				array (
					array('#', '', '', 'right'),
					array('BBB', $SortFields['BBB city'], '', 'left'),
					array('Consumer Name', $SortFields['Consumer last name'], '', 'left'),
					array('Consumer Address', $SortFields['Consumer city'], '', 'left'),
					array('Business Name', $SortFields['Business name'], '', 'left'),
					array('Business Address', $SortFields['Business city'], '', 'left'),
					array('Business TOB', $SortFields['TOB code'], '', 'left'),
					array('Date Closed', $SortFields['Date closed'], '', 'left'),
					array('Close Code', $SortFields['Close code'], '', 'left'),
					array('Class', $SortFields['Classification'], '', 'left'),
					array('Narrative', '', '', 'left'),
				)
			);
		}
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

			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;

			$maxlength = 70;

			// bbb city
			$bbb_city = "<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . $fields[3] . "</a>";
			if ($output_type > "") {  // excel or word
				$bbb_city = $fields[3];
			}

			// consumer email and phone - for Excel only
			if ($output_type > "") {  // excel or word
				$consumer_email = $fields[10];
				$consumer_phone = $fields[22];
			}

			// consumer name
			$consumer_name = "<a target=detail href=red_Consumer_Details.php?iBBBID=" . $fields[0] .
					"&iComplaintID=" . $fields[1] . ">" . $fields[4] . " " . $fields[5] . "</a>";
			if ($output_type > "") {  // excel or word
				$consumer_name = $fields[4] . " " . $fields[5];
				if ($consumer_email) $consumer_name .= ", " . $consumer_email;
				if ($consumer_phone) $consumer_name .= ", " . $consumer_phone; 
			}

			// consumer address		
			$consumer_zip = $fields[9];
			if (substr($fields[9],5,1) == '-') {
				$consumer_zip = substr($fields[9],0,5);
			}
			$consumer_address = $fields[7] . ' ' . $fields[8] . ' ' . $consumer_zip;
			if ($output_type > "") {  // excel or word
				$consumer_address = $fields[6] . ' ' . $fields[7] . ' ' . $fields[8] . ' ' . $consumer_zip;
			}

			// business name
			$business_name = "<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
				"&iBusinessID=" . $fields[2] .  ">" . $fields[11] . "</a>";
			if ($output_type > "") {  // excel or word
				$business_name = $fields[11];
			}
			
			// business address
			$business_zip = $fields[15];
			if (substr($fields[15],5,1) == '-') {
				$business_zip = substr($fields[15],0,5);
			}
			$business_address =  $fields[13] . ' ' . $fields[14] . ' ' . $business_zip;
			if ($output_type > "") {  // excel or word
				$business_address = $fields[12] . ' ' . $fields[13] . ' ' . $fields[14] . ' ' . $business_zip;
			}

			// narrative
			/*
			if (trim($fields[20]) == '') {
				$narrative = '';
			}
			else if ( strlen($fields[20]) > $maxlength) {
				$narrative_id = "narrative_" . $fields[0] . "_" . $fields[1];
				$narrative = "\n<a onclick=\"$('#" . $narrative_id . "').show();\" >" .
					strip_tags(substr($fields[20], 0, $maxlength)) . "..." . "</a>" .
					"<div id=" . $narrative_id . " name=" . $narrative_id . " " .
					"style='display:none; position:absolute; left:10%; width:80%; z-index:99;' " .
					"class='whiteback thickpadding lightborder'>" .
					"<p><a style='float:right;' " .
						"onclick=\"$('#" . $narrative_id . "').hide();\">" .
						"<span class='blue extrathinpadding lightborder lightgrayback'>X</span></a></p>" .
					strip_tags($fields[20]) . "</div>\n";
			}
			else {
				$narrative = strip_tags($fields[20]);
			}
			*/			
			$narrative = "<a target=detail href=red_Consumer_Details.php?iBBBID=" . $fields[0] .
				"&iComplaintID=" . $fields[1] . ">Narrative</a>";
			if ($output_type > "") {  // excel or word
				$narrative = strip_tags($fields[20]);
			}
			
			// outcome
			/*
			if (trim($fields[21]) == '') {
				$outcome = '';
			}
			else if ( strlen($fields[21]) > $maxlength) {
				$outcome_id = "outcome_" . $fields[0] . "_" . $fields[1];
				$outcome = "\n<a onclick=\"$('#" . $outcome_id . "').show();\" >" .
					strip_tags(substr($fields[21], 0, $maxlength)) . "..." . "</a>" .
					"<div id=" . $outcome_id . " name=" . $outcome_id . " " .
					"style='display:none; position:absolute; left:10%; width:80%; z-index:99;' " .
					"class='whiteback thickpadding lightborder'>" .
					"<p><a style='float:right;' " .
						"onclick=\"$('#" . $outcome_id . "').hide();\">" .
						"<span class='blue extrathinpadding lightborder lightgrayback'>X</span></a></p>" .
					strip_tags($fields[21]) . "</div>\n";
			}
			else {
				$outcome = strip_tags($fields[21]);
			}
			*/
			$outcome = "<a target=detail href=red_Consumer_Details.php?iBBBID=" . $fields[0] .
				"&iComplaintID=" . $fields[1] . ">Outcome</a>";
			if ($output_type > "") {  // excel or word
				$outcome = strip_tags($fields[21]);
			}

			if ($output_type > "") {  // excel or word
				$outcome = strip_tags($fields[21]);
			}

			if ($output_type > "") {  // excel or word
				$report->WriteReportRow(
					array (
						$bbb_city,
						$fields[4] . " " . $fields[5],
						$consumer_email,
						$consumer_phone,
						$fields[6],
						$fields[7],
						$fields[8],
						$consumer_zip,
						$business_name,
						$fields[12],
						$fields[13],
						$fields[14],
						$business_zip,
						$fields[16],
						$fields[23],
						FormatDate($fields[17]),
						$fields[18],
						$fields[19],
						$narrative,
						$outcome
					),
					''
				);
			}
			else {  // web
				$report->WriteReportRow(
					array (
						$xcount,
						$bbb_city,
						$consumer_name,
						$consumer_address,
						$business_name,
						$business_address,
						$fields[16],
						FormatDate($fields[17]),
						$fields[18],
						$fields[19],
						$narrative
					),
					'sized'
				);
			}
		}
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

	
$page->write_pagebottom();

?>