<?php

/*
 * 07/11/18 MJS - new file
 * 07/16/18 MJS - fixed label for ID
 * 07/25/18 MJS - added fields
 * 07/27/18 MJS - added input option for tier
 * 07/30/18 MJS - added filter for rating
 * 08/03/18 MJS - rewrote for new schema
 * 08/03/18 MJS - added columns, removed columns
 * 08/03/18 MJS - reordered input options, added input options
 * 08/17/18 MJS - removed column Experience Type
 * 09/20/18 MJS - added F Score column
 * 11/26/18 MJS - added filter for F Score
 * 01/03/19 MJS - added BID and CID to Excel output
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
$iBusinessName = NoApost($_REQUEST['iBusinessName']);
$iConsumerLastName = NoApost($_REQUEST['iConsumerLastName']);
$iConsumerFirstName = NoApost($_REQUEST['iConsumerFirstName']);
$iBusinessLastName = NoApost($_REQUEST['iBusinessLastName']);
$iNAICS = NoApost($_POST['iNAICS']);
$iTOB = NoApost($_REQUEST['iTOB']);
$iTOBCode = NoApost($_REQUEST['iTOBCode']);
$iTOBType = NoApost($_REQUEST['iTOBType']);
$iPublished = NoApost($_REQUEST['iPublished']);
$iAB = NoApost($_REQUEST['iAB']);
$iFScoreNonZero = NoApost($_REQUEST['iFScoreNonZero']);
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iNotBBBID = Numeric2($_REQUEST['iNotBBBID']);
$iCloseCode = NoApost($_POST['iCloseCode']);
$iText = NoApost(NoQuote($_REQUEST['iText']));
$iCustomerReviewID = Numeric2($_POST['iCustomerReviewID']);
$iBusinessID = Numeric2($_POST['iBusinessID']);
$iBusinessEmail = NoApost($_REQUEST['iBusinessEmail']);
$iConsumerEmail = NoApost($_REQUEST['iConsumerEmail']);
$iConsumerPhone = NoApost($_REQUEST['iConsumerPhone']);
$iConsumerIPAddress = NoApost($_REQUEST['iConsumerIPAddress']);
$iStreet = NoPunc2($_REQUEST['iStreet']);
$iZip = NoApost($_REQUEST['iZip']);
$iTier = $_POST['iTier'];
$iRating = NoApost($_REQUEST['iRating']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Dates received',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iPublished','Published status',$iPublished, array('Both' => '', 'Published' => '1', 'Unpublished' => '0') );
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddSelectField('iNotBBBID', 'Not processed by BBB', $iNotBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddTextField('iConsumerLastName', 'Consumer last name', $iConsumerLastName, "width:175px;");
$input_form->AddTextField('iConsumerFirstName', 'Consumer first name', $iConsumerFirstName, "width:175px;");
$input_form->AddTextField('iConsumerEmail', 'Consumer email', $iConsumerEmail, "width:20%;");
$input_form->AddTextField('iConsumerPhone', 'Consumer phone', $iConsumerPhone, "width:20%;");
$input_form->AddTextField('iConsumerIPAddress', 'Consumer IP address', $iConsumerIPAddress, "width:20%;");
$input_form->AddTextField('iCustomerReviewID', 'Customer review ID', $iCustomerReviewID, "width:100px;" );
$input_form->AddTextField('iText', 'Customer narrative contains word or phrase', $iText, "width:20%;");
$input_form->AddTextField('iBusinessName', 'Business name', $iBusinessName, "width:175px;");
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;" );
//$input_form->AddTextField('iStreet', 'Business street address', $iStreet, "width:20%;");
$input_form->AddTextField('iZip', 'Business postal code', $iZip, "width:5%;");
$input_form->AddTextField('iBusinessEmail', 'Business email', $iBusinessEmail, "width:20%;");
$input_form->AddTextField('iBusinessLastName', 'Business person last name', $iBusinessLastName, "width:175px;");
$input_form->AddSelectField('iAB','Business AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddMultipleSelectField('iRating', 'Business rating', $iRating,
		$input_form->BuildRatingsArray('all'), '', '', '', 'width:300px');
$input_form->AddSelectField('iNAICS', 'Business industry', $iNAICS, $input_form->BuildNAICSGroupArray() );
$input_form->AddMultipleSelectField('iTOBCode', 'Business TOB code', $iTOBCode, $input_form->BuildTOBsArray(),
		'width:300px;', '', '', 'width:375px;');
$input_form->AddTextField('iTOB','Business TOB contains word/phrase',$iTOB);
$input_form->AddRadio('iTOBType', 'Business TOB type', $iTOBType, array('Primary' => '', 'All' => 'All'));
$input_form->AddMultipleSelectField('iTier', 'Business TOB tier', $iTier,
	$input_form->BuildTOBTiersArray(''), '', '', '', 'width:100px');
$input_form->AddRadio('iFScoreNonZero', 'F Score greater than 0', $iFScoreNonZero, array('Yes' => 'Yes', 'No' => ''));
$SortFields = array(
	'Consumer last name' => 'cr.ConsumerLastName',
	'Consumer first name' => 'cr.ConsumerFirstName',
	'Consumer email' => 'cr.ConsumerEmail',
	'BBB city' => 'NicknameCity',
	'Business name' => 'b.BusinessName',
	'Business postal code' => 'b.PostalCode',
	'Consumer postal code' => 'cr.ConsumerPostalCode',
	/*
	'Business street address' => 'b.StreetAddress',
	'Business city' => 'b.City',
	'Business state' => 'b.StateProvince',
	*/
	'Business postal code' => 'b.PostalCode',
	'TOB code' => 'bt.TOBID',
	'TOB description' => 'tblYPPA.yppa_text',
	'AB' => 'b.IsBBBAccredited',
	'Rating' => 'b.BBBRatingGrade',
	'Stars' => 'cr.Stars',
	'Verified' => 'cr.IsCertified',
	'Date received' => 'cr.DateReceived'
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
			b.BBBID,
			cr.CustomerReviewID,
			b.BusinessID,
			BBB.NickNameCity,
			ltrim(cr.ConsumerFirstName),
			ltrim(cr.ConsumerLastName),
			cr.ConsumerPostalCode,
			ltrim(cr.ConsumerEmail),
			b.BusinessName,
			b.StreetAddress,
			b.City,
			b.StateProvince,
			b.PostalCode, 
			bt.TOBID + ' ' + tblYPPA.yppa_text,
			cr.DateReceived,
			ltrim(t.CustomerReviewText),
			'',
			case when cr.IsCertified = '1' then 'Yes' else 'No' end,
			case when b.IsBBBAccredited = '1' then 'Yes' else 'No' end,
			b.BBBRatingGrade,
			tblTiers.TierDescription,

			cr.DatePublished,
			case when cr.IsPublished = '1' then 'Yes' else 'No' end,
			cr.Stars,
			CDW.dbo.getRawPhone(cr.ConsumerPhone),
			cr.ConsumerIPAddress,
			dbo.getFakeReviewProbability(cr.BBBID, cr.CustomerReviewID)
		FROM BusinessCustomerReview cr
		INNER JOIN Business b WITH (NOLOCK) on b.BBBID = cr.BBBID and b.BusinessID = cr.BusinessID
		LEFT OUTER JOIN BusinessCustomerReviewText t WITH (NOLOCK) on t.BBBID = cr.BBBID and t.CustomerReviewID = cr.CustomerReviewID
		inner join BBB WITH (NOLOCK) on b.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		inner join BusinessAddress ba WITH (NOLOCK) on ba.BBBID = b.BBBID and ba.BusinessID = b.BusinessID and
			ba.IsPrimaryAddress = '1'
		inner join BusinessTOBID bt WITH (NOLOCK) on bt.BBBID = b.BBBID and bt.BusinessID = b.BusinessID and
			(bt.IsPrimaryTOBID = '1' or '{$iTOBType}' = 'All')
		left outer join tblYPPA WITH (NOLOCK) ON tblYPPA.yppa_code = bt.TOBID
		left outer join tblTiers WITH (NOLOCK) ON tblYPPA.Tier = tblTiers.TierNumber
		WHERE
			('{$iCustomerReviewID}' > '' and cr.CustomerReviewID = '{$iCustomerReviewID}') or
			(
				'{$iCustomerReviewID}' = '' and
				cr.DateReceived >= '{$iDateFrom}' and
				cr.DateReceived <= '{$iDateTo}' and
				(
					'{$iFScoreNonZero}' = '' or
					dbo.getFakeReviewProbability(cr.BBBID, cr.CustomerReviewID) > 0.00
				) and
				(
					('{$iPublished}' = '') or
					('{$iPublished}' = '1' and cr.IsPublished = '1') or
					('{$iPublished}' = '0' and (cr.IsPublished = '0' or cr.IsPublished is null))
				) and
				('{$iBusinessID}' = '' or b.BusinessID = '{$iBusinessID}' ) and
				b.BusinessName LIKE '{$iBusinessName}%' and
				('{$iBusinessLastName}' = '' or
					(select top 1 count(*) from BusinessContact p WITH (NOLOCK) where
					p.BBBID = b.BBBID and p.BusinessID = b.BusinessID and
					p.LastName like '{$iBusinessLastName}%') > 0
				) and
				cr.ConsumerLastName LIKE '{$iConsumerLastName}%' and
				cr.ConsumerFirstName LIKE '{$iConsumerFirstName}%' and
				('{$iConsumerEmail}' = '' or cr.ConsumerEmail = '{$iConsumerEmail}' ) and
				('{$iConsumerPhone}' = '' or CDW.dbo.getRawPhone(cr.ConsumerPhone) = '{$iConsumerPhone}' ) and
				('{$iConsumerIPAddress}' = '' or cr.ConsumerIPAddress = '{$iConsumerIPAddress}' ) and
				/*('' = '{$iStreet}' or ba.StreetAddressCondensed like '{$iStreet}%') and*/
				('' = '{$iZip}' or ba.PostalCode like '{$iZip}%') and
				('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
				('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
				('{$iTOBCode}' = '' or tblYPPA.yppa_code IN ('" . str_replace(",", "','", $iTOBCode) . "')) and
				('{$iTier}' = '' or tblYPPA.Tier IN ('" . str_replace(",", "','", $iTier) . "')) and
				('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
				('{$iNotBBBID}' = '' or b.BBBID <> '{$iNotBBBID}') and
				(
					('{$iAB}' = '') or
					('{$iAB}' = '1' and b.IsBBBAccredited = 1) or
					('{$iAB}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) and
				('{$iRating}' = '' or b.BBBRatingGrade IN ('" . str_replace(",", "','", $iRating) . "')) and
				('{$iText}' = '' or contains(t.CustomerReviewText, '\"*{$iText}*\"')) and
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
					array('CID', ''),
					array('Consumer Name', ''),
					array('Consumer Postal Code', ''),
					array('Consumer Email', ''),
					array('Consumer Phone', ''),
					array('Consumer IP Address', ''),
					array('BID', ''),
					array('Business Name', ''),
					array('Business Street', ''),
					array('Business City', ''),
					array('Business State', ''),
					array('Business Postal Code', ''),
					array('Business TOB', ''),
					array('Tier', ''),
					array('AB', ''),
					array('Rtg', ''),
					array('Received', ''),
					array('Stars', ''),
					array('Verified', ''),
					array('Published', ''),
					array('F Score', ''),
					array('Text', ''),
				)
			);
		}
		else {  // web
			$report->WriteHeaderRow(
				array (
					array('#', '', '', 'right'),
					array('BBB', $SortFields['BBB city'], '', 'left'),
					array('Consumer Name', $SortFields['Consumer last name'], '', 'left'),
					array('Consumer Zip', $SortFields['Consumer postal code'], '', 'left'),
					array('Consumer Email', $SortFields['Consumer email'], '', 'left'),
					array('Business Name', $SortFields['Business name'], '', 'left'),
					array('Business Zip', $SortFields['Business postalcode'], '', 'left'),
					array('Business TOB', $SortFields['TOB code'], '', 'left'),
					array('AB', $SortFields['AB'], '', 'left'),
					array('Rtg', $SortFields['Rating'], '', 'left'),
					array('Recvd', $SortFields['Date received'], '', 'left'),
					array('Stars', $SortFields['Stars'], '', 'left'),
					array('Verif', $SortFields['Verified'], '', 'left'),
					array('Publ', $SortFields['Published'], '', 'left'),
					array('F Score', '', '', 'left'),
					array('Text', '', '', 'left'),
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
			$bbb_city = "<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] . ">" . $fields[3] . "</a>";
			if ($output_type > "") {  // excel or word
				$bbb_city = $fields[3];
			}

			// consumer email
			$consumer_email = $fields[7];

			// consumer name
			$consumer_name = "<a target=detail href=red_Customer_Review_Details.php?iBBBID=" . $fields[0] .
					"&iCustomerReviewID=" . $fields[1] . ">" . $fields[4] . " " . $fields[5] . "</a>";
			if ($output_type > "") {  // excel or word
				$consumer_name = $fields[4] . " " . $fields[5];
			}

			// consumer zip
			$consumer_zip = $fields[6];
			if (substr($fields[6],5,1) == '-') {
				$consumer_zip = substr($fields[6],0,5);
			}

			// business name
			$business_name = "<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
				"&iBusinessID=" . $fields[2] .  ">" . $fields[8] . "</a>";
			if ($output_type > "") {  // excel or word
				$business_name = $fields[8];
			}
			
			// business address
			$business_zip = $fields[12];
			if (substr($fields[12],5,1) == '-') {
				$business_zip = substr($fields[12],0,5);
			}
			/*
			$business_address =  $fields[9] . ' ' . $fields[10] . ' ' . $fields[11] . ' ' . $business_zip;
			if ($output_type > "") {  // excel or word
				$business_address = $fields[9] . ' ' . $fields[10] . ' ' . $fields[11] . ' ' . $business_zip;
			}
			*/

			$narrative = "<a target=detail href=red_Customer_Review_Details.php?iBBBID=" . $fields[0] .
				"&iCustomerReviewID=" . $fields[1] . ">Text</a>";
			if ($output_type > "") {  // excel or word
				$narrative = strip_tags($fields[15]);
			}

			if ($output_type > "") {  // excel or word
				$report->WriteReportRow(
					array (
						$bbb_city,
						$fields[1],
						$consumer_name,
						$consumer_zip,
						$consumer_email,
						$fields[24],
						$fields[25],
						$fields[2],
						$business_name,
						$fields[9],
						$fields[10],
						$fields[11],
						$business_zip,
						$fields[13],
						$fields[20],
						$fields[18],
						$fields[19],
						FormatDate($fields[14]),
						/*$fields[16],*/
						$fields[23],
						$fields[17],
						$fields[22],
						substr($fields[26],0,4),
						$narrative
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
						$consumer_zip,
						$consumer_email,
						$business_name,
						$business_zip,
						$fields[13],
						$fields[18],
						$fields[19],
						FormatDate($fields[14]),
						/*$fields[16],*/
						$fields[23],
						$fields[17],
						$fields[22],
						substr($fields[26],0,4),
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