<?php

/*
 * 12/11/15 MJS - new file
 * 12/15/15 MJS - fixed bug with iMaxRecs not working
 * 12/15/15 MJS - restricted access to CBBB only
 * 08/25/16 MJS - align column headers
 * 05/07/19 MJS - added iabbb
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckCouncilOnly($BBBID);


// input

$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iCEOs = NoApost($_REQUEST['iCEOs']);
if (! $iCEOs) $iCEOs = 'both';
$iCouncil = NoApost($_REQUEST['iCouncil']);
if (! $iCouncil) $iCouncil = 'both';
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Logins DESC';
$iShowSource = $_POST['iShowSource'];

$SortFields = array(
	'Number of logins' => 'Logins',
	'Last login' => 'LastLogin',
	'BBB city' => 'BBBCity',
	'Full name' => 'FullName',
	'Email' => 'Email',
);

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Activity from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddRadio('iCEOs', 'CEOs', $iCEOs, array(
	'CEOs' => 'yes',
	'Non-CEOs' => 'no',
	'Both' => 'both',
	)
);
$input_form->AddRadio('iCouncil', 'IABBB', $iCouncil, array(
	'IABBB' => 'yes',
	'Non-IABBB' => 'no',
	'Both' => 'both',
	)
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT TOP {$iMaxRecs}
			ls.UserName as Email,
			p.FirstName + ' ' + p.LastName as FullName,
			BBB.NicknameCity + ', ' + BBB.State as BBBCity,
			COUNT(*) as Logins,
			(select top 1 DateAccessed from LoginStat ls2 WITH (NOLOCK) where
				ls2.UserName = ls.UserName order by DateAccessed DESC) as LastLogin
		FROM LoginStat ls WITH (NOLOCK)
		LEFT OUTER JOIN BBBPerson p WITH (NOLOCK) ON p.Email = ls.UserName
		LEFT OUTER JOIN BBB WITH (NOLOCK) ON BBB.BBBID = p.BBBID and BBB.BBBBranchID = p.BBBBranchID
		WHERE
			ls.DateAccessed >= '{$iDateFrom}' and ls.DateAccessed <= '{$iDateTo}' and
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			ApplicationName not in ('HR System','Contract Management System','Contracts','Timesheet') and
			(
				'{$iCEOs}' = 'both' or
				('{$iCEOs}' = 'yes' and p.CEO = 1) or
				('{$iCEOs}' = 'no' and (p.CEO = 0 or p.CEO is null))
			) and
			(
				'{$iCouncil}' = 'both' or
				('{$iCouncil}' = 'yes' and p.BBBID = '2000') or
				('{$iCouncil}' = 'no' and p.BBBID != '2000')
			)
		GROUP BY UserName, p.LastName, p.FirstName, p.CEO, BBB.NicknameCity, BBB.State
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
				array('Email', $SortFields['Email'], '', 'left'),
				array('Full Name', $SortFields['Full name'], '', 'left'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Logins', $SortFields['Number of logins'], '', 'right'),
				array('Last Login', $SortFields['Last login'], '', 'left'),
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
					$fields[0],
					$fields[1],
					$fields[2],
					$fields[3],
					FormatDate($fields[4]),
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource) $report->WriteSource($query);
}

$page->write_pagebottom();

?>