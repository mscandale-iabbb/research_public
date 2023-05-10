<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 01/28/20 MJS - refactored sql
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBIDFull = NoApost($_POST['iBBBIDFull']);
$iState = NoApost($_POST['iState']);
$iCountry = $_POST['iCountry'];
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBIDFull', 'BBB city', $iBBBIDFull, $input_form->BuildBBBCitiesArray('all', '') );
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddExportOptions('word');
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
        $query = "
	        SELECT BBB.BBBIDFull, NickNameCity, BBB.Name, Address, Address2, City, State, Zip,
				MailingAddress, MailingAddress2, MailingCity, MailingState, MailingZip,
				GeneralEmail, ComplaintEmail, InquiryEmail, SalesEmail, BBB.Region, s.StateName,
				BBB.BBBID, BBB.BBBBranchID
			FROM BBB WITH (NOLOCK)
			INNER JOIN tblStates s WITH (NOLOCK) ON s.StateAbbreviation = BBB.State
			WHERE ('{$iCountry}' = '' OR BBB.Country = '{$iCountry}') and
				NickNameCity != '' AND IsActive = 1 and
				('{$iState}' = '' or State IN ('" . str_replace(",", "','", $iState) . "')) and
				('{$iBBBIDFull}' = '' OR BBB.BBBIDFull = '{$iBBBIDFull}')
			ORDER BY s.StateName, NickNameCity
			";
	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		echo "<div class='inner_section'>";
		foreach ($rs as $k => $fields) {
			$BBBIDFull = $fields[0];
			$NickNameCity = AddApost($fields[1]);
			$Name = AddApost($fields[2]);
			$Address = AddApost($fields[3]);
			$Address2 = AddApost($fields[4]);
			$City = AddApost($fields[5]);
			$State = $fields[6];
			$Zip = $fields[7];
			$MailingAddress = AddApost($fields[8]);
			$MailingAddress2 = AddApost($fields[9]);
			$MailingCity = AddApost($fields[10]);
			$MailingState = $fields[11];
			$MailingZip = $fields[12];
			$GeneralEmail = $fields[13];
			$ComplaintEmail = $fields[14];
			$InquiryEmail = $fields[15];
			$SalesEmail = $fields[16];
			$Region = $fields[17];
			$StateName = strtoupper($fields[18]);
			$BBBID = $fields[19];
			$BBBBranchID = $fields[20];

			if ($State != $last_state) {
				echo "<p>&nbsp; ";
				echo "<h3>" . $StateName . "</h3>";
			}
	
			echo "<p>";
			echo "<b><i>Better Business Bureau</i></b><br>";
	
			if (trim($Address) || trim($Address2)) echo $Address . " " . $Address2 . "<br>";
			if (trim($City) || trim($State) || trim($Zip)) echo $City . ", " . $State . " " . $Zip . "<br>";
			if (trim($MailingAddress)) {
				echo $MailingAddress . " " . $MailingAddress2 . "<br>";
				if (trim($MailingCity) || trim($MailingState) || trim($MailingZip)) {
					echo $MailingCity . ", " . $MailingState . " " . $MailingZip . "<br>";
				}
			}

			/* urls */
			$subquery = "SELECT Subdomains FROM CMSCORE.dbo.bbbInfo WITH (NOLOCK) WHERE LegacyID = '{$BBBID}'";
			$r2 = $conn->execute($subquery);
			$r2->MoveFirst();
			while (! $r2->EOF) {
   				$subdomainstring = $r2->fields[0];
   				$r2->MoveNext();
			}
			$subdomains = ParseBBBDomains($subdomainstring, false);
			$Domain = $subdomains[0];
			echo "<a target=new href=http://" . $Domain . ">www." . $Domain . "</a><br>";

			/* phones */
			$subquery = "SELECT PhoneNumber, Description, Fax FROM BBBPhone WITH (NOLOCK) WHERE
				BBBPhone.BBBID = '" . $BBBID . "' AND BBBPhone.BBBBranchID = '" . $BBBBranchID . "' AND
				(PublicLine = '1' OR MainFax = '1') AND PhoneNumber != ''";
			$rsub = $conn->execute("$subquery");
			$rsub->MoveFirst();
			while (! $rsub->EOF) {
				echo FormatPhone($rsub->fields[0]) . " " . $rsub->fields[1];
				if ($rsub->fields[2] == '1') echo " Fax";
				echo "<br>";
				$rsub->MoveNext();
			}
	
			/* emails */
			if ($GeneralEmail != '') echo $GeneralEmail . "<br/>";

			echo "</p>";
			$last_state = $State;
		}
		echo "</div>";
	}
	$report->Close('suppress');
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>