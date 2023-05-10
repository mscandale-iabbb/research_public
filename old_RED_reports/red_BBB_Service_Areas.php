<?php

/*
 * 08/10/16 MJS - new file
 * 08/19/16 MJS - added states where bbb main offices aren't located
 * 08/29/16 MJS - replaced old report
 * 08/29/16 MJS - shuffle colors each time
 * 08/30/16 MJS - added IsActive
 * 08/30/16 MJS - support multiple states
 * 08/31/16 MJS - added more colors, more opacity levels
 * 08/31/16 MJS - added labels
 * 08/31/16 MJS - added Canadian provinces
 * 08/31/16 MJS - fixed bug with BBBs in other states
 * 09/01/16 MJS - fixed Canadian data out of bounds
 * 09/01/16 MJS - added support for Mexico
 * 01/09/17 MJS - changed calls to define links and tabs
 */

// data available at http://theplacename.com/category/counties
// some data available at http://maps.tomliebert.com/place/ventura-county/48631
// Canadian data available at https://www12.statcan.gc.ca/census-recensement/2011/geo/bound-limit/bound-limit-2011-eng.cfm

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iState = NoApost($_POST['iState']);

function Special_BuildStatesArray() {
	global $conn;
	$states = array("Select" => "");
	$query = "
			SELECT
				StateAbbreviation, StateNameProper
			FROM tblStates s WITH (NOLOCK)
			WHERE
				s.StateAbbreviation > '' AND
				(
					(select count(*) from BBBCounty c WITH (NOLOCK) where
						c.CountyState = s.StateAbbreviation) > 0 or
					(select count(*) from tblZipCoordinates z WITH (NOLOCK) where
						z.State = s.StateAbbreviation and z.BoundaryCoordinates > '') > 0
				)
			ORDER BY s.StateNameProper";
	$r = $conn->execute($query);
	$r->MoveFirst();
	while (! $r->EOF) {
		$StateAbbreviation = $r->fields[0];
		$StateNameProper = $r->fields[1];
		$states[$StateNameProper] = $StateAbbreviation;
		$r->MoveNext();
	}
	return($states);
}

$input_form = new input_form($conn);
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
		Special_BuildStatesArray(), '', '', '', 'width:400px');
$input_form->AddSubmitButton();
$input_form->Close();

if (! $_POST) return;

//$colors = ['#076989', '#2F5B00', '#BD854B', '#F5E9A3', '#BF2D19', '#9D2B1D', '#43859B', '#2991B3'];
$colors = [
	'#32302c',
	'#44423e',
	'#868686',
	'#dedede',
	'#004b64',
	'#086a8a',
	'#43859b',
	'#cae0e5',
	'#527d1e',
	'#709f39',
	'#a8cc7e',
	'#9e2b1e',
	'#d0880e',
	'#f0a017',
	'#a97444',
	'#fde299',
	'#E0695C',
	'#F4CCC8',
	'#DCC1A7',
	'#F6CC83'
];
shuffle($colors);

// map center
$query = "
	select TOP 1 BBB.Latitude, BBB.Longitude from BBB WITH (NOLOCK) where
	(
		BBB.State IN ('" . str_replace(",", "','", $iState) . "') or
		(select count(*) from BBBCounty c WITH (NOLOCK) where
			c.BBBIDFull = BBB.BBBIDFull and c.CountyState IN ('" . str_replace(",", "','", $iState) . "') ) > 0 or
		(select count(*) from tblZipCoordinates z WITH (NOLOCK) where
			z.BBBID = BBB.BBBIDFull and z.BoundaryCoordinates > '' and
			z.State IN ('" . str_replace(",", "','", $iState) . "') ) > 0
	) and BBB.BBBBranchID = '0' and BBB.IsActive = '1'";
$rsraw = $conn->execute($query);
$rs = $rsraw->GetArray();
foreach ($rs as $k => $fields) {
	$oCenterLatitude = $fields[0];
	$oCenterLongitude = $fields[1];
}

// start map
echo <<< EOT
	<script src='{$SETTINGS['GOOGLE_MAPS_API']}' xxsrc='https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false'></script>
	<script language=javascript>

	function loadmap() {
		var mapOptions = {
			zoom: 6,
			center: new google.maps.LatLng($oCenterLatitude, $oCenterLongitude),
			streetViewControl: false,
			draggable: true,
			mapTypeId: google.maps.MapTypeId.TERRAIN
		};
		var area;
		var labelOptions;
		var polygonLabel;
		var map = new google.maps.Map(document.getElementById('google_div'), mapOptions);
EOT;



$opacity = 0.4;
$color = $colors[$bbb_count];


// get each bbb in state
$bbbs_query = "
	select
		BBB.BBBID, BBB.NicknameCity, BBB.Latitude, BBB.Longitude,
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(BBB.Name,'BBB of the',''),'BBB Serving the',''),'Better Business Bureau of the',''),'Better Business Bureau Serving the',''),'BBB of',''),'BBB Serving',''),'Better Business Bureau of',''),'Better Business Bureau Serving',''),
		BBB.Country
	from BBB WITH (NOLOCK) where
		(
			BBB.State IN ('" . str_replace(",", "','", $iState) . "') or
			(select count(*) from BBBCounty c WITH (NOLOCK) where
				c.BBBIDFull = BBB.BBBIDFull and c.CountyState IN ('" . str_replace(",", "','", $iState) . "') ) > 0 or
			(select count(*) from tblZipCoordinates z WITH (NOLOCK) where
				z.BBBID = BBB.BBBIDFull and z.BoundaryCoordinates > '' and
				z.State IN ('" . str_replace(",", "','", $iState) . "') ) > 0
		) and BBB.BBBBranchID = '0' and BBB.IsActive = '1'
	ORDER BY BBB.Latitude, BBB.Longitude";
$bbbsraw = $conn->execute($bbbs_query);
$bbbs = $bbbsraw->GetArray();
foreach ($bbbs as $k => $fields) {
	$oBBBID = $fields[0];
	$oNicknameCity = $fields[1];
	$oBBBCenterLatitude = $fields[2];
	$oBBBCenterLongitude = $fields[3];
	$oName = $fields[4];
	$oCountry = $fields[5];

	// get each county of bbb
	if ($oCountry == 'US' || $oCountry == 'Mexico') $counties_query = "
		SELECT
			BoundaryCoordinates
		FROM BBBCounty c WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) ON c.BBBIDFull = BBB.BBBID AND BBB.BBBBranchID = '0' and BBB.IsActive = '1'
		WHERE
			c.BoundaryCoordinates > '' and
			BBB.BBBID = '{$oBBBID}'
		";
	else $counties_query = "
		SELECT
			BoundaryCoordinates
		FROM tblZipCoordinates z WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) ON z.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0' and BBB.IsActive = '1'
		WHERE
			z.BoundaryCoordinates > '' and BBB.BBBID = '{$oBBBID}' and
			z.Latitude >= 1 and z.Longitude <= -1 and
			z.Zip != 'R0G'
		ORDER By z.Latitude, z.Longitude
		";
	$counties_raw = $conn->execute($counties_query);
	$counties = $counties_raw->GetArray();

	// draw a bbb's counties
	foreach ($counties as $k => $fields) {
		$BoundaryCoordinates = $fields[0];
		$points = explode(" ", $BoundaryCoordinates);
		echo "var polygonCoords_x = [ ";
		$cnt = 0;
		foreach ($points as $p) {
			$lnglat = explode(",",$p);
			$tmp0 = $lnglat[0];
			$tmp1 = $lnglat[1];
			// reverse if Canada
			if ($oCountry == 'Canada') {
				$tmp1 = $lnglat[0];
				$tmp0 = $lnglat[1];
				if ($tmp0 > 1 || $tmp1 < -1 || $tmp1 < 40 || $tmp1 > 99 || $tmp0 > -50 || $tmp0 < -150) continue;
			}
			echo "
				new google.maps.LatLng(
					{$tmp1},
					{$tmp0}
				), \n";
		}
		echo "
			];
			area = new google.maps.Polygon({
				paths: polygonCoords_x,
				/*strokeColor: '#9D2B1D',*/
				strokeColor: '{$color}',
				strokeOpacity: 0.35,
				/*strokeWeight: 2,*/
				strokeWeight: 0,
				fillColor: '{$color}',
				fillOpacity: {$opacity}
			});
			area.setMap(map);
			";
	}
	echo "
        var image = {
          url: 'https://upload.wikimedia.org/wikipedia/en/4/48/Blank.JPG',
          size: new google.maps.Size(0, 0),
          origin: new google.maps.Point(0, 0),
          anchor: new google.maps.Point(0, 0)
        };
		var marker = new google.maps.Marker({
			position: new google.maps.LatLng({$oBBBCenterLatitude}, {$oBBBCenterLongitude}),
			icon: image,
			map: map
		});
		var infowindow = new google.maps.InfoWindow({
			content: '<span class=\"verysmallfont\">{$oName}</span>'
		});
		infowindow.open(map, marker);

		/*
		var overlay = new LabelOverlay(
			new google.maps.LatLng({$oBBBCenterLatitude}, {$oBBBCenterLongitude}),
			'{$oName}', 'bold normalsizefont black xextrathinpadding', map
		);
		*/
		";

	// next bbb
	$bbb_count++;
	if ($bbb_count > count($colors)) $bbb_count = 0; 
	$color = $colors[$bbb_count];
	if ($opacity == 0.4) $opacity = 0.6;
	else if ($opacity == 0.6) $opacity = 0.8;
	else if ($opacity == 0.8) $opacity = 0.4;

}

// finish map

echo <<< EOT
	}

	/*
	LabelOverlay.prototype = new google.maps.OverlayView();
	function LabelOverlay(pos, txt, cls, map) {
		this.pos = pos;
		this.txt_ = txt;
		this.cls_ = cls;
		this.map_ = map;
		this.setMap(map);
	}
	LabelOverlay.prototype.onAdd = function() {	
		var div = document.createElement('div');
		div.className = this.cls_;
		div.innerHTML = this.txt_;
		//div.style.borderStyle = 'none';
		//div.style.borderWidth = '0px';
		//this.div_ = div;
		var overlayProjection = this.getProjection();
		var position = overlayProjection.fromLatLngToDivPixel(this.pos);
		div.style.position = 'absolute';
		div.style.left = position.x + 'px';
		div.style.top = position.y + 'px';
		var panes = this.getPanes();
		panes.floatPane.appendChild(div);
		//panes.overlayLayer.appendChild(div);
		//panes.mapPane.appendChild(div);
	};
	*/

	google.maps.event.addDomListener(window, 'load', loadmap);
	</script>
EOT;


echo "
	<div class='main_section roundedtop'>
	<div id=google_div class='inner_section' style='height: 600px;'></div>
	</div>
	<br/>
	";

SlideAll();

$page->write_pagebottom();

?>