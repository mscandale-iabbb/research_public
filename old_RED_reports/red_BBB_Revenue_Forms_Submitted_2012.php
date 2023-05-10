<?php

/*
 * 08/04/15 MJS - new file made from copy of other version of report
 * 08/24/15 MJS - suppressed CBBB record
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

//$iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') - 2) ) );
$iYear = ValidYear( Numeric2( GetInput('iYear', '2012') ) );
$iSortBy = $_POST['iSortBy'];
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'type=number min=2012 max=2012');
$input_form->AddNote('This report only covers 2012 data.');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'BBB revenue' => 'f.BBBRevenue'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

function ViewButton($filename, $fileBBBID) {
	include 'config_global.php';
	if (! $filename) return;
	$b = "<a href='" .  $UPLOADS_EXTERNAL_PATH .  $fileBBBID . "/revenueform/" .
		NoPound(basename($filename)) . "' target=viewwin " .
		"style='color:#FFFFFF;' class=submit_button_small>View</a>";
	return $b;
}

if ($_POST) {
	$query = "SELECT
			f.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			BBBRevenue, BuildingIncome, RentalIncome, InvestmentIncome, RestrictedGrantIncome, 
			InKindContributions, SystemwideIncome, ReimbursementIncome, AdjustedRevenue, FiscalYearEnded, 
			CertifiedBy, CertifiedOn, BuildingIncomeDoc, RentalIncomeDoc, InvestmentIncomeDoc, 
			RestrictedGrantIncomeDoc, InKindContributionsDoc, SystemwideIncomeDoc,
			f.LastUpdatedBy, f.LastUpdatedOn
		FROM BBBRevenueForm2012 f WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) ON BBB.BBBID = f.BBBID and BBB.BBBBranchID = 0
		WHERE
			f.[Year] = '" . $iYear . "' and
			BBB.BBBID != '2000'
		";
	if ($iSortBy > '') {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute("$query");
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city']),
				array('BBB Rev', $SortFields['BBB revenue']),
				array('Building Inc', $SortFields['Building income']),
				array('Doc', ''),
				array('Rental Inc', $SortFields['Rental income']),
				array('Doc', ''),
				array('Inv Inc', $SortFields['Investment income']),
				array('Doc', ''),
				array('Rest Grant Inc', $SortFields['Restricted grant income']),
				array('Doc', ''),
				array('In-Kind Cont', $SortFields['In-kind contributions']),
				array('Doc', ''),
				array('Syswide Inc', $SortFields['Systemwide income']),
				array('Doc', ''),
				array('Reimburse Inc', $SortFields['Reimbursement income']),
				array('Adjusted Rev', $SortFields['Adjusted revenue']),
				array('Fiscal End', $SortFields['Fiscal end']),
				/*
				array('Cert By', $SortFields['Certified by']),
				array('Cert On', $SortFields['Certified on']),
				*/
				array('Excluded', ''),
				)
			);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[1]) . "</a>",
					intval($fields[2]),
					intval($fields[3]),
					ViewButton($fields[14], $fields[0]),
					intval($fields[4]),
					ViewButton($fields[15], $fields[0]),
					intval($fields[5]),
					ViewButton($fields[16], $fields[0]),
					intval($fields[6]),
					ViewButton($fields[17], $fields[0]),
					intval($fields[7]),
					ViewButton($fields[18], $fields[0]),
					intval($fields[8]),
					ViewButton($fields[19], $fields[0]),
					intval($fields[9]),
					intval($fields[10]),
					FormatDate($fields[11]),
					/*
					$fields[12],
					FormatDate($fields[13]),
					*/
					FormatPercentage( ($fields[2] - $fields[10]) / $fields[2] ),
				),
				''
			);
		}
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>