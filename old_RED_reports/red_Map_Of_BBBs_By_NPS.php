<?php

/*
 * 10/24/19 MJS - new file
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
		create table #temp (bbbid varchar(4), nps decimal(38,32), constraint pk_temp_index primary key (bbbid));
		insert into #temp values('0121', '19.5488721804511');
		insert into #temp values('0372', '11.2068965517241');
		insert into #temp values('0633', '31.4814814814815');
		insert into #temp values('0673', '50');
		insert into #temp values('0845', '31.8181818181818');
		insert into #temp values('0945', '28.5714285714286');
		insert into #temp values('1015', '34.0909090909091');
		insert into #temp values('1126', '8.19672131147541');
		insert into #temp values('1156', '20.3389830508475');
		insert into #temp values('0057', '-5.08474576271186');
		insert into #temp values('0187', '15.8536585365854');
		insert into #temp values('0653', '23.6559139784946');
		insert into #temp values('0694', '3.83386581469649');
		insert into #temp values('0806', '10.3448275862069');
		insert into #temp values('0895', '57.0247933884297');
		insert into #temp values('1066', '32.2834645669291');
		insert into #temp values('1166', '3.03030303030303');
		insert into #temp values('1216', '-1.85185185185185');
		insert into #temp values('0041', '-2.7027027027027');
		insert into #temp values('0402', '30.7017543859649');
		insert into #temp values('0403', '52.1739130434783');
		insert into #temp values('0422', '23.4939759036145');
		insert into #temp values('0543', '35.8851674641148');
		insert into #temp values('0593', '12.6760563380282');
		insert into #temp values('0663', '28.5714285714286');
		insert into #temp values('0733', '25.4681647940075');
		insert into #temp values('0875', '20.3703703703704');
		insert into #temp values('0261', '23.4234234234234');
		insert into #temp values('0302', '17.4698795180723');
		insert into #temp values('0432', '42.4242424242424');
		insert into #temp values('0503', '23.9700374531835');
		insert into #temp values('0533', '24.390243902439');
		insert into #temp values('0613', '25.6944444444444');
		insert into #temp values('0704', '-3.19148936170213');
		insert into #temp values('0724', '36.6666666666667');
		insert into #temp values('0885', '4.58015267175572');
		insert into #temp values('1055', '27.7777777777778');
		insert into #temp values('1075', '57.8947368421053');
		insert into #temp values('0087', '13.5416666666667');
		insert into #temp values('0111', '10.2362204724409');
		insert into #temp values('0272', '35.7142857142857');
		insert into #temp values('0292', '18.0156657963446');
		insert into #temp values('0322', '13.7931034482759');
		insert into #temp values('0523', '7.14285714285714');
		insert into #temp values('0573', '16.6666666666667');
		insert into #temp values('0603', '-1.81818181818182');
		insert into #temp values('0743', '41.025641025641');
		insert into #temp values('0805', '14.8148148148148');
		insert into #temp values('1025', '22');
		insert into #temp values('1086', '31.6939890710382');
		insert into #temp values('0011', '2.7027027027027');
		insert into #temp values('0017', '-1.29310344827586');
		insert into #temp values('0167', '-18.75');
		insert into #temp values('0251', '34.8837209302326');
		insert into #temp values('0352', '11.1111111111111');
		insert into #temp values('0473', '31.9587628865979');
		insert into #temp values('0654', '21.484375');
		insert into #temp values('0683', '37.5');
		insert into #temp values('0693', '32.5842696629214');
		insert into #temp values('0714', '8.26210826210826');
		insert into #temp values('0785', '25.2873563218391');
		insert into #temp values('0915', '21.9047619047619');
		insert into #temp values('0117', '5.16129032258065');
		insert into #temp values('0221', '45.3333333333333');
		insert into #temp values('0241', '15.748031496063');
		insert into #temp values('0312', '16.7330677290837');
		insert into #temp values('0463', '26.4367816091954');
		insert into #temp values('0482', '15.7894736842105');
		insert into #temp values('0483', '42.8571428571429');
		insert into #temp values('0513', '46.7289719626168');
		insert into #temp values('0664', '16.3934426229508');
		insert into #temp values('0795', '40');
		insert into #temp values('0815', '16.1290322580645');
		insert into #temp values('0935', '36.7816091954023');
		insert into #temp values('0995', '22.2222222222222');
		insert into #temp values('1116', '6.77966101694916');
		insert into #temp values('1286', '17.4603174603175');
		insert into #temp values('0027', '-7.69230769230769');
		insert into #temp values('0037', '4.08163265306122');
		insert into #temp values('0047', '4.06976744186046');
		insert into #temp values('0051', '15.5038759689923');
		insert into #temp values('0107', '2.5');
		insert into #temp values('0282', '8.57142857142857');
		insert into #temp values('0332', '25.8620689655172');
		insert into #temp values('0382', '7.60233918128655');
		insert into #temp values('0392', '40.8450704225352');
		insert into #temp values('0674', '32.5301204819277');
		insert into #temp values('0734', '14.0939597315436');
		insert into #temp values('0825', '22.0973782771536');
		insert into #temp values('0985', '31.4285714285714');
		insert into #temp values('1236', '16.6666666666667');
		select
			BBB.Latitude, BBB.Longitude, BBB.BBBID,
			round((#temp.nps + 20) * 100, 0) /*+ round(((#temp.nps + 20) * (#temp.nps + 20)) / 2000, 0)*/
		from BBB
		inner join #temp on #temp.bbbid = BBB.BBBID
		where
			BBB.BBBBranchID = '0' and BBB.IsActive = '1' and
			BBB.Latitude is not null and BBB.Longitude is not null and
			BBB.BBBID != '8888' and
			#temp.nps is not null
		group by BBB.BBBID, BBB.Latitude, BBB.Longitude, #temp.nps;
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