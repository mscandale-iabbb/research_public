<?php

/*
 * 
 * 10/06/14 MJS - modified to include Board members
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

	$rsraw = $conn->execute("$query");
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
				echo "<h3>" . $StateName;
				if ($Region > '') echo " (" . $Region . ")";
				echo "</h3>";
			}
	
        		echo "<p>";
			echo "<b>" . $NickNameCity . " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; " . $BBBIDFull . "</b><br>";

			/* main office */
			if ($BBBBranchID > '0') {
        			$subquery = "SELECT NickNameCity FROM BBB WITH (NOLOCK) WHERE
					BBB.BBBID = '" . $BBBID . "' AND BBB.BBBBranchID = '0'";
        			$rsub = $conn->execute("$subquery");
        			$rsub->MoveFirst();
        			while (! $rsub->EOF) {
					$MainOffice = AddApost($rsub->fields[0]);
					echo "(Branch of " . $MainOffice . ")<br>";
					$rsub->MoveNext();
				}
			}
	
			/* ceo */
       		$subquery = "SELECT PreName, FirstName, MiddleName, LastName, PostName, Title
				FROM BBBPerson WITH (NOLOCK) WHERE
				BBBID = '" . $BBBID . "' AND BBBBranchID = '" . $BBBBranchID . "' AND CEO = '1'";
       		$rsub = $conn->execute("$subquery");
       		$rsub->MoveFirst();
       		while (! $rsub->EOF) {
				$PreName = $rsub->fields[0];
				$FirstName = $rsub->fields[1];
				$MiddleName = $rsub->fields[2];
				$LastName = AddApost($rsub->fields[3]);
				$PostName = $rsub->fields[4];
				$Title = AddApost($rsub->fields[5]);
				echo $PreName . " " . $FirstName . " " . $MiddleName . " " . $LastName . "<br>";
				if (trim($Title) > '') echo $Title . "<br>";
				$rsub->MoveNext();
			}
	
			if (trim($Name)) echo $Name . "<br>";
			if (trim($Address) || trim($Address2)) echo $Address . " " . $Address2 . "<br>";
			if (trim($City) || trim($State) || trim($Zip)) echo $City . ", " . $State . " " . $Zip . "<br>";
			if (trim($MailingAddress) > '') {
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
       		$subquery = "SELECT PhoneNumber, Description, PublicLine, Unlisted, Fax, Member, CEODirect, AutoLine,
					MainFax, HearingRoom, EmergencyAutoLine
				FROM BBBPhone WITH (NOLOCK) WHERE PhoneNumber > '' AND BBBPhone.BBBID = '" . $BBBID . "' AND
				BBBPhone.BBBBranchID = '" . $BBBBranchID . "'";
       		$rsub = $conn->execute("$subquery");
       		$rsub->MoveFirst();
       		while (! $rsub->EOF) {
				//echo FormatPhone($rsub->fields[0]) . " " . $rsub->fields[1] . " &nbsp;";
				echo FormatPhone($rsub->fields[0]) . " &nbsp;";
				if ($rsub->fields[2] == 1) {echo "Public ";}
				if ($rsub->fields[3] == 1) {echo "Unlisted ";}
				if ($rsub->fields[4] == 1 && $rsub->fields[8] != 1) {echo "Fax ";}
				if ($rsub->fields[5] == 1) {echo "Member ";}
				if ($rsub->fields[6] == 1) {echo "CEO direct ";}
				if ($rsub->fields[7] == 1 && trim($rsub->fields[1]) != 'Autoline') {echo "AutoLine ";}
				if ($rsub->fields[8] == 1) {echo "MainFax ";}
				if ($rsub->fields[9] == 1) {echo "Hearing room ";}
				if ($rsub->fields[10] == 1) {echo "Emergency AutoLine ";}
				echo "&nbsp;";
				echo AddApost($rsub->fields[1]);
				echo "<br>";
				$rsub->MoveNext();
			}
	
			/* emails */
			if ($output_type == "") {
                		if (trim($GeneralEmail)) echo HideEmail($GeneralEmail) . " - General<br>";
                		if (trim($ComplaintEmail)) echo HideEmail($ComplaintEmail) . " - Complaint<br>";
                		if (trim($InquiryEmail)) echo HideEmail($InquiryEmail) . " - Inquiry<br>";
                		if (trim($SalesEmail)) echo HideEmail($SalesEmail) . " - Sales<br>";
			}
			else {
                		if (trim($GeneralEmail)) echo $GeneralEmail . " - General<br>";
                		if (trim($ComplaintEmail)) echo $ComplaintEmail . " - Complaint<br>";
                		if (trim($InquiryEmail)) echo $InquiryEmail . " - Inquiry<br>";
                		if (trim($SalesEmail)) echo $SalesEmail . " - Sales<br>";
			}
	
			/* contact persons */
       		$subquery = "SELECT Email, FirstName, LastName, Title, PhoneID FROM BBBPerson WITH (NOLOCK) WHERE
				BBBPerson.BBBID = '" . $BBBID . "' AND BBBPerson.BBBBranchID = '" . $BBBBranchID . "' /*AND
				(BoardMember = '0' OR BoardMember IS NULL)*/ ORDER BY LastName";
       		$rsub = $conn->execute("$subquery");
       		$rsub->MoveFirst();
       		while (! $rsub->EOF) {
				$Email = $rsub->fields[0];
				$FirstName = $rsub->fields[1];
				$LastName = AddApost($rsub->fields[2]);
				$Title = AddApost($rsub->fields[3]);
				$PhoneID = $rsub->fields[4];
				$pPhoneNumber = "";
       			if ($PhoneID >= 1) {
       				$subsubquery = "SELECT PhoneNumber FROM BBBPhone WITH (NOLOCK) WHERE
							BBBPhone.BBBID = '" . $BBBID . "' AND BBBPhone.BBBBranchID = '" .
							$BBBBranchID . "' AND PhoneID = '" . $PhoneID . "'";
       				$rsubsub = $conn->execute("$subsubquery");
       				$rsubsub->MoveFirst();
       				while (! $rsubsub->EOF) {
       					$pPhoneNumber = $rsubsub->fields[0];
       					$rsubsub->MoveNext();
       				}
       				if ($pPhoneNumber > "") $pPhoneNumber = FormatPhone($pPhoneNumber);
				}
				echo $FirstName . " " . $LastName . ", " . $Title . " " . $pPhoneNumber . " ";
				echo $Email;
				echo "<br>";
	
				$rsub->MoveNext();
			}

			echo "</p>";
			$last_state = $State;
		}
		echo "</div>";
	}
	$report->Close('suppress');
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>