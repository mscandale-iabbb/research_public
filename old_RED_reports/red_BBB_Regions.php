<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 08/19/15 MJS - use latest Census data (not necessarily current year) for establishments
 * 08/25/15 MJS - fixed to pull revenue data properly
 * 08/25/15 MJS - changed PersonsInArea to HouseholdsInArea
 * 02/19/16 MJS - changed sort order of a query
 * 04/20/16 MJS - changed sort order and column order of meetings
 * 04/20/16 MJS - locked vendors out
 * 08/25/16 MJS - aligned column headers
 * 09/27/16 MJS - added hyperlink to detail page
 * 01/09/17 MJS - changed calls to define links and tabs
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);
$page->CheckBBBOnly($BBBID);

$input_form = new input_form($conn);
$input_form->Close();

$showreport = 1;
if ($showreport) {
	$query = "SELECT
			BBB.Region,
			COUNT(*) as bbbs,
			sum(f2.TotalRevenue) as revenue,
			SUM(f.EstabsInArea) as businesses,
			SUM(YearlyStats.CountOfABsYearEnd) as abs,
			SUM(f.HouseholdsInArea) as households,
			SUM(YearlyStats.CountOfInquiries) as inquiries,
			SUM(YearlyStats.CountOfComplaints) as complaints
		from BBB WITH (NOLOCK)
		inner join BBBFinancials f WITH (NOLOCK) ON
			f.BBBID = BBB.BBBID and f.BBBBranchID = 0 and
			(select count(*) from BBBFinancials f3 WITH (NOLOCK) WHERE
				f3.BBBID = f.BBBID and f3.BBBBranchID = f.BBBBranchID and
				f3.[Year] > f.[Year]) = 0
		inner join BBBFinancials f2 WITH (NOLOCK) ON
			f2.BBBID = BBB.BBBID and f2.BBBBranchID = 0 and
			f2.[Year] = YEAR(GETDATE()) - 2
		left outer join YearlyStats WITH (NOLOCK) on YearlyStats.BBBID =
			BBB.BBBID and YearlyStats.[Year] = YEAR(GETDATE()) - 1
		where BBB.BBBBranchID = '0' and IsActive = 1
		group by BBB.Region";
	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Region', '', '', 'left'),
				array('# BBBs', '', '', 'right'),
				array('Revenue', '', '', 'right'),
				array('Firms in Area', '', '', 'right'),
				array('ABs', '', '', 'right'),
				array('Households in Area', '', '', 'right'),
				array('Inquiries', '', '', 'right'),
				array('Complaints', '', '', 'right')
			)
		);
		foreach ($rs as $k => $fields) {
			$link = "<a target=_new href=red_BBB_Region_Details.php?iRegion={$fields[0]}>{$fields[0]}</a>";
			$report->WriteReportRow(
				array (
					$link,
					$fields[1],
					intval(round($fields[2],0)),
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7]
				)
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				array_sum( get_array_column($rs, 1) ),
				intval(array_sum( get_array_column($rs, 2) )),
				array_sum( get_array_column($rs, 3) ),
				array_sum( get_array_column($rs, 4) ),
				array_sum( get_array_column($rs, 5) ),
				array_sum( get_array_column($rs, 6) ),
				array_sum( get_array_column($rs, 7) )
			)
		);
		$report->Close("nocount");
	}
}

OpenGoogleAPI('geochart', 'GeoChart');
echo "
	data.addColumn('number', 'Latitude');
	data.addColumn('number', 'Longitude');
	data.addColumn('string', 'Region');
	data.addColumn({type:'string', role:'tooltip'});
	";
$query = "SELECT
		s.Latitude,
		s.Longitude,
		/*
		color = CASE s.Region
			WHEN 'Western' THEN '1'
			WHEN 'Southeast' THEN '2'
			WHEN 'Southwest' THEN '3'
			WHEN 'Northeast' THEN '4'
			WHEN 'Midwest' THEN '5'
			ELSE '6'
		END,
		*/
		r.RegionAbbreviation,
		s.StateNameProper + ' - ' + s.Region
	FROM tblStates s WITH (NOLOCK)
	INNER JOIN tblRegions r WITH (NOLOCK) on r.RegionCode = s.Region
	WHERE s.Latitude is not null and s.Longitude is not null
	ORDER BY s.Region
	";
$rsraw = $conn->execute("$query");
$rs = $rsraw->GetArray();
foreach ($rs as $k => $fields) {
	$oLatitude = $fields[0];
	$oLongitude = $fields[1];
	$oRegion = $fields[2];
	$oTooltip = $fields[3];

	echo "data.addRow([
		{$oLatitude},
		{$oLongitude},
		'{$oRegion}',
		'{$oTooltip}'
		]); \n";
}
$query2 = "SELECT
		BBB.Latitude,
		BBB.Longitude,
		'×',
		'BBB ' + BBB.NickNameCity + ' - ' + BBB.Region
	FROM BBB WITH (NOLOCK)
	INNER JOIN tblRegions r WITH (NOLOCK) on r.RegionCode = BBB.Region
	WHERE BBB.Latitude is not null and BBB.Longitude is not null and
		BBB.IsActive = 1 and BBB.BBBBranchID >= 0 and BBB.Region > ''
	ORDER BY BBB.Region
	";
$rsraw2 = $conn->execute($query2);
$rs2 = $rsraw2->GetArray();
foreach ($rs2 as $k => $fields) {
	$oLatitude = $fields[0];
	$oLongitude = $fields[1];
	$oRegion = $fields[2];
	$oTooltip = $fields[3];

	echo "data.addRow([
		{$oLatitude},
		{$oLongitude},
		'{$oRegion}',
		'{$oTooltip}'
		]); \n";
}
if (count($rs) > 0 || count($rs2) > 0) {
	echo "output.draw(data, {
		showTip: true,
		region: 'US',
		displayMode: 'text',
		resolution: 'provinces',
		tooltip: {textStyle: {color: 'black', fontSize: 10, fontName: 'Verdana'}}
		/*
		legend: {textStyle: {color: 'black', fontSize: 10, fontName: 'Verdana'}}
		colorAxis: {values: [1, 2, 3, 4, 5, 6]},
		colorAxis: {colors: ['red', 'yellow', 'orange', 'green', 'blue', 'black']}
		*/
		}); \n";
}
CloseGoogleAPI();

echo "<div id=google_div class='inner_section'></div>";

echo "</div>";


if ($showreport) {
	$query = "SELECT
				tblRegionMeeting.RegionCode,
				DateMeetingBegin,
				DateMeetingEnd,
				MeetingLocation,
				MeetingCoordinator,
				MeetingAgendaContact
			FROM tblRegionMeeting WITH (NOLOCK)
			ORDER BY /*tblRegionMeeting.RegionCode,*/ DateMeetingBegin DESC";
	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Begin', '', '', 'left'),
				array('End', '', '', 'left'),
				array('Region', '', '', 'left'),
				array('Meeting Location', '', '', 'left'),
				array('Coordinator', '', '', 'left'),
				array('Agenda Contact', '', '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					FormatDate($fields[1]),
					FormatDate($fields[2]),
					$fields[0],
					AddApost($fields[3]),
					AddApost($fields[4]),
					$fields[5],
				)
			);
		}
		$report->Close("nocount");
	}
	echo "</div>";
}

if ($showreport) {
	$query = "SELECT
				tblRegionOfficer.RegionCode,
				FirstName, LastName, Title,
				RegionTitle, BBBPerson.PersonID, BBBPerson.PhoneID,
				BBBPerson.Email,
				'BBB ' + BBB.NickNameCity + ', ' + BBB.State,
				BBBPerson.BBBID, BBBPerson.BBBBranchID,
				DateTermBegin, DateTermEnd
		FROM BBBPerson WITH (NOLOCK)
		INNER JOIN tblRegionOfficer ON tblRegionOfficer.BBBID = BBBPerson.BBBID AND
			tblRegionOfficer.BBBBranchID = BBBPerson.BBBBranchID AND tblRegionOfficer.PersonID = BBBPerson.PersonID
		INNER JOIN BBB WITH (NOLOCK) ON BBB.BBBID = BBBPerson.BBBID AND BBB.BBBBranchID = '0'
		ORDER BY tblRegionOfficer.RegionCode, RegionTitle, LastName";
	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		$report->WriteHeaderRow(
			array (
				array('Region', '', '', 'left'),
				array('Position', '', '', 'left'),
				array('Person', '', '', 'left'),
				array('BBB', '', '', 'left'),
				array('Contact', '', '', 'left'),
				array('Term From', '', '', 'left'),
				array('Term To', '', '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					$fields[4],
					$fields[1] . ' ' . $fields[2],
					$fields[8],
					$fields[7],
					FormatDate($fields[11]),
					FormatDate($fields[12]),
				)
			);
		}
		$report->Close("nocount");
	}
	echo "</div>";
}


SlideAll();

$page->write_pagebottom();

?>