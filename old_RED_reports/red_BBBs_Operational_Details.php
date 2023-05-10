<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 06/05/15 MJS - changed JPM to SalesVendor
 * 02/19/16 MJS - fixed 2 misaligned column labels
 * 02/24/16 MJS - export full social media addresses to excel
 * 02/26/16 MJS - removed field for customer reviews
 * 02/29/16 MJS - refactored for new social media site data structure
 * 05/19/16 MJS - changed CharityReview yes/no radio to CharityReviewProvider selection list
 * 07/05/16 MJS - added DigitalMediaVendor
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 08/25/16 MJS - aligned column headers
 * 06/23/17 MJS - cleaned up code
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'Vendor' => 'Vendor,BBB.NicknameCity',
	'IVR' => 'IVR,NicknameCity',
	'Sales vendor' => 'SalesVendor,NicknameCity',
	'Digital media vendor' => 'DigitalMediaVendor,NicknameCity',
	'Foundation' => 'Foundation,NicknameCity',
	'Charity review' => 'BBB.CharityReviewProvider,BBB.NicknameCity',
	'Charity seal' => 'BBB.CharitySeal,BBB.NicknameCity',
	'Languages' => 'BBB.Languages',
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
			NickNameCity + ', ' + BBB.State,
			BBB.SalesCategory,
			tblRegions.RegionAbbreviation,
			BBB.Vendor,
			Case When BBB.IVR = '1' then 'Yes' else 'No' end,
			SalesVendor,
			DigitalMediaVendor,
			Case When BBB.Foundation = '1' then 'Yes' else 'No' end,
			CharityReviewProvider,
			Case When BBB.CharitySeal = '1' then 'Yes' else 'No' end,
			BBB.Languages
		from BBB WITH (NOLOCK)
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
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
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array('MIS Vendor', $SortFields['Vendor'], '', 'left'),
				array('IVR', $SortFields['IVR'], '', 'left'),
				array('Sales Vendor', $SortFields['Sales vendor'], '', 'left'),
				array('Digital Media Vendor', $SortFields['Digital media vendor'], '', 'left'),
				array('Foundation', $SortFields['Foundation'], '', 'left'),
				array('Charity Review', $SortFields['Charity review'], '', 'left'),
				array('Charity Seal', $SortFields['Charity seal'], '', 'left'),
				array('Foreign Languages', $SortFields['Languages'], '', 'left'),
				array('Social Media Sites', '', '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			if ($fields[0] == $BBBID) $class = "bold darkgreen";
			else $class = "";

			$oSocialMediaSites = '';
			$subquery = "SELECT SiteAddress, SiteType FROM BBBSocialMediaSite WTH (NOLOCK) WHERE
				BBBID = '{$fields[0]}' and BBBBranchID = 0 ORDER BY SiteType";
			$srsraw = $conn->execute($subquery);
			if (! $srsraw) AbortREDReport($subquery);
			$srs = $srsraw->GetArray();
			foreach ($srs as $sk => $sfields) {
				if (substr($sfields[0],0,4) != 'http') $sfields[0] = "http://" . $sfields[0];
				if ($output_type > "") /* excel or word */
						$oSocialMediaSites .= $sfields[0] . ", ";
				else $oSocialMediaSites .= "<a target=_new href=" . $sfields[0] .
					"><span class='{$class}'>" . $sfields[1] . "</span></a> ";
			}

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						"><span class='{$class}'>" . AddApost($fields[1]) . "</span></a>",
					$fields[3],
					$fields[2],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
					$fields[8],
					$fields[9],
					$fields[10],
					$fields[11],
					$oSocialMediaSites
				),
				'',
				$class
			);
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>