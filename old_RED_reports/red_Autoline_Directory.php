<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 01/03/17 MJS - changed calls to define links and tabs
 * 01/28/20 MJS - updated sql
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
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
			WHERE
				AutoLineSite = '1' and
				('" . $iCountry . "' = '' OR BBB.Country = '" . $iCountry . "') and
				NickNameCity != '' AND IsActive = 1 and
				('" . $iState . "' = '' or State IN ('" . str_replace(",", "','", $iState) . "')) and
				('" . $iBBBIDFull . "' = '' OR BBB.BBBIDFull = '" . $iBBBIDFull . "')
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
			$MailingAddress = trim(AddApost($fields[8]));
			$MailingAddress2 = trim(AddApost($fields[9]));
			$MailingCity = trim(AddApost($fields[10]));
			$MailingState = trim($fields[11]);
			$MailingZip = trim($fields[12]);
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
			echo "<center>" . $BBBIDFull . "</center>";

			/* ceo */
       		$subquery = "SELECT PreName, FirstName, MiddleName, LastName, PostName, Title
				FROM BBBPerson WITH (NOLOCK) WHERE
				BBBIDFull = '" . $BBBIDFull . "' AND (CEO = '1' OR AutolineContact = '1')";
       		$rsub = $conn->execute("$subquery");
       		$rsub->MoveFirst();
       		while (! $rsub->EOF) {
				$PreName = $rsub->fields[0];
				$FirstName = $rsub->fields[1];
				$MiddleName = $rsub->fields[2];
				$LastName = AddApost($rsub->fields[3]);
				$PostName = $rsub->fields[4];
				$Title = trim(AddApost($rsub->fields[5]));
				echo $PreName . " " . $FirstName . " " . $MiddleName . " " . $LastName . "<br>";
				if ($Title) echo $Title . "<br/>";
				$rsub->MoveNext();
			}
	
			if ($Address) echo $Address . " " . $Address2 . "<br>";
			if ($City || $State) echo $City . ", " . $State . " " . $Zip . "<br>";
			if ($MailingAddress) echo $MailingAddress . " " . $MailingAddress2 . "<br>";
			if ($MailingCity || $MailingState) echo $MailingCity . ", " . $MailingState . " " .
				$MailingZip . "<br/>";
	
			/* phones */
       		$subquery = "SELECT PhoneNumber, Description, Fax, AutoLine, EmergencyAutoLine,
					HearingRoom, Unlisted, PublicLine, Extension
				FROM BBBPhone WITH (NOLOCK) WHERE
					LEN(PhoneNumber) > 0 AND BBBIDFull = '" . $BBBIDFull . "' AND
					(AutoLine = '1' OR EmergencyAutoLine = '1' OR HearingRoom = '1' OR
					Unlisted = '1' OR PublicLine = '1')";
       		$rsub = $conn->execute("$subquery");
       		$rsub->MoveFirst();
       		while (! $rsub->EOF) {
				echo FormatPhone($rsub->fields[0]);
				if ($rsub->fields[8] > '') echo " Ext: " . $rsub->fields[8];
				if ($rsub->fields[2] == '1') echo ", Fax";
				if ($rsub->fields[3] == '1') echo ", AutoLine";
				if ($rsub->fields[4] == '1') echo ", Emergency AutoLine";
				if ($rsub->fields[5] == '1') echo ", Hearing Room";
				if ($rsub->fields[6] == '1') echo ", Unlisted";
				if ($rsub->fields[7] == '1') echo ", Public";
				if ($rsub->fields[1] > '') echo ", Desc: " . AddApost($rsub->fields[1]);
				echo "<br/>";
				$rsub->MoveNext();
			}
	
			/* contact persons */
       		$subquery = "SELECT Email, FirstName, LastName, Title, PhoneID, FaxID FROM BBBPerson WITH (NOLOCK) WHERE
				BBBIDFull = '" . $BBBIDFull . "' AND (CEO = '1' OR AutoLineContact = '1') AND
				(LastName > '' OR FirstName > '' OR Title > '')
				ORDER BY LastName";
       		$rsub = $conn->execute("$subquery");
       		$rsub->MoveFirst();
       		while (! $rsub->EOF) {
				$Email = $rsub->fields[0];
				$FirstName = $rsub->fields[1];
				$LastName = AddApost($rsub->fields[2]);
				$Title = AddApost($rsub->fields[3]);
				$PhoneID = $rsub->fields[4];
				$FaxID = $rsub->fields[5];
				$pPhoneNumber = "";
				$pFaxNumber = "";
       			if ($PhoneID >= 1) {
       				$subsubquery = "SELECT PhoneNumber FROM BBBPhone WITH (NOLOCK) WHERE
						BBBIDFull = '" . $BBBIDFull . "' AND PhoneID = '" . $PhoneID . "'";
        				$rsubsub = $conn->execute("$subsubquery");
        				$rsubsub->MoveFirst();
       				while (! $rsubsub->EOF) { $pPhoneNumber = $rsubsub->fields[0]; $rsubsub->MoveNext(); }
       				if ($pPhoneNumber > "") { $pPhoneNumber = FormatPhone($pPhoneNumber); }
				}
       			if ($FaxID >= 1) {
       				$subsubquery = "SELECT PhoneNumber FROM BBBPhone WITH (NOLOCK) WHERE
						BBBIDFull = '" . $BBBIDFull . "' AND PhoneID = '" . $FaxID . "'";
       				$rsubsub = $conn->execute("$subsubquery");
       				$rsubsub->MoveFirst();
       				while (! $rsubsub->EOF) {
						$pFaxNumber = $rsubsub->fields[0];
						$rsubsub->MoveNext();
					}
       				if ($pFaxNumber) $pFaxNumber = FormatPhone($pFaxNumber);
				}
				echo $FirstName . " " . $LastName . ", " . $Title . " " . $pPhoneNumber;
				if ($pFaxNumber > "") echo ", Fax: " . $pFaxNumber;
				echo " ";
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
