<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 08/24/15 MJS - fixed bug in search criteria
 * 03/08/16 MJS - added No Results message
 * 06/23/16 MJS - added notes column and notes editing button
 * 07/11/16 MJS - added attachments
 * 08/24/16 MJS - aligned column headers
 * 11/13/18 MJS - changed file path for viewing
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBusinessName = $_REQUEST['iBusinessName'];
$iSearchMethod = $_REQUEST['iSearchMethod'];
if (! $iSearchMethod) $iSearchMethod = 'begins';

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

$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];

// letters a to z
$browse_letters = "<br/>";
for ($x = 65; $x <= 90; $x = $x + 1) {
	$ltr = chr($x);
	$browse_letters .= "<a onclick=\"form1.iBusinessName.value='" . $ltr . "'; " .
		"form1.iSearchMethod.value='begins'; Slide('popup_wheel'); ShowWaitMessage(); " .
		"form1.submit();\" >" . $ltr . "</a> &nbsp;";
}

$input_form = new input_form($conn);
$input_form->AddTextField('iBusinessName', 'Business name', $iBusinessName, "width:200px;");
$input_form->AddNote($browse_letters);
$input_form->AddRadio('iSearchMethod', 'Search method', $iSearchMethod,
	array('Begins with' => 'begins', 'Contains' => 'matches'));
$SortFields = array(
	'Business name' => 'n.BusinessName',
	'BBB city' => 'BBB.NicknameCity,n.BusinessName',
	'ID' => 'b.BusinessID',
	'URL' => 'b.ReportURL',
	'Email' => 'p.Email',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ( $iSearchMethod == 'matches' ) $searchclause = "n.CondensedName LIKE '%" . $iBusinessName . "%' ";
else if ( $iSearchMethod == 'begins' ) $searchclause = "n.CondensedName LIKE '" . $iBusinessName . "%' ";

if ($_POST) {
	$query = "
		SELECT
			replace(n.BusinessName,'&#39;',''''),
			'BBB ' + NicknameCity + ', ' + BBB.State as BBB,
			b.ReportURL,
			b.BusinessID,
			p.Email,
			BBB.BBBID,
			SUBSTRING(nt.Notes,1,500),
			nt.Attachment1,
			nt.Attachment2
		from BusinessName n WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = n.BBBID and b.BusinessID = n.BusinessID
		inner join BBB WITH (NOLOCK) ON BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join BBBPerson p WITH (NOLOCK) on p.BBBID = BBB.BBBID AND
			p.ComplaintContact = '1' and LEN(p.Email) > 1 AND
			p.PersonID = ( Select MAX(P2.PersonID) from BBBPerson P2 WITH (NOLOCK) where
				P2.BBBID = BBB.BBBID AND P2.ComplaintContact = '1' AND LEN(P2.Email) > 1)
		left outer join NationalComplaintsNotes nt WITH (NOLOCK) on
			nt.BBBID = b.BBBID and nt.BusinessID = b.BusinessID
		where
			{$searchclause} AND
			b.ReportType = 'Single' and
			NOT n.BusinessName IS NULL AND
			b.BBBID != '9999' and b.TOBID != '90000-000' and
			b.PublishToCIBR = 1 and n.PublishToCIBR = 1 and b.IsReportable = 1
		";
	if ($iSortBy) $query .= " ORDER BY " . $iSortBy;

	if ($_POST['use_saved'] == '1') $rs = $_SESSION['rs'];
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
				array('Name', $SortFields['Business name'], '', 'left'),
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('URL', $SortFields['URL'], '', 'left'),
				array('ID', $SortFields['ID'], '', 'left'),
				array('Email', $SortFields['Email'], '', 'left'),
				array('Notes', '', '25%', 'left'),
			)
		);
		$xcount = 0;

		foreach ($rs as $k => $fields) {
			$xcount++;
			
			// URL
			$oURL = trim($fields[2]);
			if ($oURL) {
				if (substr($oURL,0,4) != 'http') $oURL = "http://" . $oURL;
				$oURL = "<a target=_viewrpt href='" . $oURL . "'>View Report</a>";
			}
			
			// Email
			$oEmail = trim($fields[4]);
			if ($oEmail) $oEmail = "<a href=mailto:" . $oEmail . ">Email BBB</a>";

			$oBBBID = $fields[5];

			// Notes attachments
			$path = $oBBBID . "/natcmpls/";
			$oAttachment1 = basename($fields[7]);
			$oAttachment2 = basename($fields[8]);
			if ($oAttachment1) $oAttachment1 = "<a target=_new href=\"servefile.php?{$path}" .
				urlencode($oAttachment1) . "\" class=submit_button_small style='color: #FFFFFF;'>View</a>";
			if ($oAttachment2) $oAttachment2 = "<a target=_new href=\"servefile.php?{$path}" .
				urlencode($oAttachment2) . "\" class=submit_button_small style='color: #FFFFFF;'>View</a>";

			// Notes
			$oNotes = trim($fields[6]);
			if ($oNotes && $BBBID == $oBBBID) $oNotes =
				"<a target=_new href=national_complaints_notes.php?iBBBID={$oBBBID}&iBusinessID={$fields[3]}>" .
				"{$oNotes}</a>";
			if (! $oNotes && $BBBID == $oBBBID) $oNotes =
				"<a target=_new href=national_complaints_notes.php?iBBBID={$oBBBID}&iBusinessID={$fields[3]}>" .
				"Notes</a>";
			$oNotes .= " " . $oAttachment1 . " " . $oAttachment2;

			$report->WriteReportRow(
				array (
					/*$xcount,*/
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $oBBBID .
						"&iBusinessID=" . $fields[3] .  ">" . AddApost($fields[0]) . "</a>",
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $oBBBID .
						">" . AddApost($fields[1]) . "</a>",
					$oURL,
					$fields[3],
					$oEmail,
					$oNotes
				)
			);
		}
	}
	else {
		echo "No results found";
	}
	$report->Close('suppress');
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>
