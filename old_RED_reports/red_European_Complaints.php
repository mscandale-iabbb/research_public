<?php

/*
 * 04/06/16 MJS - new file
 * 08/25/16 MJS - align column headers
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddExportOptions('excel');
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			c.ConsumerCountry, ConsumerCity, ConsumerStateProvince,
			ConsumerStreetAddress, ConsumerStreetAddress2,
			BusinessName,
			BusinessStreetAddress, BusinessStreetAddress2, BusinessCity,
			BusinessStateProvince, BusinessPostalCode,
			DateComplaintFiledWithBBB, DateClosed, CloseCode,
			t.ConsumerComplaint, t.DesiredOutcome, ProductOrService
		FROM BusinessComplaint c WITH (NOLOCK)
		/*inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID*/
		left outer join BusinessComplaintText t WITH (NOLOCK) ON
			t.BBBID = c.BBBID AND t.ComplaintID = c.ComplaintID
		left outer JOIN ForeignCountries WITH (NOLOCK) on
			c.ConsumerCountry = ForeignCountries.Country
		WHERE
			c.ConsumerCountry in (SELECT Country from ForeignCountries) and
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			CloseCode <= '300' and
			NOT ConsumerCountry in
			(
				'China','Japan','Mexico','Australia','New Zealand','Egypt','Brazil','india','indonesia','nigeria','Philippines',
				'UAE','uganda','russia','israel','south africa','colombia','pakistan','korea','KOREA, REPUBLIC OF','chile',
				'thailand','singapore','jamaica','DOMINICAN REPUBLIC','uruguay','puerto rico','Nicaragua','bahamas',
				'saudi arabia','Trinidad and Tobago','costa rica','venezuela','hong kong','iraq','UNITED ARAB EMIRATES','columbia',
				'malaysia','argentina','bangladesh','kuwait','taiwan','ghana','vietnam','guam','American Samoa','peru','bolivia',
				'botswana','qatar','panama','cameroon','ZAMBIA','Zimbabwe','viet nam','TAIWAN, PROVINCE OF CHINA','oman','lebanon',
				'guatemala','grenada','ecuador','algeria','AFGHANISTAN','aruba','barbados','belize','bermuda','el salvador',
				'guyana','haiti','HONDURAS','NAMIBIA','paraguay','SAINT KITTS AND NEVIS','SAINT LUCIA','TURKS AND CAICOS ISLANDS',
				'TURKS AND CAICOS ISL'
			) AND
			c.ComplaintID NOT like 'scam%'
		ORDER BY c.ConsumerCountry
		";

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) AbortREDReport($query);
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('Consumer Country', '', '', 'left'),
				array('Consumer City', '', '', 'left'),
				array('Consumer State', '', '', 'left'),
				array('Consumer Street', '', '', 'left'),
				array('Consumer Street 2', '', '', 'left'),
				array('Business Name', '', '', 'left'),
				array('Business Street', '', '', 'left'),
				array('Business Street 2', '', '', 'left'),
				array('Business City', '', '', 'left'),
				array('Business State', '', '', 'left'),
				array('Business Postal Code', '', '', 'left'),
				array('Filed', '', '', 'left'),
				array('Closed', '', '', 'left'),
				array('Close Code', '', '', 'left'),
				array('Complaint Narrative', '', '', 'left'),
				array('Desired Outcome', '', '', 'left'),
				array('Product or Service', '', '', 'left')
			)
		);

		//$iPageSize = count($rs);
		//$TotalPages = round(count($rs) / $iPageSize, 0);

		foreach ($rs as $k => $fields) {
			$narrative = strip_tags($fields[14]);
			$outcome = strip_tags($fields[15]);
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[1],
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
					$fields[8],
					$fields[9],
					$fields[10],
					FormatDate($fields[11]),
					FormatDate($fields[12]),
					$fields[13],
					$narrative,
					$outcome,
					$fields[16],
				),
				''
			);
		}
	}
	$report->Close();
}
	
$page->write_pagebottom();

?>