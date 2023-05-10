<?php

/*
 * 10/28/20 MJS - new file
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

function DrawMap() {
	global $conn, $SETTINGS;

	$oCenterLatitude = 30.3;
	$oCenterLongitude = -81.6;
	
	$color = "#663507";
	$color = "#A13E29";
	$opacity = 0.4;

	// map points
	$query = "
		create table #temp (xcounty varchar(50), xprop decimal(8,2) constraint pk_temp_index4 primary key (xcounty));
		insert into #temp values('Alachua, FL', '1.61');
		insert into #temp values('Baker, FL', '0.77');
		insert into #temp values('Bradford, FL', '1.62');
		insert into #temp values('Clay, FL', '3.34');
		insert into #temp values('Columbia, FL', '1.25');
		insert into #temp values('Dixie, FL', '1.58');
		insert into #temp values('Duval, FL', '4.10');
		insert into #temp values('Flagler, FL', '2.31');
		insert into #temp values('Gilchrist, FL', '0.41');
		insert into #temp values('Hamilton, FL', '0');
		insert into #temp values('Jefferson, FL', '0.39');
		insert into #temp values('Lafayette, FL', '0');
		insert into #temp values('Leon, FL', '1.48');
		insert into #temp values('Madison, FL', '1.32');
		insert into #temp values('Nassau, FL', '2.58');
		insert into #temp values('Putnam, FL', '1.76');
		insert into #temp values('St Johns, FL', '2.95');
		insert into #temp values('Suwanne, FL', '1.42');
		insert into #temp values('Taylor, FL', '0.75');
		insert into #temp values('Union, FL', '2.27');
		insert into #temp values('Appling, GA', '0.82');
		insert into #temp values('Atkinson, GA', '0');
		insert into #temp values('Bacon, GA', '0');
		insert into #temp values('Brantley, GA', '0.49');
		insert into #temp values('Brooks, GA', '0.95');
		insert into #temp values('Bryan, GA', '2.48');
		insert into #temp values('Bulloch, GA', '0.87');
		insert into #temp values('Camden, GA', '1.31');
		insert into #temp values('Candler, GA', '0.95');
		insert into #temp values('Charton, GA', '0');
		insert into #temp values('Chatham, GA', '1.67');
		insert into #temp values('Clinch, GA', '0');
		insert into #temp values('Coffee, GA', '0.48');
		insert into #temp values('Echols, GA', '0');
		insert into #temp values('Effingham, GA', '2.26');
		insert into #temp values('Evans, GA', '1.33');
		insert into #temp values('Glynn, GA', '0.81');
		insert into #temp values('Jeff Davis, GA', '0.41');
		insert into #temp values('Jenkins, GA', '0');
		insert into #temp values('Lanier, GA', '0');
		insert into #temp values('Liberty, GA', '1.81');
		insert into #temp values('Long, GA', '1.35');
		insert into #temp values('Lowndes, GA', '0.75');
		insert into #temp values('McIntosh, GA', '0.57');
		insert into #temp values('Pierce, GA', '0.91');
		insert into #temp values('Screven, GA', '0.96');
		insert into #temp values('Tattnal, GA', '0.33');
		insert into #temp values('Telfair, GA', '0');
		insert into #temp values('Toombs, GA', '0.91');
		insert into #temp values('Ware, GA', '1.05');
		insert into #temp values('Wayne, GA', '0.88');
		insert into #temp values('Wheeler, GA', '1.75');
		insert into #temp values('Allendale, SC', '0');
		insert into #temp values('Beaufort, SC', '1.06');
		insert into #temp values('Hampton, SC', '0.62');
		insert into #temp values('Jasper, SC', '1.46');
		SELECT
				BoundaryCoordinates, t.xprop, c.CountyName
			FROM BBBCounty c WITH (NOLOCK)
			INNER JOIN BBB WITH (NOLOCK) ON c.BBBIDFull = BBB.BBBID AND BBB.BBBBranchID = '0' and BBB.IsActive = '1'
			LEFT OUTER JOIN #temp t ON lower(t.xcounty) = lower(RTRIM(c.CountyName)) + ', ' + lower(c.CountyState)
			WHERE
				c.BoundaryCoordinates > '' and
				/*CountyName != 'BROOKS' and*/
				/*CountyName = 'DUVAL' and*/
				BBB.BBBID = '0403'
		drop table #temp;
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();

	$html .= "
			<script src='{$SETTINGS['GOOGLE_MAPS_API']}'></script>
			<script language=javascript>
			google.maps.Polygon.prototype.getBoundingBox = function() {
			  var bounds = new google.maps.LatLngBounds();  this.getPath().forEach(function(element,index) {
				bounds.extend(element)
			  });  return(bounds);
			};
			function loadmap() {
				var mapOptions = {
					zoom: 7,
					center: new google.maps.LatLng({$oCenterLatitude}, {$oCenterLongitude}),
					streetViewControl: false,
					draggable: true,
					mapTypeId: google.maps.MapTypeId.TERRAIN,					
					styles: [
						{ featureType: 'administrative.locality', elementType: 'labels', stylers: [{ visibility: 'off' }] },
						{ featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }
					]
				};
				var area;
				var labelOptions;
				var polygonLabel;
				var map = new google.maps.Map(document.getElementById('map_div'), mapOptions);
			var bbbmap = {};
			";
	foreach ($rs as $k => $fields) {
		$BoundaryCoordinates = $fields[0];
		$Saturation = $fields[1];
		$CountyName = $fields[2];
		$CountyName = trim(strtoupper(substr($CountyName,0,1)) . strtolower(substr($CountyName,1)));
		if ($CountyName == "Mcintosh") {
			$CountyName = "McIntosh";
		}
		if ($CountyName == "Jeff davis") {
			$CountyName = "Jeff Davis";
		}
		if ($CountyName == "St johns") {
			$CountyName = "St Johns";
		}
		if ($Saturation == "") {
			$Saturation = 0.00;
		}
		$points = explode(" ", $BoundaryCoordinates);
		$html .= "var polygonCoords_x = [ ";
		$cnt = 0;
		foreach ($points as $p) {
			$lnglat = explode(",",$p);
			$tmp0 = $lnglat[0];
			$tmp1 = $lnglat[1];
			$html .= "
				new google.maps.LatLng(
					{$tmp1},
					{$tmp0}
				), \n";
		}
		$opacity = 0.15 + (0.75 * ($Saturation / 4.20));
		$html .= "
			];
			area = new google.maps.Polygon({
				paths: polygonCoords_x,
				strokeColor: '#9D2B1D',
				strokeOpacity: 0.15,
				strokeWeight: 2,
				fillColor: '{$color}',
				fillOpacity: {$opacity}
			});
			area.setMap(map);
			";
		$Saturation_label = "";
		$label_color = "gray";
		if ($Saturation != 0.00) {
			$Saturation_label = " " . round($Saturation,0) . "%";
			$label_color = "darkbrown";
		}
		$html .= "
			var image = {
			  url: 'https://upload.wikimedia.org/wikipedia/en/4/48/Blank.JPG',
			  size: new google.maps.Size(0, 0),
			  origin: new google.maps.Point(0, 0),
			  anchor: new google.maps.Point(0, 0)
			};
			var marker = new google.maps.Marker({
				/*position: new google.maps.LatLng({$tmp1}, {$tmp0}),*/
				position: area.getBoundingBox().getCenter(),
				icon: image,
				map: map,
				label: {text: '{$CountyName}{$Saturation_label}', color: '{$label_color}', fontSize: '11px', fontFamily: 'Verdana'}
			});
			";
	}
	$html .= "
			}
			google.maps.event.addDomListener(window, 'load', loadmap);

			</script>
			";
	return $html;
}

echo DrawMap() .
	"<div class='main_section'>" .
	"<div id=map_div class='inner_section' style='height: 600px; width:100.0%; left:0%;'></div>" .
	"</div>";

SlideAll();

?>