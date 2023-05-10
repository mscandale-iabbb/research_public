<?php

/*
 * RED Reports main menu page
 *
 * @author	MJS
 * @copyright CBBB
 * @written	12/06/13
 * @revised	06/20/14 - revised map to add lines
 * @revised	06/24/14 - made map expandable on click, only show charts & map when showing all, added IE7 warning
 * @revised	06/25/14 - enhanced map, added series of icons for reports
 * @revised	11/24/15 - added support for CBBB-only menu
 * @revised	12/04/15 - added search box
 * @revised	12/10/15 - moved search box to left side
 * @revised	12/14/15 - modified search to look at all folders
 * @revised 01/07/16 - added CEOOnly criteria
 * @revised 01/08/16 - added label to search box
 * @revised 04/01/16 - added Evaluations menu
 * @revised 10/13/16 - removed live map
 * @revised 10/14/16 - changed Google Maps API
 * @revised 01/03/17 - changed calls to define links and tabs
 * @revised 11/14/18 - changed to ICONS_PATH
 * @revised 05/16/19 - used SETTINGS for org name
 *
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);
include 'getceostatus.php';

$iFolderName = NoApost($_REQUEST['iFolderName']);
$iSearch = NoApost($_POST['iSearch']);
if ($iSearch) $iFolderName = '';  // when searching, search all folders

$icon_files = array(
	'map' => 'red_report_map_icon.png',
	'chart' => 'red_report_chart_icon.png',
	'report' => 'red_report_table_icon.png',
	'records' => 'red_report_search_icon.png',
	'page' => 'red_report_notebook_icon.png',
	'download' => 'red_report_download_icon.png',
	'' => 'red_report_paper_icon.png',
);
// http://www.mricons.com/show/map-icons


$browser = trim( substr($_SERVER['HTTP_USER_AGENT'], 0, 255) );
if (strpos($browser, 'MSIE 7.0;') == true) {
	echo "<p class='alert_message red'>Warning: You are using an older version of a browser that needs to be upgraded.</p>";
}
//else if (strpos($browser, '11.0') == true && strpos($browser, 'like Gecko') == true) {
else if (strpos($browser, 'Trident') == true) {
	echo "<p class='alert_message move_up_line red'>Warning: Please use Chrome or Firefox browsers for best results.</p>";
}

echo "<div class='main_section'>";

function DrawChart($query, $title, $conn) {
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs)) {

		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
	
		$barchart = new barchart($vals);

		$barchart->highest = 125;
		$barchart->bar_max_height = 50;
		$barchart->chart_height = 85;
		$barchart->chart_width = 200;
		$barchart->precision = 1;
		$barchart->offset_factor = 50;
		$barchart->bar_width = 35;
		$barchart->label_width = 50;
		$barchart->gridlines = 5;
		$barchart->opacity = 0.60;
		$barchart->x_axis_margin = 15;
		$barchart->gridline_indent = 35;
		$barchart->label_position = -2;

		$barchart->Open('suppress_average');
	
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$oDate = substr(FormatDate($fields[0]),0,5);
			$oPercentage = round($fields[1],1) . '%';
			$barchart->DrawBar($xcount, $oPercentage, $oDate);
		}
		$barchart->DrawTitle($title, 10);
		$barchart->Close();
	}
}

function DrawCharts($conn) {
	echo "<div id=charts_div class='inner_section sectionback' style='height: 113px;'>";

	// chart 1 of 4
	$query = "select TOP 3
			DateGenerated,
			100 * ROUND((
				CAST (REDComplaintsYTD AS DECIMAL(14,2))
				/
				CAST (LocalComplaintsYTD AS DECIMAL(14,2))
			),3,1) as Percentage
		FROM tblDataWarehouseAccuracy WITH (NOLOCK)
		ORDER BY DateGenerated DESC";
	DrawChart($query, 'RED Complaints to local DBs', $conn);
	
	// chart 2 of 4
	$query = "select TOP 3
			DateGenerated,
			100 * ROUND((
				CAST (REDInquiriesYTD AS DECIMAL(14,2))
				/
				CAST (LocalInquiriesYTD AS DECIMAL(14,2))
			),3,1) as Percentage
		FROM tblDataWarehouseAccuracy WITH (NOLOCK)
		ORDER BY DateGenerated DESC";
	DrawChart($query, 'RED Inquiries to local DBs', $conn);
	
	// chart 3 of 4
	$query = "select TOP 3
			DateGenerated,
			100 * ROUND((
				CAST (REDBusinesses AS DECIMAL(14,2))
				/
				CAST (LocalBusinesses AS DECIMAL(14,2))
			),3,1) as Percentage
		FROM tblDataWarehouseAccuracy WITH (NOLOCK)
		ORDER BY DateGenerated DESC";
	DrawChart($query, 'RED Businesses to local DBs', $conn);
	
	// chart 4 of 4
	$query = "select TOP 3
			DateGenerated,
			100 * ROUND((
				CAST (REDABs AS DECIMAL(14,2))
				/
				CAST (LocalABs AS DECIMAL(14,2))
			),3,1) as Percentage
		FROM tblDataWarehouseAccuracy WITH (NOLOCK)
		ORDER BY DateGenerated DESC";
	DrawChart($query, 'RED ABs to local DBs', $conn);

	echo "</div>";
}

function DrawMap($conn) {
	global $SETTINGS;

	echo "<div id=map_div class='inner_section sectionback' style='height: 225px; width:70.0%; left:13%;' ></div>";

	// map center
	$oCenterLatitude = 39.5;
	$oCenterLongitude = -100.0;

	// cbbb location
	$query = "select BBB.Latitude, BBB.Longitude from BBB WITH (NOLOCK) where BBB.State = 'DC'";
	$rsraw = $conn->execute("$query");
	$rs = $rsraw->GetArray();
	foreach ($rs as $k => $fields) {
		$oCBBBLatitude = $fields[0];
		$oCBBBLongitude = $fields[1];
	}

	// map points
	$query = "select BBB.Latitude, BBB.Longitude, BBB.BBBID,
			(select CountOfBillableABs from qaBusiness q WITH (NOLOCK)where 
				q.BBBID = BBB.BBBID)
		from BBB WITH (NOLOCK) where
		BBB.IsActive = 1 and BBB.BBBBranchID = 0 and
		BBB.Latitude is not null and BBB.Longitude is not null and
		BBB.Latitude > -999 and BBB.Latitude < 999";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	echo "
		<script src='{$SETTINGS['GOOGLE_MAPS_API']}'></script>
		<script language=javascript>
		var bbbmap = {};
		";
	foreach ($rs as $k => $fields) {
		$oLatitude = $fields[0];
		$oLongitude = $fields[1];
		$oBBBID = $fields[2];
		$oBBBSize = $fields[3];
		echo "bbbmap['z" . $oBBBID . "'] = { " .
			"center: new google.maps.LatLng(" . $oLatitude . ", " . $oLongitude . "), " .
			"bbbkey: '" . $oBBBID . "', " .
			"bbbsize: " . $oBBBSize .
			"}; \n";
	}
	echo "
		var bbbcircle;
		function loadmap() {
			var mapOptions = {
				zoom: 3,
				center: new google.maps.LatLng($oCenterLatitude, $oCenterLongitude),
				streetViewControl: false,
				disableDefaultUI: true,
				draggable: true,
				scrollwheel: true,
				/*
				StreetViewControlOptions: {},
				draggable: false,
				mapTypeControl: false,
				keyboardShortcuts: false,
				overviewMapControl: false,
				rotateControl: false,
				panControl: false,
				scaleControl: false,
				zoomControl: false,
				*/
				mapTypeId: google.maps.MapTypeId.TERRAIN
			};
			var map = new google.maps.Map(document.getElementById('map_div'), mapOptions);
			bbbcircle = [];
			for (var bbb in bbbmap) {
				var circleoptions = {
					strokeColor: '#BF2D19',
					strokeOpacity: 0.8,
					strokeWeight: 1,
					fillColor: '#9D2B1D',
					fillOpacity: 0.50,
					map: map,
					center: bbbmap[bbb].center,
					radius: bbbmap[bbb].bbbsize * 15
				};
				bbbcircle[bbbmap[bbb].bbbkey] = new google.maps.Circle(circleoptions);

			
				lineCoords = [ bbbmap[bbb].center,
					new google.maps.LatLng(" . $oCBBBLatitude . ", " . $oCBBBLongitude . ")
					];
				line = new google.maps.Polyline({
					path: lineCoords,
					strokeColor: '#BF2D19',
					strokeOpacity: 0.8,
					strokeWeight: 1 /*,
					fillColor: '#9D2B1D',
					fillOpacity: 0.35 */
				});
				line.setMap(map);

			}
			/*
			var infowindow = new google.maps.InfoWindow();
			infowindow.setContent(
				'<div class=\'darkblue lightgray01back extrathinpadding\' style=\'width: 108px; height: 16px; text-align:center; font-weight: bold;\'>RED Data Sources</div>'
			);
			infowindow.setPosition(
				new google.maps.LatLng(46.5, -77)
			);
			infowindow.open(map);
			*/
		}
		google.maps.event.addDomListener(window, 'load', loadmap);
		</script>";
}

// map
if (! $iFolderName && ! $iSearch) {  // hide map if on a particular folder and/or if searching
	/*
	echo "<table class='report_table'><tr><td colspan=2 class='column_header center'>
		<span class='section_title'>RED Data Sources</span></table>";
	echo "<table class='report_table'><tr><td colspan=2 class='column_header center thinpadding'>";
	DrawMap($conn);
	echo "</table>";
	*/
}

echo "<table class='report_table'>";

// search form
echo "<tr><td colspan=2 class='column_header left'>
	<form id=form1 method=post>RED reports pertaining to <input type=text id=iSearch name=iSearch style='width:10%' />
	<img src='{$ICONS_PATH}red_report_search_icon.png' onclick='form1.submit();'/>
	</form>";

$query = "
	SELECT
		r.FolderName
	FROM tblDataWarehouseReports r WITH (NOLOCK)
	WHERE
		(r.FolderName = '{$iFolderName}' OR '{$iFolderName}' = '') and
		('{$BBBID}' = '2000' OR r.FolderName != '{$SETTINGS['ORG_NAME']} Only' OR r.FolderName != 'Evaluations') and
		('{$oCEOStatus}' = '1' OR r.CEOOnly = '0' or r.CEOOnly is null)
	GROUP BY r.FolderName
	ORDER BY r.FolderName
	";
$rs = $conn->execute($query);
foreach ($rs as $k => $row) {
	$oFolderName = $rs->fields[0];
	$subquery = "
		SELECT
			r.ReportFileName,
			r.ReportPublicName,
			r.ReportDescription,
			r.ReportIconType
		FROM tblDataWarehouseReports r WITH (NOLOCK)
		WHERE
			r.FolderName = '{$oFolderName}' and
			('{$BBBID}' = '2000' OR r.FolderName != '{$SETTINGS['ORG_NAME']} Only' OR r.FolderName != 'Evaluations') and
			('{$oCEOStatus}' = '1' OR r.CEOOnly = '0' or r.CEOOnly is null) and
			(
				'{$iSearch}' = '' or
				r.ReportPublicName like '%{$iSearch}%' or
				r.ReportDescription like '%{$iSearch}%'
			) and
			r.ReportPublicName > '' and r.ReportFileName > ''
		ORDER BY r.ReportPublicName
		";
	$rs2 = $conn->execute($subquery);
	$rowcount = 0;
	foreach ($rs2 as $k => $row) {
		$oReportFileName = "red_" . $rs2->fields[0] . ".php";
		$oReportPublicName = $rs2->fields[1];
		$oReportDescription = $rs2->fields[2];
		$oReportIconType = $rs2->fields[3];

		if (! file_exists($oReportFileName)) continue;

		$rowcount++;

		// header
		if ($rowcount == 1)
			echo "<tr><td colspan=2 class='column_header center'><span class='section_title'>" . $oFolderName . "</span>";

		echo "<tr><td class='table_cell'>";
		if (file_exists($oReportFileName)) $class = '';
		else $class = 'gray01';
		if (file_exists($oReportFileName)) echo "<a href=" . $oReportFileName . ">";
		if ($icon_files[$oReportIconType]) {
			echo "<img src=" . $ICONS_PATH . $icon_files[$oReportIconType] .
				" " . "height=16 width=16 />" . " &nbsp; ";
		}
		echo "<span class='" . $class . "'>" . $oReportPublicName . "</span>";
		if (file_exists($oReportFileName)) echo "</a>";
		echo "<td class='table_cell'><span class='" . $class . "'>" . $oReportDescription . "</span>";
	}
}

echo "</table>";

if (! $iFolderName) {
	//DrawCharts($conn);
	//DrawMap($conn);
	/*
	echo "<table class='report_table'><tr><td colspan=2 class='column_header center'>";
	echo "<p> &nbsp; </p>";
	echo "<p class='gray smallfont' align=left>
		All information is confidential and shouldn't be shared outside the BBB system without the
		express authorization of the BBB that supplied the data. The data isn't for personal use.
		No consumers or businesses should be contacted without first checking with the originating BBB.
		No consumer information should be given out to anyone without first obtaining permission from that
		consumer. Complaint analysis should be reported to the originating BBB and any conclusions should
		be confirmed with the originating BBB.
		</p>";
	echo "</table>";
	*/
}
echo "</div>";

$page->SlideAll();

?>