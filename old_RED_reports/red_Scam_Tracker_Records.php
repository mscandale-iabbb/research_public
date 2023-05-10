<?php

/*
 * 12/03/15 MJS - new file
 * 12/15/15 MJS - changed default sort order
 * 12/15/15 MJS - fixed bug with iMaxRecs not working
 * 12/15/15 MJS - added "none" when consumer name blank so link works
 * 12/15/15 MJS - restricted access to CBBB only
 * 12/15/15 MJS - added business phone
 * 08/26/16 MJS - align column headers
 * 09/24/19 MJS - opened permissions to all
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
/* $page->CheckCouncilOnly($BBBID); */


// input

$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'DateClosed DESC';
$iShowSource = $_POST['iShowSource'];

$SortFields = array(
	'Consumer last name' => 'ConsumerLastName',
	'Consumer first name' => 'ConsumerFirstName',
	'ID' => 'c.ComplaintID',
	'Consumer state' => 'ConsumerStateProvince',
	'Consumer postal code' => 'ConsumerPostalCode',
	'Consumer email' => 'ConsumerEmail',
	'Consumer phone' => 'ConsumerPhone',
	'Business name' => 'c.BusinessName',
	'Business street' => 'c.BusinessStreetAddress',
	'Business city' => 'c.BusinessCity',
	'Business state' => 'c.BusinessStateProvince',
	'Business postal code' => 'c.BusinessPostalCode',
	'Business phone' => 'c.BusinessPhone',
	'Date closed' => 'DateClosed',
);

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Searches from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT TOP {$iMaxRecs}
			c.BBBID,
			/*BBB.NickNameCity + ', ' + BBB.State,*/
			c.ComplaintID,
			c.ConsumerFirstName,
			c.ConsumerLastName,
			c.ConsumerStateProvince,
			c.ConsumerPostalCode,
			c.ConsumerEmail,
			c.ConsumerPhone,
			c.BusinessName,
			c.BusinessStreetAddress,
			c.BusinessCity,
			c.BusinessStateProvince,
			c.BusinessPostalCode,
			c.BusinessPhone,
			c.DateClosed,
			t.ConsumerComplaint,
			t.DesiredOutcome
		FROM BusinessComplaint c WITH (NOLOCK)
		/*LEFT OUTER join BBB WITH (NOLOCK) on c.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'*/
		left outer join BusinessComplaintText t WITH (NOLOCK) ON
			t.BBBID = c.BBBID AND t.ComplaintID = c.ComplaintID
		WHERE
			c.ComplaintID like 'scam%' and
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}'
		";
	if ($iSortBy) $query .= " ORDER BY " . $iSortBy;

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
				array('Consumer Name', $SortFields['Consumer last name'], '', 'left'),
				array('State', $SortFields['Consumer state'], '', 'left'),
				array('Zip', $SortFields['Consumer postal code'], '', 'left'),
				array('Consumer Email', $SortFields['Consumer email'], '', 'left'),
				array('Phone', $SortFields['Consumer phone'], '', 'left'),
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('Business Street', $SortFields['Business street'], '', 'left'),
				array('Business City', $SortFields['Business city'], '', 'left'),
				array('State', $SortFields['Business state'], '', 'left'),
				array('Zip', $SortFields['Business postal code'], '', 'left'),
				array('Phone', $SortFields['Business phone'], '', 'left'),
				array('Closed', $SortFields['Date closed'], '', 'left'),
			)
		);

		$xcount = 0;
		
		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_REQUEST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;

		foreach ($rs as $k => $fields) {
			$xcount++;
			
			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;

			if (! $fields[2] && ! $ $fields[3]) $fields[2] = "(None)";
			$consumer_name = "<a target=detail href=red_Consumer_Details.php?iBBBID=" . $fields[0] .
					"&iComplaintID=" . $fields[1] . ">" . $fields[2] . " " . $fields[3] . "</a>";

			$report->WriteReportRow(
				array (
					$consumer_name,
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
					$fields[8],
					$fields[9],
					$fields[10],
					$fields[11],
					$fields[12],
					$fields[13],
					FormatDate($fields[14]),
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>