<?php

/*
 * 04/07/16 MJS - new file
 * 04/07/16 MJS - removed CBBB-only access
 * 04/12/16 MJS - re-wrote for new schema
 * 04/14/16 MJS - fixed bug with url
 * 04/14/16 MJS - re-wrote query to pull ones with no postal code
 * 05/09/16 MJS - added columns
 * 08/25/16 MJS - aligned column headers
 * 09/27/16 MJS - added checkbox column
 * 09/29/16 MJS - added monthly option
 * 11/10/16 MJS - changed REQUEST to POST
 * 07/26/17 MJS - cleaned code
 * 02/07/18 MJS - removed paging
 * 02/09/18 MJS - increased iMaxRecs
 * 03/22/18 MJS - excluded out of business
 * 04/25/18 MJS - added option for all BBBs, column for BBB
 * 04/25/18 MJS - added option for Scam Type, column for Scam Type
 * 04/30/18 MJS - added drop-down list of Scam Types
 * 07/25/18 MJS - added option for business id
 * 07/27/18 MJS - fixed checkbox field when export to excel
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


// input

$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iBBBID = NoApost($_POST['iBBBID']);
$iBusinessID = Numeric2($_POST['iBusinessID']);
//if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
//if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
//$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iScamType = NoApost($_POST['iScamType']);
$iMaxRecs = 9999;
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'c.ComplaintID';
$iShowSource = $_POST['iShowSource'];

$SortFields = array(
	'Scam ID' => 'c.ComplaintID',
	'BBB' => 'BBB.NicknameCity',
	'Business name' => 'c.BusinessName',
	'Business street' => 'c.BusinessStreetAddress',
	'Business city' => 'c.BusinessCity',
	'Business state' => 'c.BusinessStateProvince',
	'Business postal code' => 'c.BusinessPostalCode',
	'Business phone' => 'c.BusinessPhone',
	'Date closed' => 'DateClosed',
	'Possible business match' => 'b.BusinessName',
	'Possible business match ID' => 'b.BusinessID',
	'Possible business match rating' => 'b.BBBRatingGrade',
	'Possible business match AB' => 'AB',
);

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Scams received from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;" );
//$input_form->AddTextField('iScamType', 'Scam type', $iScamType, '');
$scamtypes = array(
	'All' => '',
	'Advance Fee Loan' => 'Advance Fee Loan',
	'Business Email Compromise' => 'Business Email Compromise',
	'Charity' => 'Charity',
	'Counterfeit Product' => 'Counterfeit Product',
	'Credit Cards' => 'Credit Cards',
	'Credit Repair/Debt Relief' => 'Credit Repair/Debt Relief',
	'Debt Collections' => 'Debt Collections',
	'Employment' => 'Employment',
	'Fake Check/Money Order' => 'Fake Check/Money Order',
	'Fake Invoice' => 'Fake Invoice',
	'Family/Friend Emergency' => 'Family/Friend Emergency',
	'Government Grant' => 'Government Grant',
	'Healthcare/Medicaid/Medicare' => 'Healthcare/Medicaid/Medicare',
	'Home Improvement' => 'Home Improvement',
	'Identity Theft' => 'Identity Theft',
	'Investment' => 'Investment',
	'Moving' => 'Moving',
	'Nigerian/Foreign Money Exchange' => 'Nigerian/Foreign Money Exchange',
	'Online Purchase' => 'Online Purchase',
	'Other' => 'Other',
	'Phishing' => 'Phishing',
	'Rental' => 'Rental',
	'Romance' => 'Romance',
	'Scholarship' => 'Scholarship',
	'Sweepstakes/Lottery/Prizes' => 'Sweepstakes/Lottery/Prizes',
	'Tax Collection' => 'Tax Collection',
	'Tech Support' => 'Tech Support',
	'Travel/Vacations' => 'Travel/Vacations',
	'Utility' => 'Utility',
	'Yellow Pages/Directories' => 'Yellow Pages/Directories'
);
$input_form->AddSelectField('iScamType', 'Scam type', $iScamType, $scamtypes);
$input_form->AddSortOptions($iSortBy, $SortFields);
//$input_form->AddPagingOption();
$input_form->AddScheduledTaskOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$column_OOB = "OutOfBusinessTypeId";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$column_OOB = "OutOfBusinessStatusTypeId";
	}

	$query = "
		SELECT TOP {$iMaxRecs}
			c.BBBID,
			c.ComplaintID,
			c.BusinessName,
			c.BusinessStreetAddress,
			c.BusinessCity,
			c.BusinessStateProvince,
			c.BusinessPostalCode,
			c.BusinessPhone,
			c.DateClosed,
			b.BusinessName,
			b.ReportURL,
			b.BusinessID,
			b.BBBRatingGrade,
			case when b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			ch.ComplaintID,
			BBB.NicknameCity,
			SUBSTRING(
				SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType\": \"',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType\": \"',t.DesiredOutcome)),
				1,
				CHARINDEX('\"',
					SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType\": \"',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType\": \"',t.DesiredOutcome)))
					- 1
			)
		FROM ScamBusinessMatch m WITH (NOLOCK)
		INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
		LEFT OUTER JOIN BusinessComplaintText t WITH (NOLOCK) ON t.BBBID = m.BBBID and t.ComplaintID = m.ComplaintID
		INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
		INNER JOIN BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = '0'
		LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		WHERE
			('{$iBBBID}' = '' or m.MatchBBBID = '{$iBBBID}') and
			('{$iBusinessID}' = '' or b.BusinessID = '{$iBusinessID}' ) and
			('{$iScamType}' = '' or '{$iScamType}' =
				SUBSTRING(
					SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType\": \"',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType\": \"',t.DesiredOutcome)),
					1,
					CHARINDEX('\"',
						SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType\": \"',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType\": \"',t.DesiredOutcome)))
						- 1
				)
			) and
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			c.ComplaintID like 'scam%' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			b.IsReportable = '1'
		";
	if ($iSortBy) $query .= " ORDER BY " . $iSortBy;

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
				array('Reviewed'),
				array('BBB', $SortFields['BBB'], '', 'left'),
				array('Scam ID', $SortFields['ID'], '', 'left'),
				array('Type', '', '', 'left'),
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('Business Street', $SortFields['Business street'], '', 'left'),
				array('Business City', $SortFields['Business city'], '', 'left'),
				array('State', $SortFields['Business state'], '', 'left'),
				array('Zip', $SortFields['Business postal code'], '', 'left'),
				array('Phone', $SortFields['Business phone'], '', 'left'),
				array('Closed', $SortFields['Date closed'], '', 'left'),
				array('Possible Business Match', $SortFields['Possible business match'], '', 'left'),
				array('BID', $SortFields['Possible business match ID'], '', 'left'),
				array('Rating', $SortFields['Possible business match rating'], '', 'left'),
				array('AB', $SortFields['Possible business match AB'], '', 'left'),
			)
		);

		$xcount = 0;

		/*
		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_POST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;
		*/

		foreach ($rs as $k => $fields) {
			$xcount++;

			/*
			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;
			*/

			$oID = "<a target=detail
				href=red_Consumer_Details.php?iBBBID={$fields[0]}&iComplaintID={$fields[1]}>{$fields[1]}</a>";
			$href = $fields[10];
			if (substr($href,0,4) != "http") $href = "http://" . $href;
			$link = "<a target=_new href={$href}>{$fields[9]}</a>";

			if ($fields[14]) $checked = 'CHECKED';
			else $checked = '';

			if ($output_type > "") {  // excel or word
				if ($fields[14]) $checkboxfield = "Checked";
				else $checkboxfield = "";
			}
			else {
				$checkboxfield = "<input id=iChecked{$fields[1]} type=checkbox {$checked} " .
					"onclick=\"SaveChange('{$fields[1]}', this.checked)\" />";
			}

			$report->WriteReportRow(
				array (
					$checkboxfield,
					$fields[15],
					$oID,
					$fields[16],
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
					FormatDate($fields[8]),
					$link,
					"<span class=gray>{$fields[11]}</span>",
					"<span class=gray>{$fields[12]}</span>",
					"<span class=gray>{$fields[13]}</span>",
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

if ($_POST['output_type'] == '') {
	$page->AddHTML(
		"<script>" .
		"function SaveChange(complaintid, checked) { " .
		"	/*alert(complaintid + checked);*/ " .
		"	$.ajax({ " .
		"		url: 'red_Businesses_With_Scam_Tracker_Scams-db.php', " .
		"		type: 'POST', " .
		"		cache: false, " .
		"		async: true, " .
		"		datatype: 'jsonp', " .
		"		jsonp: 'jsoncallback', " .
		"		data: { iComplaintID: complaintid, iChecked: checked }, " .
		"		success: function(data) { " .
		"			alert(data); " .
		"		} " .
		"	}); " .
		"}" .
		"</script>"
	);
}

$page->write_pagebottom();

?>