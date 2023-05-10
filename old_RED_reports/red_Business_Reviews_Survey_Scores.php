<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 04/20/16 MJS - locked vendors out
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 08/25/16 MJS - aligned column headers
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckBBBOnly($BBBID);


$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'BBB.NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'"Helpful?"' => 'percent_helpful',
	'"Use Again?"' => 'percent_woulduseagain',
	'"Valuable?"' => 'avg_howvaluable',
	'"Easy?"' => 'avg_howeasy',
	'"Overview Valuable?"' => 'avg_howvaluableoverview',
	'"Complaints Valuable?"' => 'avg_howvaluablecmpls',
	'"Photos Valuable?"' => 'avg_howvaluablephotos',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT
		BBB.BBBID,
		BBB.NickNameCity + ', ' + BBB.State,
		BBB.SalesCategory,
		tblRegions.RegionAbbreviation,

		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			(IsHelpful = '0' or IsHelpful = '1') and SurveyType = 'Business-Review-IsHelpful' and
			(s.BBBID = BBB.BBBID) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as responded_helpful,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			IsHelpful = '1' and SurveyType = 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as helpful,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			IsHelpful = '0' and SurveyType = 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as nothelpful,
		cast (
				cast (
					(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
						IsHelpful = '1' and
						SurveyType = 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
						DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "')
					as decimal(14,2) )
				/
				cast (
					(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
						(IsHelpful = '0' or IsHelpful = '1') and
						SurveyType = 'Business-Review-IsHelpful' and
						(s.BBBID = BBB.BBBID) and DateSubmitted >= '" . $iDateFrom . "' and
						DateSubmitted <= '" . $iDateTo . "' )
					as decimal(14,2) )
			as decimal(14,2) )
			as percent_helpful,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			(WouldUseAgain = '0' or WouldUseAgain = '1') and SurveyType != 'Business-Review-IsHelpful' and
			(s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as responded_woulduseagain,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			WouldUseAgain = '1' and SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as woulduseagain,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			WouldUseAgain = '0' and SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as wouldnotuseagain,
		cast (
				cast (
					(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
						WouldUseAgain = '1' and SurveyType != 'Business-Review-IsHelpful' and
						(s.BBBID = BBB.BBBID ) and
						DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "')
					as decimal(14,2) )
				/
				cast (
					(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
						(WouldUseAgain = '0' or WouldUseAgain = '1') and
						SurveyType != 'Business-Review-IsHelpful' and
						(s.BBBID = BBB.BBBID) and DateSubmitted >= '" . $iDateFrom . "' and
						DateSubmitted <= '" . $iDateTo . "' )
					as decimal(14,2) )
			as decimal(14,2) )
			as percent_woulduseagain,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuableWasReview is not null and HowValuableWasReview != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as responded_howvaluable,
		(select SUM(HowValuableWasReview) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuableWasReview is not null and HowValuableWasReview != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as sum_howvaluable,
		2.00 + cast(
			(select SUM( cast( HowValuableWasReview as decimal(14,2)) )
				from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuableWasReview is not null and HowValuableWasReview != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			/
			(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuableWasReview is not null and HowValuableWasReview != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as decimal(14,2) )
			as avg_howvaluable,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowEasyToFindInformation is not null and HowEasyToFindInformation != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as responded_howeasy,
		(select SUM(HowEasyToFindInformation) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowEasyToFindInformation is not null and HowEasyToFindInformation != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as sum_howeasy,
		2.00 + cast(
			(select SUM( cast( HowEasyToFindInformation as decimal(14,2)) )
				from BusinessReviewSurveys s WITH (NOLOCK) where
				HowEasyToFindInformation is not null and HowEasyToFindInformation != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			/
			(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
				HowEasyToFindInformation is not null and HowEasyToFindInformation != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as decimal(14,2) )
			as avg_howeasy,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuableOverview is not null and HowValuableOverview != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as responded_howvaluableoverview,
		(select SUM(HowValuableOverview) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuableOverview is not null and HowValuableOverview != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as sum_howvaluableoverview,
		2.00 + cast(
			(select SUM( cast( HowValuableOverview as decimal(14,2)) )
				from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuableOverview is not null and HowValuableOverview != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			/
			(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuableOverview is not null and HowValuableOverview != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as decimal(14,2) )
			as avg_howvaluableoverview,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuableComplaints is not null and HowValuableComplaints != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as responded_howvaluablecmpls,
		(select SUM(HowValuableComplaints) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuableComplaints is not null and HowValuableComplaints != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as sum_howvaluablecmpls,
		2.00 + cast(
			(select SUM( cast( HowValuableComplaints as decimal(14,2)) )
				from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuableComplaints is not null and HowValuableComplaints != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			/
			(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuableComplaints is not null and HowValuableComplaints != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as decimal(14,2) )
			as avg_howvaluablecmpls,
		(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuablePhotoVideo is not null and HowValuablePhotoVideo != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as responded_howvaluablephotos,
		(select SUM(HowValuablePhotoVideo) from BusinessReviewSurveys s WITH (NOLOCK) where
			HowValuablePhotoVideo is not null and HowValuablePhotoVideo != 0 and
			SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
			DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as sum_howvaluablephotos,
		2.00 + cast(
			(select SUM( cast( HowValuablePhotoVideo as decimal(14,2)) )
				from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuablePhotoVideo is not null and HowValuablePhotoVideo != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			/
			(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
				HowValuablePhotoVideo is not null and HowValuablePhotoVideo != 0 and
				SurveyType != 'Business-Review-IsHelpful' and (s.BBBID = BBB.BBBID ) and
				DateSubmitted >= '" . $iDateFrom . "' and DateSubmitted <= '" . $iDateTo . "' )
			as decimal(14,2) )
			as avg_howvaluablephotos
	
		from BBB WITH (NOLOCK)
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
			BBB.BBBBranchID = 0 AND BBB.IsActive = '1' AND
			(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
				(IsHelpful = '0' or IsHelpful = '1') and
				SurveyType = 'Business-Review-IsHelpful' and
				(s.BBBID = BBB.BBBID) and DateSubmitted >= '" . $iDateFrom . "' and
				DateSubmitted <= '" . $iDateTo . "' ) > 0 and
			(select COUNT(*) from BusinessReviewSurveys s WITH (NOLOCK) where
				(WouldUseAgain = '0' or WouldUseAgain = '1') and
				SurveyType != 'Business-Review-IsHelpful' and
				(s.BBBID = BBB.BBBID) and DateSubmitted >= '" . $iDateFrom . "' and
				DateSubmitted <= '" . $iDateTo . "' ) > 0 and
			('" . $iRegion . "' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('" . $iSalesCategory . "' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			('" . $iState . "' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
		";
	if ($iSortBy > '') {
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
				array('Sales Cat', $SortFields['Sales category'], '', 'right'),
				array('Region', $SortFields['BBB region'], '', 'left'),
				array('"Helpful?" Surveyed', $SortFields['"Helpful?"'], '', 'right'),
				array('Replied Yes', '', '', 'right'),
				array('"Use Again?" Surveyed', $SortFields['"Use Again?"'], '', 'right'),
				array('Replied Yes', '', '', 'right'),
				array('"Valuable?" Surveyed', $SortFields['"Valuable?"'], '', 'right'),
				array('Avg Score 0-4', '', '', 'left'),
				array('"Easy?" Surveyed', $SortFields['"Easy?"'], '', 'right'),
				array('Avg Score 0-4', '', '', 'left'),
				array('"Overview Valuable?"', $SortFields['"Overview Valuable?"'], '', 'right'),
				array('Avg Score 0-4', '', '', 'left'),
				array('"Complaints Valuable?"', $SortFields['"Complaints Valuable?"'], '', 'right'),
				array('Avg Score 0-4', '', '', 'left'),
				array('"Photos Valuable?"', $SortFields['"Photos Valuable?"'], '', 'right'),
				array('Avg Score 0-4', '', '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			if ($fields[0] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						"><span class='{$class}'>" . AddApost($fields[1]) . "</span></a>",
					$fields[2],
					$fields[3],
					$fields[4],
					FormatPercentage($fields[7]),
					$fields[8],
					FormatPercentage($fields[11]),
					$fields[12],
					$fields[14],
					$fields[15],
					$fields[17],
					$fields[18],
					$fields[20],
					$fields[21],
					$fields[23],
					$fields[24],
					$fields[26],
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				'',
				'',
				array_sum( get_array_column($rs, 4) ),
				FormatPercentage(
					array_sum( get_array_column($rs, 5) ) / array_sum( get_array_column($rs, 4) )
				),
				array_sum( get_array_column($rs, 8) ),
				FormatPercentage(
					array_sum( get_array_column($rs, 9) ) / array_sum( get_array_column($rs, 8) )
				),
				array_sum( get_array_column($rs, 12) ),
				round(2 + (array_sum( get_array_column($rs, 13) ) / array_sum( get_array_column($rs, 12) )), 2),
				array_sum( get_array_column($rs, 15) ),
				round(2 + (array_sum( get_array_column($rs, 16) ) / array_sum( get_array_column($rs, 15) )), 2),
				array_sum( get_array_column($rs, 18) ),
				round(2 + (array_sum( get_array_column($rs, 19) ) / array_sum( get_array_column($rs, 18) )), 2),
				array_sum( get_array_column($rs, 21) ),
				round(2 + (array_sum( get_array_column($rs, 22) ) / array_sum( get_array_column($rs, 21) )), 2),
				array_sum( get_array_column($rs, 24) ),
				round(2 + (array_sum( get_array_column($rs, 25) ) / array_sum( get_array_column($rs, 24) )), 2),
			)
		);
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>