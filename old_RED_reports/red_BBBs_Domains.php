<?php

/*
 * 10/06/15 MJS - new file
 * 01/28/20 MJS - refactored
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBID = NoApost($_POST['iBBBID']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all', '') );
$SortFields = array(
	'BBB city' => 'NicknameCity'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {


	$query = "
		SELECT
			BBB.BBBID,
			NickNameCity,
			Subdomains
		FROM CMSCORE.dbo.bbbInfo WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) ON BBB.BBBID = LegacyID
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
			('{$iBBBID}' = '' OR BBB.BBBID = '{$iBBBID}')
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
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city']),
				array('Domain', ''),
			)
		);
		foreach ($rs as $k => $fields) {
			$subdomainstring = $fields[2];
			if ($fields[1] == "Worcester") {
				$subdomainstring = "[\"central-westernma.bbb.org\",\"springfieldma.bbb.org\",\"worcester.bbb.org\",\"www.springfieldma.bbb.org\",\"www.worcester.bbb.org\",\"cne.bbb.org\",\"springfield-ma.bbb.org\",\"www.central-westernma.bbb.org\",\"www.cne.bbb.org\",\"www.springfield-ma.bbb.org\"]";
			}
			$subdomains = ParseBBBDomains($subdomainstring, true);
			foreach ($subdomains as $s) {
				$report->WriteReportRow(
					array (
						"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
							">" . AddApost($fields[1]) . "</a>",
						$s
					)
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