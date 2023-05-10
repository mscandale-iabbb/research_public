<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 08/04/15 MJS - limited to 2013 and later - because we're splitting from older format (which is now in a separate report)
 * 08/05/15 MJS - rewrote for new fields
 * 08/10/15 MJS - updated for more changes to fields
 * 08/24/15 MJS - suppressed CBBB record
 * 08/25/15 MJS - added several new fields
 * 12/15/15 MJS - restricted access to CBBB only
 * 08/25/16 MJS - align column headers
 * 01/09/17 MJS - changed calls to define links and tabs
 * 07/14/17 MJS - added 12 new columns, converted to decimal
 * 07/21/17 MJS - modified to show all BBBs
 * 11/16/17 MJS - added 3 new columns
 * 11/20/17 MJS - refactored for CBBBDuesRate table
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);
$page->CheckCouncilOnly($BBBID);

$iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') - 2) ) );
$iSortBy = $_POST['iSortBy'];
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'number min=2013');
$input_form->AddNote('This report only covers 2013 and later data.');
$SortFields = array(
	'BBB city' => 'NicknameCity',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			BBBTotalRevenue, BBBAdjustedRevenue, BBBTypeOfTaxForm,
			FoundationTotalRevenue, FoundationAdjustedRevenue, FoundationTypeOfTaxForm,
			BBBFiscalYearEnded, FoundationFiscalYearEnded,
			BBBAccountingType, FoundationAccountingType,
			BBBFiscalYearBegan, FoundationFiscalYearBegan,
			Comments,
			/* CertifiedBy, CertifiedOn, f.LastUpdatedBy, f.LastUpdatedOn */
			f.BBBExclusionAutoline, f.BBBExclusionDuesOtherBBBs, f.BBBExclusionRentalIncomeAffiliate,
			f.BBBExclusionInvestmentIncome, f.BBBExclusionRentalIncome, f.BBBExclusionSaleAssets,
			f.FoundationExclusionAutoline, f.FoundationExclusionDuesOtherBBBs, f.FoundationExclusionRentalIncomeAffiliate, 
			f.FoundationExclusionInvestmentIncome, f.FoundationExclusionRentalIncome, f.FoundationExclusionSaleAssets,
			(BBBAdjustedRevenue + FoundationAdjustedRevenue),
			cast(BBBAdjustedRevenue + FoundationAdjustedRevenue as decimal(14,2)) * DuesRate,
			(cast(BBBAdjustedRevenue + FoundationAdjustedRevenue as decimal(14,2)) * DuesRate) / 12,
			DuesRate
		FROM BBB WITH (NOLOCK)
		INNER JOIN BBBFinancials bf WITH (NOLOCK) ON bf.BBBID = BBB.BBBID and bf.BBBBranchID = BBB.BBBBranchID and
			bf.[Year] = '{$iYear}'
		LEFT OUTER JOIN BBBRevenueForm f WITH (NOLOCK) ON f.BBBID = BBB.BBBID AND f.[Year] = '{$iYear}'
		INNER JOIN CBBBDuesRate WITH (NOLOCK) ON DuesYear = '{$iYear}'
		WHERE
			BBB.BBBBranchID = '0' and BBB.BBBID != '2000' and
			(BBB.IsActive = '1' or f.BBBID is not null)
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('BBB Tot Rev', '', '', 'right'),
				array('BBB Adj Rev', '', '', 'right'),
				array('BBB Tax Form', '', '', 'left'),
				array('BBB Excluded', '', '', 'right'),
				array('BBB Fiscal Began', '', '', 'left'),
				array('BBB Fiscal End', '', '', 'left'),
				array('BBB Accounting', '', '', 'left'),
				array('Found Tot Rev', '', '', 'right'),
				array('Found Adj Rev', '', '', 'right'),
				array('Found Tax Form', '', '', 'left'),
				array('Found Excluded', '', '', 'right'),
				array('Found Fiscal Began', '', '', 'left'),
				array('Found Fiscal End', '', '', 'left'),
				array('Found Accounting', '', '', 'left'),
				array('Comments', '', '', 'left'),
				array('BBB AutoLn', '', '', 'right'),
				array('BBB Dues Other BBBs', '', '', 'right'),
				array('BBB Rental Income Affiliate', '', '', 'right'),
				array('BBB Invest Income', '', '', 'right'),
				array('BBB Rental Income', '', '', 'right'),
				array('BBB Sale Assets', '', '', 'right'),
				array('Found AutoLn', '', '', 'right'),
				array('Found Dues Other BBBs', '', '', 'right'),
				array('Found Rental Income Affiliate', '', '', 'right'),
				array('Found Invest Income', '', '', 'right'),
				array('Found Rental Income', '', '', 'right'),
				array('Found Sale Assets', '', '', 'right'),
				array('Dues Rate', '', '', 'right'),
				array('Tot Adj Rev', '', '', 'right'),
				array("Tot Dues {$showrate}", '', '', 'right'),
				array('Monthly Dues', '', '', 'right'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[1]) . "</a>",
					$fields[2],
					$fields[3],
					$fields[4],
					FormatPercentage( ($fields[2] - $fields[3]) / $fields[2] ),
					FormatDate($fields[12]),
					FormatDate($fields[8]),
					$fields[10],
					$fields[5],
					$fields[6],
					$fields[7],
					FormatPercentage( ($fields[5] - $fields[6]) / $fields[5] ),
					FormatDate($fields[13]),
					FormatDate($fields[9]),
					$fields[11],
					AddApost($fields[14]),
					$fields[15],
					$fields[16],
					$fields[17],
					$fields[18],
					$fields[19],
					$fields[20],
					$fields[21],
					$fields[22],
					$fields[23],
					$fields[24],
					$fields[25],
					$fields[26],
					($fields[30] * 100) . "%",
					intval($fields[27]),
					intval($fields[28]),
					intval($fields[29]),
				),
				''
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