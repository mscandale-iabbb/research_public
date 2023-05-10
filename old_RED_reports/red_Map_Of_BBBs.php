<?php

/*
 * 12/28/16 MJS - renamed, refactored, moved to RED
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

	$oCenterLatitude = 39.5;
	$oCenterLongitude = -103.0;

	// map points
	$query = "
		create table #temp (bbbid varchar(4), workers int, constraint pk_temp_index primary key (bbbid));
		insert into #temp values('0011','3693'); insert into #temp values('0021','18426');
		insert into #temp values('0041','1920'); insert into #temp values('0111','2124');
		insert into #temp values('0121','58379'); insert into #temp values('0141','3823');
		insert into #temp values('0221','947'); insert into #temp values('0241','48485');
		insert into #temp values('0261','1642'); insert into #temp values('0272','726');
		insert into #temp values('0292','2536'); insert into #temp values('0302','4037');
		insert into #temp values('0312','1764'); insert into #temp values('0322','905');
		insert into #temp values('0332','3175'); insert into #temp values('0352','1367');
		insert into #temp values('0372','1728'); insert into #temp values('0382','1821');
		insert into #temp values('0402','1080'); insert into #temp values('0443','8148');
		insert into #temp values('0473','386'); insert into #temp values('0503','879');
		insert into #temp values('0543','875'); insert into #temp values('0573','975');
		insert into #temp values('0583','2718'); insert into #temp values('0593','649');
		insert into #temp values('0603','2592'); insert into #temp values('0633','6747');
		insert into #temp values('0653','1538'); insert into #temp values('0654','17866');
		insert into #temp values('0664','116');
		insert into #temp values('0674','3346'); insert into #temp values('0704','10213');
		insert into #temp values('0714','1206'); insert into #temp values('0733','941');
		insert into #temp values('0734','5641'); insert into #temp values('0785','957');
		insert into #temp values('0825','2710'); insert into #temp values('0835','782');
		insert into #temp values('0875','7864'); insert into #temp values('0885','848');
		insert into #temp values('0915','6029'); insert into #temp values('0935','262');
		insert into #temp values('0985','617'); insert into #temp values('0995','505');
		insert into #temp values('1025','709'); insert into #temp values('1066','246');
		insert into #temp values('1086','233'); insert into #temp values('1116','16685');
		insert into #temp values('1126','3470'); insert into #temp values('1156','2949');
		insert into #temp values('1166','339'); insert into #temp values('1216','27860');
		insert into #temp values('1236','640'); insert into #temp values('1286','675');
		insert into #temp values('1296','8524');

		select
			BBB.Latitude, BBB.Longitude, BBB.BBBID,
			/*case when BBB.BBBBranchID = 0 then 3000 else 1200 end as BBBSize,*/
			3.00 * 1008.00 * (1.00 / (AVG(geography::Point(BBB.Latitude, BBB.Longitude, 4326).STDistance(geography::Point(BBB2.Latitude, BBB2.Longitude, 4326)) / 1609.344) / 1008.00))
			/* (SQRT(#temp.workers) * 50) */
		from BBB
		inner join BBB BBB2 on BBB2.BBBID != BBB.BBBID
		left outer /*inner*/ join #temp on #temp.bbbid = BBB.BBBID
		where
			BBB.BBBBranchID = 0 and BBB.IsActive = '1' and
			BBB.Latitude is not null and BBB.Longitude is not null and
			/*BBB.Latitude > -999 and BBB.Latitude < 999 and*/
			BBB.BBBID != '8888' and
			BBB2.BBBBranchID = 0 and BBB2.IsActive = '1' and
			BBB2.Latitude is not null and BBB2.Longitude is not null
		group by BBB.BBBID, BBB.Latitude, BBB.Longitude, #temp.workers;
		drop table #temp;
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();

	$html .= "
			<script src='{$SETTINGS['GOOGLE_MAPS_API']}'></script>
			<script language=javascript>
			var bbbmap = {};
			";
		foreach ($rs as $k => $fields) {
			$oLatitude = $fields[0];
			$oLongitude = $fields[1];
			$oBBBID = $fields[2];
			$oBBBSize = $fields[3];
			$oAvgDistance = $fields[4];
			$html .= "bbbmap['z{$oBBBID}'] = { " .
				"center: new google.maps.LatLng({$oLatitude}, {$oLongitude}), " .
				"bbbkey: '{$oBBBID}', " .
				"bbbsize: {$oBBBSize} " .
				"}; \n";
		}
		$html .= "
			var bbbcircle;
			function loadmap() {
				var mapOptions = {
					zoom: 4,
					center: new google.maps.LatLng({$oCenterLatitude}, {$oCenterLongitude}),
					streetViewControl: false,
					disableDefaultUI: true,
					draggable: true,
					scrollwheel: true,
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
				}
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