<?php

/*
 * 06/27/17 MJS - new file
 * 06/28/17 MJS - rewrote for db fields
 * 07/07/17 MJS - made last 3 columns sortable
 * 07/11/17 MJS - rewrote sql, removed stripping of numeric facebook ids
 * 11/16/17 MJS - added estabs column
 * 04/02/18 MJS - cleaned up code
 * 06/15/18 MJS - removed Facebook
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);

$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',date('n')) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iBBBID = NoApost($_POST['iBBBID']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iMonthFrom', 'As of month', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all', '') );
$SortFields = array(
	'BBB city' => 'NicknameCity',
	/*'Facebook' => 'FacebookPage',*/
	/*'Facebook Likes' => 'FacebookLikes',*/
	'Twitter' => 'TwitterAccount',
	'Twitter Followers' => 'TwitterFollowers',
	'YouTube' => 'YouTubeAddress',
	'YouTube Views' => 'YouTubeViews',
	'Estabs' => 'Estabs'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @datefrom date;
		set @datefrom = CONVERT(datetime, '{$iMonthFrom}' + '/1/' + '{$iYearFrom}');

		declare @dateto date;
		declare @tomonth int;
		declare @toyear int;
		set @tomonth = '{$iMonthFrom}';
		set @toyear = '{$iYearFrom}';

		SELECT
			BBB.BBBID,
			NickNameCity + ', ' + BBB.State,
			'', /*s.SiteAddress as FacebookPage,*/
			s2.SiteAddress as TwitterAccount,
			s3.SiteAddress as YouTubeAddress,
			0 /*( select sum(m.CountOfFacebookLikes) from BBBSocialMediaStats m WITH (NOLOCK) where
				m.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @datefrom
			)*/ as FacebookLikes,
			( select sum(m.CountOfTwitterFollowers) from BBBSocialMediaStats m WITH (NOLOCK) where
				m.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @datefrom
			) as TwitterFollowers,
			( select sum(m.CountOfYouTubeViews) from BBBSocialMediaStats m WITH (NOLOCK) where
				m.BBBID = BBB.BBBID and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) >= @datefrom and
				CONVERT(datetime, CAST( MonthNumber as VARCHAR) + '/1/' +
					CAST( [Year] AS VARCHAR) ) <= @datefrom
			) as YouTubeViews,
			EstabsInArea
		from BBB WITH (NOLOCK)
		left outer join BBBSocialMediaSite s WITH (NOLOCK) on
			s.BBBID = BBB.BBBID and s.BBBBranchID = BBB.BBBBranchID and s.SiteType = 'Facebook'
		left outer join BBBSocialMediaSite s2 WITH (NOLOCK) on
			s2.BBBID = BBB.BBBID and s2.BBBBranchID = BBB.BBBBranchID and s2.SiteType = 'Twitter'
		left outer join BBBSocialMediaSite s3 WITH (NOLOCK) on
			s3.BBBID = BBB.BBBID and s3.BBBBranchID = BBB.BBBBranchID and s3.SiteType = 'YouTube'
		left outer join BBBFinancials f WITH (NOLOCK) ON f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and
			f.[Year] = @toyear
		where
			BBB.BBBBranchID = '0' AND BBB.IsActive = '1' AND
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
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array("Estabs {$iYearTo}", $SortFields['Estabs'], '', 'right'),
				/*array('Facebook Page', $SortFields['Facebook'], '', 'left'),*/
				array('Twitter Account', $SortFields['Twitter'], '', 'left'),
				array('YouTube Address', $SortFields['YouTube'], '', 'left'),
				/*array('Facebook Page Likes', $SortFields['Facebook Likes'], '', 'right'),*/
				array('Twitter Followers', $SortFields['Twitter Followers'], '', 'right'),
				array('YouTube Views', $SortFields['YouTube Views'], '', 'right'),
			)
		);
		foreach ($rs as $k => $fields) {

			/*
			// parse facebook page name
			$facebook_page = "";
			$fields[2] = trim($fields[2]);
			if (strpos($fields[2], " ") !== false) {  // if has spaces inside it
				$fields[2] = "";
			}
			if (strpos($fields[2], "?") !== false) {  // if has a question mark inside it
				$fields[2] = substr($fields[2], 0, strrpos($fields[2], "?"));  // strip after question mark
			}
			$facebook_page = basename($fields[2]);
			if ($facebook_page == "facebook") {
				$facebook_page = "";
			}
			*/

			// parse twitter account name
			$twitter_account = "";
			$fields[3] = trim($fields[3]);
			if (strpos($fields[3], " ") !== false) {  // if has spaces inside it
				$fields[3] = "";
			}
			if (substr($fields[3],0,1) == "@") {
				$fields[3] = substr($fields[3],1);
			}
			$twitter_account = basename($fields[3]);
			if ($twitter_account == "twitter") {
				$twitter_account = "";
			}

			// parse youtube address
			$youtube_address = "";
			$fields[4] = trim($fields[4]);
			if (strpos($fields[4], " ") !== false) {  // if has spaces inside it
				$fields[4] = "";
			}
			if (substr($fields[4], strlen($fields[4]) - 9, 9) == "/featured") {
				$fields[4] = substr($fields[4], 0, strlen($fields[4]) - 9);
			}
			if (substr($fields[4], strlen($fields[4]) - 5, 5) == "/feed") {
				$fields[4] = substr($fields[4], 0, strlen($fields[4]) - 5);
			}
			$youtube_address = basename($fields[4]);

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>" .
						AddApost($fields[1]) . "</a>",
					$fields[8],
					/*"<a target=detail href=https://www.facebook.com/{$facebook_page}>" .
						$facebook_page . "</a>",*/
					"<a target=detail href=https://www.twitter.com/{$twitter_account}>" .
						$twitter_account . "</a>",
					"<a target=detail href=https://www.youtube.com/channel/{$youtube_address}>" .
						$youtube_address . "</a>",
					/*$fields[5],*/
					$fields[6],
					$fields[7]
				)
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