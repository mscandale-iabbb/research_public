<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 08/19/15 MJS - use latest Census data (not necessarily current year) for establishments
 * 03/02/16 MJS - set default year to depend on current month
 * 04/19/16 MJS - locked out vendors
 * 05/27/16 MJS - added inactive BBBs
 * 06/16/16 MJS - for BBBs that haven't submitted their evaluations yet, show zeroes for values
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 08/25/16 MJS - aligned column headers
 * 11/02/16 MJS - fixed bug for BBBs without estabs
 * 11/23/16 MJS - changed years pulled for Census data, cleaned up code
 * 12/19/16 MJS - changed year pulled for Census data to match Evalations record
 * 12/19/16 MJS - added some column header labels
 * 01/04/17 MJS - fixed market share, changed to show market share for inactive bbbs
 * 01/04/17 MJS - changed calls to define links and tabs
 * 09/05/17 MJS - modified to show even if evaluation not submitted, added column for submitted
 * 02/28/18 MJS - highlighted Austin 2016
 * 04/16/18 MJS - modified to exclude ones with 0s
 * 04/16/18 MJS - modified logic of when to exclude ones with 0s based on calendar date
 * 06/24/19 MJS - modified to use billable ABs instead of ABs for avg dues
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

function GetDefaultYear() {
	$thismonth = date('n');
	if ($thismonth >= 6) $xyear = date('Y') - 1;
	else $xyear = date('Y') - 2;
	return $xyear;
}

$iYear = ValidYear( Numeric2( GetInput('iYear',GetDefaultYear()) ) );
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'year');
/*
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
*/
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'Region' => 'Region,NicknameCity',
	'Fiscal year ends' => 've.FiscalYearEnds',
	'Accounting method' => 've.AccountingMethod',
	'Total revenue' => 've.TotalIncomeYTD',
	'Dues revenue' => 'DuesRevenue',
	'Total salaries' => 'TotalSalaries',
	'Salaries to revenue' => 'SalariesToRevenue',
	'Total expenses' => 've.TotalExpenseYTD',
	'Net gain/loss' => 've.NetYTD',
	'Begin fund balance' => 'BeginFundBalance',
	'End fund balance' => 'EndFundBalance',
	'Change in balance' => 'ChangeInFundBalance',
	'ABs' => 'ABs',
	'Estabs in area' => 'EstabsInArea',
	'Market share' => 'MarketShare',
	'Average dues' => 'AverageDues',
	'Submitted' => 'e.EvaluationSubmitted'
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
			tblRegions.RegionAbbreviation,
			BBB.SalesCategory,
			ve.TotalIncomeYTD,
			ve.TotalExpenseYTD,
			ve.NetYTD,
			ve.FiscalYearEnds,
			case
					when {$iYear} < 2012 THEN f2.BeginFundBalance
					when {$iYear} = 2012 THEN f3.EndFundBalance
					else ve2.EndFundBalance
				end as BeginFundBalance,
			case
					when {$iYear} < 2012 THEN f2.EndFundBalance
					else ve.EndFundBalance
				end as EndFundBalance,
			ve.AccountingMethod,
			case
					when {$iYear} < 2012 THEN f2.DuesRevenue 
					else ve.DuesRevenue
				end as DuesRevenue,
			case
					when {$iYear} < 2012 THEN f2.TotalSalaries
					else ve.TotalSalaries
				end as TotalSalaries,
			case
					when {$iYear} < 2012 and ve.TotalIncomeYTD > 0 THEN (f2.TotalSalaries / ve.TotalIncomeYTD)
					when {$iYear} < 2012 and ve.TotalIncomeYTD = 0 THEN '-'
					when {$iYear} >= 2012 and ve.TotalIncomeYTD > 0 THEN (ve.TotalSalaries / ve.TotalIncomeYTD) 
					when {$iYear} >= 2012 and ve.TotalIncomeYTD = 0 THEN '-'
				end as SalariesToRevenue,
			case
					when {$iYear} < 2012 and f2.BeginFundBalance > 0 THEN ((f2.EndFundBalance - f2.BeginFundBalance) / f2.BeginFundBalance)
					when {$iYear} < 2012 and f2.BeginFundBalance = 0 THEN '-'
					when {$iYear} = 2012 and ve.EndFundBalance > 0 THEN ((ve.EndFundBalance - f3.EndFundBalance) / f3.EndFundBalance)
					when {$iYear} = 2012 and ve.EndFundBalance = 0 THEN '-'
					when {$iYear} >= 2012 and ve2.EndFundBalance > 0 THEN ((ve.EndFundBalance - ve2.EndFundBalance) / ve2.EndFundBalance)
					when {$iYear} >= 2012 and ve2.EndFundBalance = 0 THEN '-'
					else '-'
				end as ChangeInFundBalance,
			(
				select CountOfABs from SnapshotStats s WITH (NOLOCK) where s.BBBID = BBB.BBBID and s.MonthNumber = '1' and
					s.[Year] = '" . ($iYear + 1) . "'
			) as ABs,
			f2.EstabsInArea,
			/*
			(
				select top 1 EstabsInArea from BBBFinancials f WITH (NOLOCK) where f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and
				f.CensusYear <= '{$iYear}'
				order by f.CensusYear desc

				(select count(*) from BBBFinancials f2 WITH (NOLOCK) WHERE
					f2.BBBID = f.BBBID and f2.BBBBranchID = f.BBBBranchID and
					f2.[Year] > f.[Year]) = 0
			) as EstabsInArea,
			*/
			case when f2.EstabsInArea > 0 then (
				cast(( select CountOfABs from SnapshotStats s WITH (NOLOCK) where s.BBBID = BBB.BBBID and s.MonthNumber = '1' and
					s.[Year] = '" . ($iYear + 1) . "') as decimal(14,2))
				/
				cast(f2.EstabsInArea as decimal(14,2))
				/*
				cast(( select EstabsInArea from BBBFinancials f WITH (NOLOCK) where
					f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and
					f.EstabsInArea > 0 and
					(select count(*) from BBBFinancials f2 WITH (NOLOCK) WHERE
						f2.BBBID = f.BBBID and f2.BBBBranchID = f.BBBBranchID and
						f2.[Year] > f.[Year]) = 0
				) as decimal(14,2))
				*/
			) else 0 end as MarketShare,
			case
					when {$iYear} < 2012 THEN f2.DuesRevenue /
							( select CountOfBillableABs from SnapshotStats s WITH (NOLOCK) where s.BBBID = BBB.BBBID and s.MonthNumber = '1' and
								s.[Year] = '" . ($iYear + 1) . "')
					else ve.DuesRevenue /
							( select CountOfBillableABs from SnapshotStats s WITH (NOLOCK) where s.BBBID = BBB.BBBID and s.MonthNumber = '1' and
								s.[Year] = '" . ($iYear + 1) . "')
				end as AverageDues,
			e.EvaluationSubmitted
		from EVAL_tblBBBEvaluation e WITH (NOLOCK)
		inner join vwEvaluationFinancials ve WITH (NOLOCK) on ve.BBBID = e.BBBID and ve.EvaluationDate = e.EvaluationDate
		inner join vwEvaluationFinancials ve2 WITH (NOLOCK) on ve2.BBBID = e.BBBID and YEAR(ve2.EvaluationDate) = YEAR(e.EvaluationDate) - 1
		inner join BBB WITH (NOLOCK) on BBB.BBBID = e.BBBID AND BBB.BBBBranchID = 0
		left outer join tblRegions WITH (NOLOCK) on tblRegions.RegionCode = BBB.Region
		left outer join BBBFinancials f2 WITH (NOLOCK) on f2.BBBID = BBB.BBBID and f2.BBBBranchID = BBB.BBBBranchID and
			f2.[Year] = '" . ($iYear) . "'
		left outer join BBBFinancials f3 WITH (NOLOCK) on f3.BBBID = BBB.BBBID and f3.BBBBranchID = BBB.BBBBranchID and
			f3.[Year] = '" . ($iYear - 1) . "'
		where
			BBB.BBBBranchID = 0 and
			/* include inactive BBBs */ BBB.BBBID != '2000' /*IsActive = 1*/ and
			/*
			('" . $iRegion . "' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('" . $iSalesCategory . "' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
			*/
			Year(e.EvaluationDate) = '{$iYear}' AND
			/* suppress entries with 0s in older years */
			(
				ve.TotalIncomeYTD > 0 or
				ve.TotalSalaries > 0 or
				f2.TotalSalaries > 0 or
				'{$iYear}' = YEAR(GETDATE()) - 1
			) and
			(BBB.Country != 'Canada' or year(e.EvaluationDate) >= '2012') /* include Canadian after 2011 */
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
				array('Sls Cat', $SortFields['Sales category'], '', 'right'),
				array('Reg', $SortFields['BBB region'], '', 'left'),
				array('Fiscal Year Ends', $SortFields['Fiscal year ends'], '', 'left'),
				array('Acctg Method', $SortFields['Accounting method'], '', 'left'),
				array('Total Revenue', $SortFields['Total revenue'], '', 'right'),
				array('Dues Revenue', $SortFields['Dues revenue'], '', 'right'),
				array('Total Salaries', $SortFields['Total salaries'], '', 'right'),
				array('Salaries to Revenue', $SortFields['Salaries to revenue'], '', 'right'),
				array('Total Expenses', $SortFields['Total expenses'], '', 'right'),
				array('Net Gain/ Loss', $SortFields['Net gain/loss'], '', 'right'),
				array('Begin Fund Balance', $SortFields['Begin fund balance'], '', 'right'),
				array('End Fund Balance', $SortFields['End fund balance'], '', 'right'),
				array('Change in Bal', $SortFields['Change in balance'], '', 'right'),
				array('ABs 12/31/' . $iYear, $SortFields['ABs'], '', 'right'),
				array('Estabs in Area ' . $iYear, $SortFields['Estabs in area'], '', 'right'),
				array('Market Share ' . $iYear, $SortFields['Market share'], '', 'right'),
				array('Avg Dues', $SortFields['Average dues'], '', 'right'),
				array('Submitted', $SortFields['Submitted'], '', 'left'),
			)
		);		
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			
			$oBBBID = $fields[0];
			$oBBBCity = AddApost($fields[1]);
			$oRegion = $fields[2];
			$oSalesCat = $fields[3];
			$oTotalRevenue = $fields[4];
			$oTotalExpenses = $fields[5];
			$oNet = $fields[6];
			$oFiscalYearEnds = FormatDate($fields[7]);
			$oBeginFundBalance = $fields[8];
			$oEndFundBalance = $fields[9];
			$oAccountingMethod = $fields[10];
			$oDuesRevenue = $fields[11];
			$oTotalSalaries = $fields[12];
			$oSalariesToRevenue = $fields[13];
			$oChangeFundBalance = $fields[14];
			$oABs = $fields[15];
			$oEstabsInArea = $fields[16];
			$oMarketShare = $fields[17];
			$oAverageDues = $fields[18];
			$oEvaluationSubmitted = $fields[19];
			/*
			if ($oEvaluationSubmitted != '1') {
				$oTotalRevenue = 0;
				$oDuesRevenue = 0;
				$oTotalSalaries = 0;
				$oSalariesToRevenue = 0;
				$oTotalExpenses = 0;
				$oNet = 0;
				$oBeginFundBalance = 0;
				$oEndFundBalance = 0;
				$oChangeFundBalance = 0;
				$oABs = 0;
				$oMarketShare = 0;
				$oAverageDues = 0;
			}
			*/

			$class = "";
			if ($oBBBID == $BBBID) {
				$class = "bold darkgreen";
			}
			if ($oBBBID == "0825" && $iYear == '2016') /* BBB Austin 2016 */ {
				$oBBBCity .= " (merger*)";
				$class = "bold gray";
			}

			if ($oEvaluationSubmitted == '0' || $oEvaluationSubmitted == '') {
				$oEvaluationSubmitted = 'No';
			}
			else {
				$oEvaluationSubmitted = 'Yes';
			}

			$report->WriteReportRow(
				array (
					/*$xcount,*/
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $oBBBID .
						"><span class='{$class}'>{$oBBBCity}</span></a>",
					$oSalesCat,
					$oRegion,
					$oFiscalYearEnds,
					$oAccountingMethod,
					intval($oTotalRevenue),
					intval($oDuesRevenue),
					intval($oTotalSalaries),
					FormatPercentage($oSalariesToRevenue, 0),
					intval($oTotalExpenses),
					intval($oNet),
					intval($oBeginFundBalance),
					intval($oEndFundBalance),
					FormatPercentage($oChangeFundBalance, 0),
					$oABs,
					$oEstabsInArea,
					FormatPercentage($oMarketShare, 1),
					intval($oAverageDues),
					$oEvaluationSubmitted
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				'',
				'',
				'',
				'',
				intval( array_sum( get_array_column($rs, 4) )),
				intval( array_sum( get_array_column($rs, 11) )),
				intval( array_sum( get_array_column($rs, 12) )),
				FormatPercentage(
					intval( array_sum( get_array_column($rs, 12) ) ) /
					intval( abs(array_sum( get_array_column($rs, 4) )) ),
					0),
				intval( array_sum( get_array_column($rs, 5) )),
				intval( array_sum( get_array_column($rs, 6) )),
				intval( array_sum( get_array_column($rs, 8) )),
				intval( array_sum( get_array_column($rs, 9) )),
				FormatPercentage(
					( intval( array_sum( get_array_column($rs, 9) ) ) - intval( array_sum( get_array_column($rs, 8) ) ) ) /
						intval( array_sum( get_array_column($rs, 8) ) ),
					0),
				intval( array_sum( get_array_column($rs, 15) )),
				intval( array_sum( get_array_column($rs, 16) )),
				FormatPercentage(
					intval( array_sum( get_array_column($rs, 15) ) ) /
					intval( abs(array_sum( get_array_column($rs, 16) )) ),
					1),
				intval(
					intval( array_sum( get_array_column($rs, 11) ) ) /
					intval( abs(array_sum( get_array_column($rs, 15) )) )
				),
			)
		);
		$report->WriteTotalsRow(
			array (
				'Averages',
				'',
				'',
				'',
				'',
				intval( array_sum( get_array_column($rs, 4)) / count( get_array_column($rs, 4)) ),
				intval( array_sum( get_array_column($rs, 11)) / count( get_array_column($rs, 11)) ),
				intval( array_sum( get_array_column($rs, 12)) / count( get_array_column($rs, 12)) ),
				FormatPercentage(
					intval( array_sum( get_array_column($rs, 13) ) ) / count($rs),
					1),
				intval( array_sum( get_array_column($rs, 5)) / count( get_array_column($rs, 5)) ),
				intval( array_sum( get_array_column($rs, 6)) / count( get_array_column($rs, 6)) ),
				intval( array_sum( get_array_column($rs, 8)) / count( get_array_column($rs, 8)) ),
				intval( array_sum( get_array_column($rs, 9)) / count( get_array_column($rs, 9)) ),
				FormatPercentage(
					intval( array_sum( get_array_column($rs, 14) ) ) / count($rs),
					1),
				intval( array_sum( get_array_column($rs, 15)) / count( get_array_column($rs, 15)) ),
				intval( array_sum( get_array_column($rs, 16)) / count( get_array_column($rs, 16)) ),
				FormatPercentage(
					intval( array_sum( get_array_column($rs, 17) ) ) / count($rs),
					1),
				intval(
					intval( array_sum( get_array_column($rs, 18) ) ) / count($rs)
				),
			)
		);
		$report->WriteTotalsRow(
			array (
				'Medians',
				'',
				'',
				'',
				'',
				intval( GetMedian( get_array_column($rs, 4) ) ),
				intval( GetMedian( get_array_column($rs, 11) ) ),
				intval( GetMedian( get_array_column($rs, 12) ) ),
				FormatPercentage( GetMedian( get_array_column($rs, 13) ), 0),
				intval( GetMedian( get_array_column($rs, 5) ) ),
				intval( GetMedian( get_array_column($rs, 6) ) ),
				intval( GetMedian( get_array_column($rs, 8) ) ),
				intval( GetMedian( get_array_column($rs, 9) ) ),
				FormatPercentage( GetMedian( get_array_column($rs, 14) ), 1),
				intval( GetMedian( get_array_column($rs, 15) ) ),
				intval( GetMedian( get_array_column($rs, 16) ) ),
				FormatPercentage( GetMedian( get_array_column($rs, 17) ), 1),
				intval( GetMedian( get_array_column($rs, 18) ) ),
			)
		);
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>