<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 12/15/15 MJS - ensured Scam Tracker records won't appear
 * 07/26/16 MJS - excluded blank close codes
 * 08/24/16 MJS - aligned column headers
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iSearchType = NoApost($_REQUEST['iSearchType']);
if (! $iSearchType) $iSearchType = 'name';
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iMinComplaints = Numeric2($_REQUEST['iMinComplaints']);
if (! $iMinComplaints) $iMinComplaints = 10;
$iConsumerLastName = NoApost($_REQUEST['iConsumerLastName']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Complaints DESC';
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddRadio('iSearchType', 'Review type', $iSearchType,
	array(
		'Consumer name' => 'name',
		'Consumer address' => 'address',
		'Consumer phone' => 'phone',
		'Consumer email' => 'email',
	)
);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddTextField('iMinComplaints', 'With at least', $iMinComplaints, "width:50px;", '', 'number');
$input_form->AddNote('complaints');
$input_form->AddTextField('iConsumerLastName', 'Consumer last name', $iConsumerLastName, "width:175px;");
$SortFields = array(
	'Consumer' => 'Consumer',
	'Complaints' => 'Complaints'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @min int = " . $iMinComplaints . ";
		declare @searchtype varchar(20);
		set @searchtype = '" . $iSearchType . "';

		if @searchtype = 'name' BEGIN
			SELECT TOP " . $iMaxRecs . "
				c.ConsumerLastName + ', ' +
					c.ConsumerFirstName + ' - ' +
					c.ConsumerCity + ', ' +
					c.ConsumerStateProvince + ' ' +
					LEFT(c.ConsumerPostalCode,5)
					as Consumer,
				COUNT(*) as Complaints
			FROM BusinessComplaint c WITH (NOLOCK) WHERE
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.CloseCode is not null and c.CloseCode > 0 and
				LEN(c.ConsumerStateProvince) = 2 and
				LEN(c.ConsumerLastName) > 1 and
				LEN(c.ConsumerFirstName) > 1 and
				c.ConsumerLastName LIKE '" . $iConsumerLastName . "%' and
				c.ConsumerLastName != 'review' and
				LEN(c.ConsumerCity) > 1 and
				LEN(LEFT(c.ConsumerPostalCode,5)) = 5 and
				c.ComplaintID not like 'scam%'
			group by
				c.ConsumerLastName + ', ' +
				c.ConsumerFirstName + ' - ' +
				c.ConsumerCity + ', ' +
				c.ConsumerStateProvince + ' ' +
				LEFT(c.ConsumerPostalCode,5)
			having COUNT(*) >= @min
			ORDER BY " . $iSortBy . "
		END
		if @searchtype = 'email' BEGIN
			SELECT TOP " . $iMaxRecs . "
				c.ConsumerEmail as Consumer,
				COUNT(*) as Complaints
			FROM BusinessComplaint c WITH (NOLOCK) WHERE
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.ConsumerLastName LIKE '" . $iConsumerLastName . "%' and
				LEN(c.ConsumerEmail) >= 7 and
				c.ConsumerEmail like '%@%' and
				c.ConsumerEmail like '%.%' and
				NOT c.ConsumerEmail like 'noreply@%' and
				NOT c.ConsumerEmail like '%bbb.org' and
				NOT c.ConsumerEmail like 'noemail@%' and
				c.ComplaintID not like 'scam%'
			group by c.ConsumerEmail
			having COUNT(*) >= @min
			ORDER BY " . $iSortBy . "
		END
		if @searchtype = 'phone' BEGIN
			SELECT TOP " . $iMaxRecs . "
				Consumer = Case
					When LEN(c.ConsumerPhone) = 7 then
						SUBSTRING(c.ConsumerPhone,1,3) + '-' +
						SUBSTRING(c.ConsumerPhone,4,4)
					When LEN(c.ConsumerPhone) = 10 then
						SUBSTRING(c.ConsumerPhone,1,3) + '-' +
						SUBSTRING(c.ConsumerPhone,4,3) + '-' +
						SUBSTRING(c.ConsumerPhone,7,4)
					else c.ConsumerPhone end,
				COUNT(*) as Complaints
			FROM BusinessComplaint c WITH (NOLOCK) WHERE
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.ConsumerLastName LIKE '" . $iConsumerLastName . "%' and
				LEN(c.ConsumerPhone) >= 10 and
				c.ConsumerPhone != '0000000000' and
				c.ConsumerPhone != '9999999999' and
				c.ConsumerPhone != '999-999-9999' and
				NOT c.ConsumerPhone LIKE 'daytime%' and
				c.ConsumerPhone != '000-000-0000' and
				c.ComplaintID not like 'scam%'
			group by c.ConsumerPhone
			having COUNT(*) >= @min
			ORDER BY " . $iSortBy . "
		END
		if @searchtype = 'address' BEGIN
			SELECT TOP " . $iMaxRecs . "
				c.ConsumerStreetAddress + ' ' +
					c.ConsumerCity + ', ' +
					c.ConsumerStateProvince + ' ' +
					LEFT(c.ConsumerPostalCode,5)
					as Consumer,
				COUNT(*) as Complaints
			FROM BusinessComplaint c WITH (NOLOCK) WHERE
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.ConsumerLastName LIKE '" . $iConsumerLastName . "%' and
				LEN(c.ConsumerStreetAddress) > 3 and
				c.ConsumerStreetAddress != 'none' and
				c.ConsumerStreetAddress != 'not provided' and
				c.ConsumerStreetAddress != 'unknown' and
				c.ConsumerStreetAddress != 'no address' and
				c.ConsumerStreetAddress != 'not supplied' and
				NOT c.ConsumerStreetAddress LIKE 'integrity place%' and
				c.ConsumerStreetAddress != '1005 la posada dr' and
				c.ConsumerStreetAddress != '2706 gannon road' and
				c.ConsumerStreetAddress != 'po box 1000' and
				LEN(c.ConsumerCity) > 2 and
				c.ConsumerCity != 'none' and
				c.ConsumerCity != 'unknown' and
				LEN(c.ConsumerStateProvince) = 2 and
				LEN(LEFT(c.ConsumerPostalCode,5)) = 5 and
				c.ComplaintID not like 'scam%'
			group by
				c.ConsumerStreetAddress + ' ' +
				c.ConsumerCity + ', ' +
				c.ConsumerStateProvince + ' ' +
				LEFT(c.ConsumerPostalCode,5)
			having COUNT(*) >= @min
			ORDER BY " . $iSortBy . "
		END
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
				array('#', '', '', 'right'),
				array('Consumer', $SortFields['Consumer'], '', 'left'),
				array('Complaints', $SortFields['Complaints'], '', 'right'),
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

			$report->WriteReportRow(
				array (
					$xcount,
					AddApost($fields[0]),
					$fields[1]
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>