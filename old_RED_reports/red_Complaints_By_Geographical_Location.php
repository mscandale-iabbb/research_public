<?php

/*
 * 03/13/15 MJS - Added 150 close code
 * 06/03/15 MJS - Modified for BBB Mexico City
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iBBBID = Numeric2($_POST['iBBBID']);
$iBusinessID = Numeric2($_POST['iBusinessID']);

if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray() );
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;", '', '', 'required' );
$input_form->AddNote("<span class=red>* required</span> ");
$input_form->AddNote('(also called BID)');
if ($iBBBID > '') {
	$query = "SELECT distinct b.BusinessName FROM Business b WITH (NOLOCK) WHERE
		b.BBBID = '" . $iBBBID . "' and b.BusinessID = '" . $iBusinessID . "'";
	$rsraw = $conn->execute("$query");
	$rs = $rsraw->GetArray();
	foreach ($rs as $k => $fields) {
		$oBusinessName = $fields[0];
	}
	echo "<br/>";
	echo $oBusinessName;
}
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	OpenGoogleAPI('map', 'Map');
	echo "	data.addColumn('number', 'Lat');
		data.addColumn('number', 'Lon');
		data.addColumn('string', 'Name');
		";
	$query = "select TOP 400 /* Google max */
			z.Longitude,
			z.Latitude
		from BusinessComplaint c WITH (NOLOCK)
		inner join tblZipCoordinates z WITH (NOLOCK) on
			(LEN(z.Zip) = 5 and z.Zip = LEFT(c.ConsumerPostalCode,5)) or
			(LEN(z.Zip) = 3 and z.Zip = LEFT(c.ConsumerPostalCode,3))
		where
			c.BBBID = '" . $iBBBID . "' and c.BusinessID = '" . $iBusinessID . "' and
			c.CloseCode IN ('110','111','112','120','121','122','150','200','300') and
			c.DateClosed >= GETDATE() - 1095 and
			z.Longitude is not null and z.Latitude is not null and
			c.BBBID != '8888'
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

	echo "<div class='main_section roundedborder'>";
	if ($iBBBID == '8888') {
		echo "<div class='inner_section'><p>No geographic data for Mexico</p></div>";
	}
	else if (count($rs) == 0) {
		echo "<div class='inner_section'><p>No complaint records found for that business</p></div>";
	}
	else {
		echo "<div id=google_div class='inner_section' style='height: 600px;'></div>";
	}
	echo "</div>";

	echo "<br/>";
}


$page->write_pagebottom();

?>
