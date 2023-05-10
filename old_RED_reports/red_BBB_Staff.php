<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 04/23/15 MJS - added column for date board member term began and ended
 * 04/23/15 MJS - changed board member term begin and end to board Chair term begin and end
 * 04/24/15 MJS - fixed bug where date values weren't being reset
 * 09/14/15 MJS - alphabetized list, added item for Data Quality contacts
 * 12/31/15 MJS - added selection for LocalReviewsContact
 * 08/25/16 MJS - aligned column headers
 * 11/07/16 MJS - added selection for TrainingCoordinator
 * 11/14/16 MJS - in excel, split name into pre/first/middle/last/post
 * 11/15/16 MJS - fixed bug in getting phone
 * 01/09/17 MJS - changed calls to define links and tabs
 * 06/21/17 MJS - cleaned up code
 * 09/21/17 MJS - changed Type label
 * 11/01/18 MJS - added option for HispanicContact
 * 03/18/19 MJS - fixed order of types
 * 08/05/19 MJS - added BBBNP
 * 01/22/20 MJS - added option for cms contacts
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iLastName = NoApost($_REQUEST['iLastName']);
$iType = NoApost($_POST['iType']);
$iBBBIDFull = NoApost($_POST['iBBBIDFull']);
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iLastName', 'Last name', $iLastName, "width:175px;");
$types = array(
	'ALL' => '',
	'Ad Review contacts' => 'AdReview',
	'AutoLine contacts' => 'AutoLine',
	'Board Chairs' => 'BoardChair',
	'Branch Managers' => 'BranchManager',
	'Brand champions' => 'BrandChampion',
	'CEOs' => 'CEO',
	'Charity contacts' => 'Charity',
	'CMS contacts' => 'CMS',
	'Complaint contacts' => 'Complaint',
	'Data quality contacts' => 'Quality',
	'DR contacts' => 'DR',
	'Foundation contacts' => 'Foundation',
	'Hispanic contacts' => 'Hispanic',
	'Investigations contacts' => 'Investigations',
	'IT contacts' => 'IT',
	'Membership contacts' => 'Membership',
	'MilitaryLine contacts' => 'Military',
	'Operations contacts' => 'Operations',
	'PR contacts' => 'PR',
	'Sales contacts' => 'Sales',
	'Secure Your ID Day contacts' => 'Secure',
	'Single Reviews contacts' => 'LocalReviewsContact',
	'Training contacts' => 'Training'
);
$input_form->AddSelectField('iType', 'Function/job type (ex. CEOs)', $iType, $types);
$input_form->AddSelectField('iBBBIDFull', 'BBB city', $iBBBIDFull, $input_form->BuildBBBCitiesArray('all', 'branches') );
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
		$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
		$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
		$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$SortFields = array(
	'Person name' => 'p.LastName,p.FirstName',
	'Title' => 'ltrim(p.Title),p.LastName,p.FirstName',
	'BBB name' => 'BBB.Name,p.LastName,p.FirstName',
	'BBB city' => 'BBB.NicknameCity',
	'Phone' => 'ph.PhoneNumber,p.LastName,p.FirstName',
	'Email' => 'p.Email,p.LastName,p.FirstName',
	'Board Chair begin' => 'p.BoardChairTermBegin',
	'Board Chair end' => 'p.BoardChairTermEnd',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT
			p.PreName,
			p.FirstName,
			p.MiddleName,
			p.LastName,
			p.PostName,
			REPLACE(p.Title,'/',' / '),
			BBB.NicknameCity + ', ' + BBB.State,
			BBB.Name,
			BBB.Address,
			BBB.Address2,
			BBB.City,
			BBB.State,
			BBB.Zip,
			BBB.MailingAddress,
			BBB.MailingAddress2,
			BBB.MailingCity,
			BBB.MailingState,
			BBB.MailingZip,
			BBB.Country,
			p.BoardMember,
			p.BoardAddress1,
			p.BoardAddress2,
			p.BoardCity,
			p.BoardState,
			p.BoardZip,
			ph.PhoneNumber,
			ph.Extension,
			TimeZone,
			p.Email,
			p.BoardChairTermBegin,
			p.BoardChairTermEnd
		from BBBPerson p WITH (NOLOCK)
		LEFT OUTER JOIN BBBPhone ph WITH (NOLOCK) ON
			ph.BBBIDFull = p.BBBIDFull and ph.PhoneID = p.PhoneID
		INNER JOIN BBB WITH (NOLOCK) ON p.BBBID = BBB.BBBID AND p.BBBBranchID = BBB.BBBBranchID
		WHERE
			(BBB.IsActive = 1 or BBB.BBBID = '2100') and
			('{$iLastName}' = '' or p.LastName LIKE '{$iLastName}%') and
			(
				('{$iType}' = '') OR
				(CEO = '1' AND '{$iType}' = 'CEO') OR
				(OperationsContact = '1' AND '{$iType}' = 'Operations') OR
				(PRContact = '1' AND '{$iType}' = 'PR') OR
				(SalesContact = '1' AND '{$iType}' = 'Sales') OR
				(MembershipContact = '1' AND '{$iType}' = 'Membership') OR
				(AdReviewContact = '1' AND '{$iType}' = 'AdReview') OR
				(CharityContact = '1' AND '{$iType}' = 'Charity') OR
				(QualityContact = '1' AND '{$iType}' = 'Quality') OR
				(AutoLineContact = '1' AND '{$iType}' = 'AutoLine') OR
				(InvestigationsContact = '1' AND '{$iType}' = 'Investigations') OR
				(HispanicContact = '1' AND '{$iType}' = 'Hispanic') OR
				(FoundationContact = '1' AND '{$iType}' = 'Foundation') OR
				(ComplaintContact = '1' AND '{$iType}' = 'Complaint') OR
				(CMSContact = '1' AND '{$iType}' = 'CMS') OR
				(BrandChampion = '1' AND '{$iType}' = 'BrandChampion') OR
				(DRContact = '1' AND '{$iType}' = 'DR') OR
				(ITContact = '1' AND '{$iType}' = 'IT') OR
				(SecureContact = '1' AND '{$iType}' = 'Secure') OR
				(MilitaryLineContact = '1' AND '{$iType}' = 'Military') OR
				(BranchManager = '1' AND '{$iType}' = 'BranchManager') OR
				(BoardChair = '1' AND '{$iType}' = 'BoardChair') OR
				(LocalReviewsContact = '1' AND '{$iType}' = 'LocalReviewsContact') OR
				(TrainingCoordinator = '1' AND '{$iType}' = 'Training')
			) AND
			('{$iCountry}' = '' OR Country = '{$iCountry}') AND
			('{$iBBBIDFull}' = '' OR p.BBBIDFull = '{$iBBBIDFull}') AND
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
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
		if ($output_type == "") {
			$report->WriteHeaderRow(
				array (
					array('Name', $SortFields['Person name'], '', 'left'),
					array('Title', $SortFields['Title'], '', 'left'),
					array('BBB', $SortFields['BBB name'], '', 'left'),
					array('Street 1', '', '', 'left'),
					array('Street 2', '', '', 'left'),
					array('City', '', '', 'left'),
					array('State', '', '', 'left'),
					array('Zip', '', '', 'left'),
					array('Country', '', '', 'left'),
					array('Email', $SortFields['Email'], '', 'left'),
					array('Phone', $SortFields['Phone'], '10%', 'left'),
					array('Board Chair Begin', $SortFields['Board Chair begin'], '', 'left'),
					array('Board Chair End', $SortFields['Board Chair end'], '', 'left'),
				)
			);
		}
		else { // excel or word
			$report->WriteHeaderRow(
				array (
					array('Pre Name'),
					array('First Name'),
					array('Middle Name'),
					array('Last Name'),
					array('Post Name'),
					array('Title'),
					array('BBB'),
					array('Street 1'),
					array('Street 2'),
					array('City'),
					array('State'),
					array('Zip'),
					array('Country'),
					array('Email'),
					array('Phone'),
					array('Board Chair Begin'),
					array('Board Chair End'),
				)
			);
		}
		foreach ($rs as $k => $fields) {

			// person name
			$name = '';
			if (trim($fields[0])) $name = AddApost($fields[0]);
			if (trim($fields[1])) $name .= ' ' . AddApost($fields[1]);
			if (trim($fields[2])) $name .= ' ' . AddApost($fields[2]);
			if (trim($fields[3])) $name .= ' ' . AddApost($fields[3]);
			if (trim($fields[4])) $name .= ', ' . AddApost($fields[4]);
			$name = trim($name);
			
			// address
			$Address = '';
			$Address2 = '';
			$City = '';
			$State = '';
			$Zip = '';
			$BoardChairTermBegin = '';
			$BoardChairTermEnd = '';
			if ($fields[19] != '1') {  // not a board member
				$Address = AddApost($fields[8]);
				$Address2 = AddApost($fields[9]);
				$City = AddApost($fields[10]);
				$State = $fields[11];
				$Zip = $fields[12];
				if (trim($fields[13])) $Address = AddApost($fields[13]); // mailing address 1
				if (trim($fields[14])) $Address2 = AddApost($fields[14]); // mailing address 2
				if (trim($fields[15])) $City = AddApost($fields[15]); // mailing city
				if (trim($fields[16])) $State = $fields[16]; // mailing state
				if (trim($fields[17])) $Zip = $fields[17]; // mailing zip
			}
			else {  // board member
				if ($fields[20]) $Address = AddApost($fields[18]);  // board address 1
				if ($fields[21]) $Address2 = AddApost($fields[19]);  // board address 2
				if ($fields[22]) $City = AddApost($fields[20]);  // board city
				if ($fields[23]) $State = $fields[21];  // board state
				if ($fields[24]) $Zip = $fields[22];  // board zip
				if ($fields[29]) $BoardChairTermBegin = FormatDate($fields[29]);  // board term date begin
				if ($fields[30]) $BoardChairTermEnd = FormatDate($fields[30]);  // board term date end
			}

			// email
			$email = '';
			if ($output_type == '' && $fields[28]) $email = "<a href=mailto:" . $fields[28] . ">Email</a>";
			else $email = $fields[28];

			if ($output_type == "") {
				$report->WriteReportRow(
					array (
						$name,
						AddApost($fields[5]),  // title
						AddApost($fields[7]),  // bbb
						$Address,
						$Address2,
						$City,
						$State,
						$Zip,
						$fields[18],  // country
						$email,
						FormatPhone($fields[25]) . ' ' . $fields[26] . ' ' . $fields[27],
						$BoardChairTermBegin,
						$BoardChairTermEnd,
					)
				);
			}
			else { // excel or word
				$report->WriteReportRow(
					array (
						$fields[0],
						$fields[1],
						$fields[2],
						$fields[3],
						$fields[4],
						AddApost($fields[5]),  // title
						AddApost($fields[7]),  // bbb
						$Address,
						$Address2,
						$City,
						$State,
						$Zip,
						$fields[18],  // country
						$email,
						FormatPhone($fields[25]) . ' ' . $fields[26] . ' ' . $fields[27],
						$BoardChairTermBegin,
						$BoardChairTermEnd,
					)
				);
			}
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>