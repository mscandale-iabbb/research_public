<?php

/*
 * 06/28/17 MJS - new file
 * 07/05/17 MJS - changed curl to file_get_contents
 * 07/11/17 MJS - rewrote sql, removed stripping of numeric facebook ids
 * 07/17/17 MJS - added error reporting
 * 07/31/17 MJS - added editing form
 * 02/16/18 MJS - removed facebook api call
 * 06/15/18 MJS - removed facebook
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);

/*
// load Facebook JavaScript SDK
$page->AddHTML("
	<script>
	  window.fbAsyncInit = function() {
	    FB.init({
	      appId            : '420197755046635', // app ID for BBBIntranet
	      autoLogAppEvents : true,
	      xfbml            : true,
	      version          : 'v2.12'
	    });
	  };
	  (function(d, s, id){
	     var js, fjs = d.getElementsByTagName(s)[0];
	     if (d.getElementById(id)) {return;}
	     js = d.createElement(s); js.id = id;
	     js.src = \"https://connect.facebook.net/en_US/sdk.js\";
	     fjs.parentNode.insertBefore(js, fjs);
	   }(document, 'script', 'facebook-jssdk'));
	</script>
	");
$page->AddHTML("
	<script>
	var xusertoken;
	function myFacebookLogin() {
		FB.login(
			function(response) {
				if (response.authResponse) {
					xusertoken = FB.getAuthResponse()['accessToken'];
					alert('logged in');
				}
				else {
					alert('error logging in');
				}
			},
			{scope: 'publish_pages,manage_pages,pages_show_list,read_insights'}
		);
	}
	function GetFacebookStats(page_id) {
		var xpagetoken;
		// alert('User access token is: ' + xusertoken);
		FB.api('/' + page_id, {fields: 'access_token'}, function(response){ alert(JSON.stringify(response)); });
		// FB.api('/' + page_id + '/insights/page_fans_country?access_token=' + xtoken, function(response){ alert(response.data[0].values[0].value); });
		FB.api('/' + page_id + '/insights/page_fans_country?access_token=' + xpagetoken, function(response){ alert(JSON.stringify(response)); });
	}
	</script>
	<button onclick=\"myFacebookLogin()\">Login with Facebook</button>
	");
*/

$iShowSource = $_POST['iShowSource'];
$iStoreStats = NoApost($_POST['iStoreStats']);
$iEditStats = NoApost($_POST['iEditStats']);

$input_form = new input_form($conn);
$input_form->AddTextField('iStoreStats', 'Internal data', $iStoreStats, "background-color:gray; width:500px;" /*"xvisibility:hidden;"*/ );
$input_form->AddSubmitButton();
$input_form->Close();

/*
function GetURL($url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($curl);
	curl_close($curl);
	$json = json_decode($output, true);
	return $json;
}
*/

function GetURL($url) {
	$output = file_get_contents($url);
	if (! $output) {
		$error = error_get_last();
		echo "<p>{$error['message']}</p>";
		return;
	}
	$json = json_decode($output, true);
	return $json;
}

/*
function GetFacebookLikes($page) {
	$access_token = "420197755046635|b33abae366ab59b2c0a5709ce01fa405";
	$url = "https://graph.facebook.com/{$page}/insights/page_fans_country?access_token=" . $access_token;

	$likes = 0;
	$output = GetURL($url);
	$data = $output['data'][0]['values'][0]['value'];
	if (isset($data)) {
		foreach ($data as $item) {
			$likes += $item;
		}
	}
	return $likes;
}
*/

function GetTwitterFollowers($account) {
	$url = "https://cdn.syndication.twimg.com/widgets/followbutton/info.json?screen_names=" . $account;
	/* $data = file_get_contents("https://cdn.syndication.twimg.com/widgets/followbutton/info.json?screen_names=" . $account); */

	$output = GetURL($url);
	$followers = $output[0]['followers_count'];
	return $followers;
}

function GetYouTubeViews($video) {
	$access_key = "AIzaSyA11moGHCUE5s6gmYpgBBDgLPKTbXVQggs";
	$url = "https://www.googleapis.com/youtube/v3/videos?id={$video}&key={$access_key}&part=statistics";

	$output = GetURL($url);
	$views = $output['items'][0]['statistics']['viewCount'];
	return $views;
}

function GetYouTubeChannel($user) {
	$access_key = "AIzaSyA11moGHCUE5s6gmYpgBBDgLPKTbXVQggs";
	$url = "https://www.googleapis.com/youtube/v3/channels?forUsername={$user}&part=id&key={$access_key}";

	$output = GetURL($url);
	$channel = $output['items'][0]['id'];
	return $channel;
}

function GetYouTubeVideos($channel) {
	$access_key = "AIzaSyA11moGHCUE5s6gmYpgBBDgLPKTbXVQggs";

	$allvideos = array();
	$pages = 0;
	while (true) {
		$pages++;
		if ($pages >= 11) {
			break;
		}
		$url = "https://www.googleapis.com/youtube/v3/search?key={$access_key}&channelId={$channel}&part=snippet,id&maxResults=50";
		if ($nextpagetoken) {
			$url .= "&pageToken=" . $nextpagetoken;
		}

		$videos = array();
		$output = GetURL($url);
		foreach ($output['items'] as $data) {
			$videos[] = $data['id']['videoId'];
			$allvideos[] = $data['id']['videoId'];
		}
		if (count($videos) == 50) {
			$nextpagetoken = trim($output['nextPageToken']);
			if (! $nextpagetoken) break;
		}
		else break;
	}

	return $allvideos;
}

if ($_POST) {

	/* special option to store stats */
	if ($iStoreStats) {
		$iVals = explode("|", $iStoreStats);
		$input_BBBID = $iVals[0];
		//$input_facebook_page = $iVals[1];
		$input_twitter_account = $iVals[1];
		$input_youtube_address = $iVals[2];

		/*
		// get facebook page likes		
		$facebook_likes = 0;
		if ($input_facebook_page) {
		}
		*/

		// get twitter followers
		$twitter_followers = 0;
		if ($input_twitter_account) {
			$twitter_followers = GetTwitterFollowers($input_twitter_account);
		}

		// get youtube views		
		$youtube_views = 0;
		if ($input_youtube_address) {

			// convert youtube user name to youtube channel, if specified
			if (strpos($input_youtube_address, "/channel/") === false) {
				$youtube_channel = GetYouTubeChannel(basename($input_youtube_address));
			}
			else {
				$youtube_channel = basename($input_youtube_address);
			}
	
			// get list of videos from channel
			if ($youtube_channel) {
				$videos = GetYouTubeVideos($youtube_channel);
			}

			// get youtube views
			foreach ($videos as $video) {
				$tmp_views = GetYouTubeViews($video);
				if ($tmp_views >= 1) {
					$youtube_views += $tmp_views;
				}
			}
		}

		$update = "
			INSERT INTO BBBSocialMediaStats (BBBID, [Year], MonthNumber) VALUES (
				'{$input_BBBID}', YEAR(GETDATE()), MONTH(GETDATE()) );
			UPDATE BBBSocialMediaStats SET
				/*CountOfFacebookLikes = '{$facebook_likes}',*/
				CountOfTwitterFollowers = '{$twitter_followers}',
				CountOfYouTubeViews = '{$youtube_views}'
			WHERE
				BBBID = '{$input_BBBID}' and
				[Year] = YEAR(GETDATE()) and MonthNumber = MONTH(GETDATE())
			";
		$r = $conn->execute($update);
		echo "
			<p><xpre>{$update}</xpre></p>
			<form method=post>
			<p class='indented50 lightgrayback'>
			<!--Facebook likes <input type=text size=2 id=iCountOfFacebookLikes name=iCountOfFacebookLikes value='{$facebook_likes}' /><br/>-->
			Twitter followers <input type=text size=2 id=iCountOfTwitterFollowers name=iCountOfTwitterFollowers value='{$twitter_followers}' /><br/>
			YouTube views <input type=text size=2 id=iCountOfYouTubeViews name=iCountOfYouTubeViews value='{$youtube_views}' /><br/>
			BBBID <input type=text size=2 id=iBBBID name=iBBBID value='{$input_BBBID}' /><br/>
			<input type=hidden id=iEditStats name=iEditStats value=1 />
			<input type=submit class=submit_button_small value='    Save Manual Edits     ' />
			</p>
			</form>
			<p class='center'><a class='submit_button' style='color:white' href=red_Monthly_Social_Media.php>Run Report Again</a></p>
			";
		die();
	}

	/* special option to edit stats */
	if ($iEditStats == '1') {
		//$iCountOfFacebookLikes = NoApost($_POST['iCountOfFacebookLikes']);
		$iCountOfTwitterFollowers = NoApost($_POST['iCountOfTwitterFollowers']);
		$iCountOfYouTubeViews = NoApost($_POST['iCountOfYouTubeViews']);
		$iBBBID = NoApost($_POST['iBBBID']);
		$update = "
			INSERT INTO BBBSocialMediaStats (BBBID, [Year], MonthNumber) VALUES (
				'{$iBBBID}', YEAR(GETDATE()), MONTH(GETDATE()) );
			UPDATE BBBSocialMediaStats SET
				/*CountOfFacebookLikes = '{$iCountOfFacebookLikes}',*/
				CountOfTwitterFollowers = '{$iCountOfTwitterFollowers}',
				CountOfYouTubeViews = '{$iCountOfYouTubeViews}'
			WHERE
				BBBID = '{$iBBBID}' and
				[Year] = YEAR(GETDATE()) and MonthNumber = MONTH(GETDATE())
			";
		$r = $conn->execute($update);
		echo "
			<p><xpre>{$update}</xpre></p>
			<p class='center'><a class='submit_button' style='color:white' href=red_Monthly_Social_Media.php>Run Report Again</a></p>
			";
		die();
	}

	$query = "
		SELECT
			BBB.BBBID,
			NickNameCity + ', ' + BBB.State,
			'' /*s.SiteAddress as FacebookPage*/,
			s2.SiteAddress as TwitterAccount,
			s3.SiteAddress as YouTubeAddress,
			0 /*( select sum(m.CountOfFacebookLikes) from BBBSocialMediaStats m WITH (NOLOCK) where
				m.BBBID = BBB.BBBID and MonthNumber = MONTH(GETDATE()) and [Year] = YEAR(GETDATE())
			)*/ as FacebookLikes,
			( select sum(m.CountOfTwitterFollowers) from BBBSocialMediaStats m WITH (NOLOCK) where
				m.BBBID = BBB.BBBID and MonthNumber = MONTH(GETDATE()) and [Year] = YEAR(GETDATE())
			) as TwitterFollowers,
			( select sum(m.CountOfYouTubeViews) from BBBSocialMediaStats m WITH (NOLOCK) where
				m.BBBID = BBB.BBBID and MonthNumber = MONTH(GETDATE()) and [Year] = YEAR(GETDATE())
			) as YouTubeViews
		from BBB WITH (NOLOCK)
		left outer join BBBSocialMediaSite s WITH (NOLOCK) on
			s.BBBID = BBB.BBBID and s.BBBBranchID = BBB.BBBBranchID and s.SiteType = 'Facebook'
		left outer join BBBSocialMediaSite s2 WITH (NOLOCK) on
			s2.BBBID = BBB.BBBID and s2.BBBBranchID = BBB.BBBBranchID and s2.SiteType = 'Twitter'
		left outer join BBBSocialMediaSite s3 WITH (NOLOCK) on
			s3.BBBID = BBB.BBBID and s3.BBBBranchID = BBB.BBBBranchID and s3.SiteType = 'YouTube'
		WHERE
			BBB.BBBBranchID = '0' AND BBB.IsActive = '1' and
			(s.SiteAddress > '' or s2.SiteAddress > '' or s3.SiteAddress > '')
		ORDER BY NickNameCity
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('BBB City', '', '', 'left'),
				/*array('Facebook Page', '', '', 'left'),*/
				array('Twitter Account', '', '', 'left'),
				array('YouTube Address', '', '', 'left'),
				/*array('Facebook Page Likes', '', '', 'right'),*/
				array('Twitter Followers', '', '', 'right'),
				array('YouTube Views', '', '', 'right'),
				array('', '', '', 'left'),
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
			$raw_youtube_address = $fields[4]; // includes full path


			// build store button
	
			$oStoreStats = $fields[0] . "|" . /*$facebook_page . "|" .*/ $twitter_account . "|" . $raw_youtube_address;
			$store_button = "<a class=cancel_button_small " .
					"onclick=\" form1.iStoreStats.value = '{$oStoreStats}'; " .
					"form1.submit(); \">Store</a>";

			$facebook_likes = $fields[5];
			$twitter_followers = $fields[6];
			$youtube_views = $fields[7];

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[1]) . "</a>",
					/*"<a target=detail href=https://www.facebook.com/{$facebook_page}>" .
						$facebook_page . "</a>",*/
					"<a target=detail href=https://www.twitter.com/{$twitter_account}>" .
						$twitter_account . "</a>",
					"<a target=detail href=https://www.youtube.com/channel/{$youtube_address}>" .
						$youtube_address . "</a>",
					/*$facebook_likes,*/
					$twitter_followers,
					$youtube_views,
					$store_button
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