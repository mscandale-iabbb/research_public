<?php

/*
 * 08/28/17 MJS - new file
 * 09/26/17 MJS - added column for most common words
 * 09/27/17 MJS - added "sign" for Contract Issues
 * 03/22/18 MJS - excluded out of business
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$userBBBID = $BBBID;

$iBBBID = Numeric2($_POST['iBBBID']);
if (! $_POST && $userBBBID != '2000') $iBBBID = $userBBBID;
else if (! $_POST && $userBBBID == '2000') $iBBBID = '1066';
$iClassificationID1 = Numeric2($_POST['iClassificationID1']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddMultipleSelectField('iClassificationID1', 'Classification', $iClassificationID1,
	$input_form->BuildComplaintTypesArray(), '', '', '', 'width:300px');
$SortFields = array(
	'ID' => 'c.ComplaintID',
	'BBB city' => 'BBB.NicknameCity,n.BusinessName',
	'Classification' => 'c.ClassificationID1',
	'Consumer last name' => 'c.ConsumerLastName',
	'Business name' => 'c.BusinessName'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$column_OOB = "OutOfBusinessTypeId";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$column_OOB = "OutOfBusinessStatusTypeId";
	}

	$query = "
		select top {$iMaxRecs}
			c.BBBID,
			BBB.NicknameCity,
			c.ComplaintID,
			cast(c.ClassificationID1 as varchar(2)) + ' ' + cl.ClassificationDescription,
			c.ConsumerLastName,
			c.BusinessName,
			t.ConsumerComplaint,
			t.DesiredOutcome
		from BusinessComplaint c WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		left outer join BusinessComplaintText t WITH (NOLOCK) on c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
		left outer join BBB WITH (NOLOCK) ON BBB.BBBID = c.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblYPPA WITH (NOLOCK) ON BusinessTOBID = tblYPPA.yppa_code
		left outer join tblClassification cl WITH (NOLOCK) ON c.ClassificationID1 = cl.ClassificationCode
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		where
			('{$iBBBID}' = '' or c.BBBID = '{$iBBBID}') and
			('{$iClassificationID1}' = '' or c.ClassificationID1 = '{$iClassificationID1}') and 
			c.DateClosed >= GETDATE() - 1095 and c.DateClosed <= GETDATE() and
			c.ComplaintID not like 'scam%' and
			c.CloseCode != '400' and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			b.IsReportable = '1' and
			len(t.ConsumerComplaint) > 75 and
			c.ClassificationID1 > '' and
			(
				(
					c.ClassificationID1 = '6' and
					t.ConsumerComplaint not like '%guarantee%' and
					t.ConsumerComplaint not like '%warranty%' and
					t.ConsumerComplaint not like '%promise%' and
					t.ConsumerComplaint not like '%honor%' and
					t.DesiredOutcome not like '%guarantee%' and
					t.DesiredOutcome not like '%warrant%' and
					t.DesiredOutcome not like '%promise%' and
					t.DesiredOutcome not like '%honor%'
				) or
				(
					c.ClassificationID1 = '9' and
					t.ConsumerComplaint not like '%broke%' and
					t.ConsumerComplaint not like '%damage%' and
					t.ConsumerComplaint not like '%fix%' and
					t.ConsumerComplaint not like '%repair%' and
					t.ConsumerComplaint not like '%patch%' and
					t.ConsumerComplaint not like '%redo%' and
					t.ConsumerComplaint not like '%not work%' and
					t.ConsumerComplaint not like '%nt work%' and
					t.DesiredOutcome not like '%broke%' and
					t.DesiredOutcome not like '%damage%' and
					t.DesiredOutcome not like '%fix%' and
					t.DesiredOutcome not like '%repair%' and
					t.DesiredOutcome not like '%patch%' and
					t.DesiredOutcome not like '%redo%' and
					t.DesiredOutcome not like '%not work%' and
					t.DesiredOutcome not like '%nt work%'
				) or
				(
					c.ClassificationID1 = '5' and
					t.ConsumerComplaint not like '%ship%' and
					t.ConsumerComplaint not like '%arrive%' and
					t.ConsumerComplaint not like '%deliver%' and
					t.ConsumerComplaint not like '%receive%' and
					t.ConsumerComplaint not like '%transport%' and
					t.ConsumerComplaint not like '%showed up%' and
					t.ConsumerComplaint not like '%t get%' and
					t.DesiredOutcome not like '%ship%' and
					t.DesiredOutcome not like '%arrive%' and
					t.DesiredOutcome not like '%deliver%' and
					t.DesiredOutcome not like '%receive%' and
					t.DesiredOutcome not like '%transport%' and
					t.DesiredOutcome not like '%showed up%' and
					t.DesiredOutcome not like '%t get%'
				) or
				(
					c.ClassificationID1 = '3' and
					t.ConsumerComplaint not like '%contract%' and
					t.ConsumerComplaint not like '%agreement%' and
					t.ConsumerComplaint not like '% sign%' and
					t.ConsumerComplaint not like '% lease%' and
					t.ConsumerComplaint not like '% leasing%' and
					t.DesiredOutcome not like '%contract%' and
					t.DesiredOutcome not like '%agreement%' and
					t.DesiredOutcome not like '% sign %' and
					t.DesiredOutcome not like '%lease%' and
					t.DesiredOutcome not like '%leasing%'
				) or
				(
					c.ClassificationID1 = '10' and
					t.ConsumerComplaint not like '%mislead%' and
					t.ConsumerComplaint not like '%misrepresent%' and
					t.ConsumerComplaint not like '%dishonest%' and
					t.ConsumerComplaint not like '% lied%' and
					t.ConsumerComplaint not like '% lying%' and
					t.ConsumerComplaint not like '%promise%' and
					t.ConsumerComplaint not like '%pressure%' and
					t.ConsumerComplaint not like '%sales rep%' and
					t.ConsumerComplaint not like '%salesman%' and
					t.ConsumerComplaint not like '%salesmen%' and
					t.ConsumerComplaint not like '%sales man%' and
					t.ConsumerComplaint not like '%sales men%' and
					t.ConsumerComplaint not like '%sales person%' and
					t.ConsumerComplaint not like '%sales people%' and
					t.ConsumerComplaint not like '%sales staff%' and
					t.ConsumerComplaint not like '%sales team%' and
					t.DesiredOutcome not like '%mislead%' and
					t.DesiredOutcome not like '%misrepresent%' and
					t.DesiredOutcome not like '%dishonest%' and
					t.DesiredOutcome not like '% lied%' and
					t.DesiredOutcome not like '% lying%' and
					t.DesiredOutcome not like '%promise%' and
					t.DesiredOutcome not like '%pressure%' and
					t.DesiredOutcome not like '%sales rep%' and
					t.DesiredOutcome not like '%salesman%' and
					t.DesiredOutcome not like '%salesmen%' and
					t.DesiredOutcome not like '%sales man%' and
					t.DesiredOutcome not like '%sales men%' and
					t.DesiredOutcome not like '%sales person%' and
					t.DesiredOutcome not like '%sales people%' and
					t.DesiredOutcome not like '%sales staff%' and
					t.DesiredOutcome not like '%sales team%'
				)
			)
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

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
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('ID', $SortFields['ID'], '', 'left'),
				array('Classification', $SortFields['Classification'], '', 'left'),
				array('Consumer Last Name', $SortFields['Consumer last name'], '', 'left'),
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('Most Common Words', '', '', 'left'),
				array('', '', '', 'left'),
			)
		);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_POST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;

		foreach ($rs as $k => $fields) {
			$xcount++;

			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;

			$topwords = '';
			$output = shell_exec("python complaint_similarity.py 'topwords' '{$fields[6]} {$fields[7]}' ''");
			if ($output) {
				$topwords = str_replace('|', ' ', $output);
			}

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>{$fields[1]}</a>",
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$topwords,
					"<a target=detail href=red_Consumer_Details.php?iBBBID={$fields[0]}&iComplaintID={$fields[2]}>Details</a>",
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