<?php

/*
 * 11/24/15 MJS - added menu for CBBB-only
 * 04/01/16 MJS - added menu for Evaluations
 * 12/28/16 MJS - noting NOHTML
 * 07/11/18 MJS - added menu for Customer Reviews
 * 05/16/19 MJS - used SETTINGS for org name
 */

$tabs = array ();

$tempFolderName = $_REQUEST['iFolderName'];
if ($tempFolderName == '') $tempFolderName = 'All RED Reports';
//$tabs["red_reports.php"] = $temp;

if ($tempFolderName != 'All RED Reports') $tabs["red_reports.php?iFolderName="] = "All RED Reports";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Accreditation') $tabs["red_reports.php?iFolderName=Accreditation"] = "Accreditation";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Businesses') $tabs["red_reports.php?iFolderName=Businesses"] = "Businesses";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Complaints') $tabs["red_reports.php?iFolderName=Complaints"] = "Complaints";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Customer Reviews') $tabs["red_reports.php?iFolderName=Customer%20Reviews"] = "Customer Reviews";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Data Quality') $tabs["red_reports.php?iFolderName=Data%20Quality"] = "Data Quality";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Directory') $tabs["red_reports.php?iFolderName=Directory"] = "Directory";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Finances') $tabs["red_reports.php?iFolderName=Finances"] = "Finances";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'General') $tabs["red_reports.php?iFolderName=General"] = "General";
else $tabs["red_reports.php"] = $tempFolderName;
if ($tempFolderName != 'Inquiries') $tabs["red_reports.php?iFolderName=Inquiries"] = "Inquiries";
else $tabs["red_reports.php"] = $tempFolderName;
if ($BBBID == '2000') {
	if ($tempFolderName != "{$SETTINGS['ORG_NAME']} Only") {
		$tabs["red_reports.php?iFolderName={$SETTINGS['ORG_NAME']}%20Only"] = "{$SETTINGS['ORG_NAME']} Only";
	}
	else $tabs["red_reports.php"] = $tempFolderName;
	if ($tempFolderName != 'Evaluations') $tabs["red_reports.php?iFolderName=Evaluations"] = "Evaluations";
	else $tabs["red_reports.php"] = $tempFolderName;
}

/*
$tabs["faq.php"] = "FAQ";
$tabs["mailto:xyz@xyz.org?subject=Question"] = "Questions";
if ($_SESSION['LoweredEmail'] == $ADMIN_EMAIL_ADDRESS) {
	$tabs["admin.php"] = "Admin";
}
*/

?>