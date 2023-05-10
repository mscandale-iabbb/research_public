<?php

/*
 * 11/27/15 MJS - fixed layout width
 * 02/26/16 MJS - removed field for reporting customer reviews
 * 02/29/16 MJS - refactored for new social media site data structure
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();

$iBBBID = Numeric2($_GET['iBBBID']);

function ShowField($label, $value) {
	echo "<tr>";
	echo "<td class='labelback'>";
	echo $label;
	echo "<td class='table_cell'>";
	echo $value;
}

echo "<div class='main_section roundedborder'>";
echo "<table class='report_table'>";
if ($_GET) {
	$query = "SELECT
			BBB.NickNameCity + ', ' + BBB.State,
			BBB.BBBID,
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
			BBB.Vendor,
			BBB.SalesCategory,
			BBB.TimeZone,
			BBB.ServingArea,
			BBB.Region,
			BBB.Languages,
			BBB.GeneralEmail,
			BBB.ComplaintEmail,
			BBB.InquiryEmail,
			BBB.SalesEmail,
			BBB.YearEstablished,
			Case When BBB.IsActive = '1' then 'Yes' else 'No' end,
			BBB.LastModified
		FROM BBB WITH (NOLOCK)
		WHERE
			BBB.BBBID = '{$iBBBID}' and
			BBB.BBBBranchID = '0'
		";

	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {

			$oSocialMediaSites = '';
			$subquery = "SELECT SiteAddress, SiteType FROM BBBSocialMediaSite WTH (NOLOCK) WHERE
				BBBID = '{$fields[1]}' and BBBBranchID = 0 ORDER BY SiteType";
			$srsraw = $conn->execute($subquery);
			if (! $srsraw) AbortREDReport($subquery);
			$srs = $srsraw->GetArray();
			foreach ($srs as $sk => $sfields) {
				if (substr($sfields[0],0,4) != 'http') $sfields[0] = "http://" . $sfields[0];
				$oSocialMediaSites .= "<a target=_new href=" . $sfields[0] . ">" . $sfields[1] . "</a> ";
			}

			ShowField("BBB city", AddApost($fields[0]) );
			ShowField("BBB ID", $fields[1]);
			ShowField("BBB name", AddApost($fields[2]) );

			ShowField("Address", AddApost(
				$fields[3] . ' ' . $fields[4] . ' &nbsp;' .  $fields[5] . ' ' . $fields[6] . ' ' . $fields[7]) );
			if (trim($fields[10]) > '' && $fields[10] != $fields[5]) {
				ShowField("Mailing address", AddApost(
					$fields[8] . ' ' . $fields[9] . ' ' .
					$fields[10] . ' ' . $fields[11] . ' ' . $fields[12]) );
			}

			// contact persons
			$subquery = "SELECT 
					p.Prename,
					p.FirstName,
					p.MiddleName,
					p.LastName,
					p.PostName,
					p.Title,
					p.Email
				FROM BBBPerson p WITH (NOLOCK) WHERE
					p.BBBID = '" . $iBBBID . "' and p.BBBBranchID = '0'
				ORDER BY p.LastName
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$persons = "";
				foreach ($srs as $k => $row) {
					$persons .= trim($srs->fields[0] . " " . $srs->fields[1] . " " .
						$srs->fields[2] . " " . $srs->fields[3] . " " . $srs->fields[4]) . ", " .
						$srs->fields[5] . " &nbsp; <a href=mailto:" . $srs->fields[6] . ">" .
						$srs->fields[6] . "</a>" .
						"<br/>";
				}
				if ($srs->RecordCount() > 12) {
					$persons = "<div style='height:200px; overflow:auto;'>" .
						$persons . "</div>";
				}
				ShowField("Persons", $persons);
			}

			// additional phones
			$subquery = "SELECT 
					p.PhoneNumber
				FROM BBBPhone p WITH (NOLOCK) WHERE
					p.BBBID = '" . $iBBBID . "' and p.BBBBranchID = '0' and
					LEN(p.PhoneNumber) >= 7
				ORDER BY p.PhoneNumber
				";
			$srs = $conn->execute("$subquery");
			if ($srs->RecordCount() > 0) {
				$phones = "";
				foreach ($srs as $k => $row) {
					$phones .= FormatPhone($srs->fields[0]) .  "<br/>";
				}
				if ($srs->RecordCount() > 6) {
					$phones = "<div style='height:75px; overflow:auto;'>" .
						$phones . "</div>";
				}
				ShowField("Phones", $phones);
			}

			ShowField("Vendor", $fields[14]);
			ShowField("Sales category", $fields[15]);
			ShowField("Time zone", $fields[16]);
			ShowField("Serving area", $fields[17]);
			ShowField("Region", $fields[18]);
			ShowField("Languages", $fields[19]);
			ShowField("General email", "<a href=mailto:" . $fields[20] . ">" . $fields[20] . "</a>");
			ShowField("Complaint email", "<a href=mailto:" . $fields[21] . ">" . $fields[21] . "</a>");
			ShowField("Inquiry email", "<a href=mailto:" . $fields[22] . ">" . $fields[22] . "</a>");
			ShowField("Sales email", "<a href=mailto:" . $fields[23] . ">" . $fields[23] . "</a>");
			ShowField("Year established", $fields[24]);
			ShowField("Social media sites", $oSocialMediaSites);
			ShowField("Active", $fields[25]);
			ShowField("Record last updated", FormatDate($fields[26]));
		}
	}
}
echo "<tr><td colspan=2 class='column_header thickpadding center'>";
echo "<a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a>";
echo "</table>";
echo "</div>";

$page->write_pagebottom();

?>