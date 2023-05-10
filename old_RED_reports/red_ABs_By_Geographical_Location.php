<?php

/*
 * 10/14/16 MJS - changed Google Maps API
 * 01/03/17 MJS - changed calls to define links and tabs
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = $_REQUEST['iBBBID'];
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray() );
$input_form->AddSubmitButton();
$input_form->Close();

if (! $_POST) return;

/*
OpenGoogleAPI('map', 'Map');
echo "	data.addColumn('number', 'Lat');
	data.addColumn('number', 'Lon');
	data.addColumn('string', 'Name');
	";
$query = "select TOP 400
		z.Longitude,
		z.Latitude
	from Business b WITH (NOLOCK)
	inner join tblZipCoordinates z WITH (NOLOCK) on
		(LEN(z.Zip) = 5 and z.Zip = LEFT(b.PostalCode,5)) or
		(LEN(z.Zip) = 3 and z.Zip = LEFT(b.PostalCode,3))
	where
		b.BBBID = '" . $iBBBID . "' and
		b.IsBBBAccredited = '1' and
		z.Longitude is not null and z.Latitude is not null
	group by z.Longitude, z.Latitude";
$rsraw = $conn->execute("$query");
$rs = $rsraw->GetArray();
foreach ($rs as $k => $fields) {
	$oLongitude = $fields[0];
	$oLatitude = $fields[1];
	$oConsumerName = 'Lat: ' . $oLatitude . ', Lon: ' . $oLongitude;

	echo "data.addRow([
		" . $oLatitude . ",
		" . $oLongitude . ",
		'" . $oConsumerName . "'
		]); \n";
}
if (count($rs) > 0) {
	echo "	output.draw(data, {
		showTip: true,
		useMapTypeControl: true,
		mapType: 'terrain'
		}); \n";
}
CloseGoogleAPI();
*/

$query = "select BBB.Latitude, BBB.Longitude, BBB.Country
	from BBB WITH (NOLOCK)
	where BBB.BBBID = '{$iBBBID}' and BBB.BBBBranchID = 0";
$rsraw = $conn->execute("$query");
$rs = $rsraw->GetArray();
foreach ($rs as $k => $fields) {
	$oCenterLatitude = $fields[0];
	$oCenterLongitude = $fields[1];
	$oBBBCountry = $fields[2];
}
/*
if ($oBBBCountry == 'Canada') $numdigits = 3;
else $numdigits = 5;

$query = "select TOP 1000
		z.Latitude,
		z.Longitude,
		z.Zip,
		count(*),
		(select [2011Establishments] from ZipEstablishments e WITH (NOLOCK)
			where e.Zipcode = z.Zip)
	from Business b WITH (NOLOCK)
	inner join tblZipCoordinates z WITH (NOLOCK) on 
		z.Zip = LEFT(b.PostalCode," . $numdigits . ")
	where
		b.BBBID = '" . $iBBBID . "' and
		b.IsBBBAccredited = '1' and
		z.Longitude is not null and z.Latitude is not null
	group by z.Zip, z.Longitude, z.Latitude
	order by count(*) desc";
*/

$query = "select TOP 1000
		a.Latitude,
		a.Longitude,
		count(*)
	from Business b WITH (NOLOCK)
	inner join BusinessAddress a WITH (NOLOCK) on 
		a.BBBID = b.BBBID and a.BusinessID = b.BusinessID and a.IsPrimaryAddress = '1'
	where
		b.BBBID = '" . $iBBBID . "' and
		b.IsBBBAccredited = '1' and
		a.Longitude is not null and a.Latitude is not null
	group by a.Latitude, a.Longitude
	order by count(*) desc
	";
$rsraw = $conn->execute($query);
$rs = $rsraw->GetArray();

echo "<script src='{$SETTINGS['GOOGLE_MAPS_API']}' xxsrc='https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false'></script>
	<script language=javascript>
	var businessmap = {}; ";
foreach ($rs as $k => $fields) {
	$oLatitude = $fields[0];
	$oLongitude = $fields[1];
	$oABs = $fields[2];
	$oKey = $oLatitude . "," . $oLongitude;
	echo "businessmap['z" . $oKey . "'] = { " .
		"center: new google.maps.LatLng(" . $oLatitude . ", " . $oLongitude . "), " .
		"popuptext: '<b>Coordinates " . $oKey . "</b><br/>" .
			"ABs: " . AddComma($oABs) .
			"', " .
		"ABs: '" . $oABs . "', " .
		"businesskey: '" . $oKey . "' " .
		"}; \n";
}
echo <<< EOT
	var businesscircle;
	function loadmap() {
		var mapOptions = {
			zoom: 10,
			center: new google.maps.LatLng($oCenterLatitude, $oCenterLongitude),
			streetViewControl: false,
			mapTypeId: google.maps.MapTypeId.TERRAIN
		};
		var map = new google.maps.Map(document.getElementById('google_div'), mapOptions);
		businesscircle = [];
		for (var business in businessmap) {
			var circleoptions = {
				strokeColor: '#BF2D19',
				strokeOpacity: 0.8,
				strokeWeight: 1,
				fillColor: '#9D2B1D',
				fillOpacity: 0.35,
				map: map,
				center: businessmap[business].center,
				radius: (100 * businessmap[business].ABs)
			};
			businesscircle[businessmap[business].businesskey] = new google.maps.Circle(circleoptions);
			businesscircle[businessmap[business].businesskey].infowindowtext =
				'<div style=\'width: 230px; height: 80px; text-align:right\'>' + businessmap[business].popuptext + '</div>';
			businesscircle[businessmap[business].businesskey].infowindow = new google.maps.InfoWindow();
			google.maps.event.addListener(businesscircle[businessmap[business].businesskey], 'click', function() {
				for (var tmp in businesscircle) {
					businesscircle[tmp].infowindow.close();  // close all open ones
				}
				this.infowindow.setContent(this.infowindowtext);
				this.infowindow.open(map, this);
			});

		}
	}
	google.maps.event.addDomListener(window, 'load', loadmap);
	</script>
EOT;

echo "<div class='main_section roundedtop'>";
echo "<div id=google_div class='inner_section' style='height: 600px;'></div>";
echo "</div>";

echo "<br/>";


SlideAll();

$page->write_pagebottom();

?>