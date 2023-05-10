<?php

/*
 * 11/30/16 MJS - new file
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


$iYear = Numeric2($_POST['iYear']);
if (! $iYear && $EvaluationDate && $EvaluationDate != '1/1/') $iYear = GetYear($EvaluationDate) + 1;
else $iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') ) ) );
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Evaluation round year', $iYear, "width:50px;", '', 'year');
$SortFields = array(
	'BBB city' => 'BBB.NicknameCity',
	'Sales category' => 'BBB.SalesCategory,BBB.NicknameCity',
	'Accounting method' => 'AccountingMethod,BBB.NicknameCity',
	'Fiscal year ends' => 'FiscalYearEnds,BBB.NicknameCity',
	'Total revenue' => 'TotalRevenue',
	'New AB sales' => 'NewABSales',
	'Non-dues revenue' => 'NonDuesRevenue',
	'Total expenses' => 'TotalExpenses',
	'Total salaries' => 'TotalSalaries',
	'Sales expenses' => 'SalesExpenses',
	'Deprec expenses' => 'DeprecExpenses',
	'Net' => 'Net',
	'Total cash reserves' => 'TotalCashReserves',
	'Total cash assets' => 'TotalCashAssets',
	'Total assets' => 'TotalAssets',
	'Current liab' => 'CurrentLiabilities',
	'Total liab' => 'TotalLiabilities',
	'End balance' => 'EndFundBalance'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$iYear--;

	if ($EvaluationDate == '' || $EvaluationDate == '1/1/') $EvaluationDate = "1/1/" . $iYear;
	$iEvaluationDate = $_REQUEST['iEvaluationDate'];
	if (! $iEvaluationDate) $iEvaluationDate = '1/1/' . $iYear;
	if ($iEvaluationDate) $EvaluationDate = $iEvaluationDate;
	session_register('EvaluationDate');
	$_SESSION['EvaluationDate'] = $EvaluationDate;

	$query = "
		SELECT
			BBB.NickNameCity + ', ' + BBB.State,
			LEFT(BBB.Country,3) as Country,
			BBB.SalesCategory,
			(Select FieldValueText from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'AccountingMethod'
				) as AccountingMethod,
			(Select FieldValueDate from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'FiscalYearEnds'
				) as FiscalYearEnds,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'TotalIncomeYTD'
				) as TotalRevenue,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'NewMemberSalesYTD'
				) as NewABSales,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'NonDuesIncomeYTD'
				) as NonDuesRevenue,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'TotalExpenseYTD'
				) as TotalExpenses,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'TotalSalaries'
				) as TotalSalaries,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'SalesExpenseYTD'
				) as SalesExpenses,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'DeprecExpenseYTD'
				) as DeprecExpenses,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'NetYTD'
				) as Net,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'TotalCashReserves'
				) as TotalCashReserves,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'TotalAssetsCash'
				) as TotalCashAssets,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'TotalAssets'
				) as TotalAssets,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'CurrentLiabilities'
				) as CurrentLiabilities,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'TotalLiabilities'
				) as TotalLiabilities,
			(Select FieldValueMoney from EVAL_tblBBBStandardFieldValue v WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBStandardField f WITH (NOLOCK) ON
					f.EvaluationDate = v.EvaluationDate and f.StandardNumber = v.StandardNumber and f.FieldNumber = v.FieldNumber
				WHERE
				v.BBBID = BBB.BBBID and v.EvaluationDate = '1/1/{$iYear}' and
				f.FieldName = 'EndFundBalance'
				) as EndFundBalance
		FROM BBB WITH (NOLOCK) WHERE
			BBB.BBBBranchID = 0 and BBB.IsActive = 1
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
				/*array('Country', $SortFields['Country'], '', 'left'),*/
				array('Sls Cat', $SortFields['Sales category'], '', 'left'),
				array('Acctg Method', $SortFields['Accounting method'], '', 'left'),
				array($iYear . ' Fiscal Year Ends', $SortFields['Fiscal year ends'], '', 'left'),
				array($iYear . ' Total Revenue', $SortFields['Total revenue'], '', 'right'),
				array($iYear . ' New AB Sales', $SortFields['New AB sales'], '', 'right'),
				array($iYear . ' Non-Dues Revenue', $SortFields['Non-dues revenue'], '', 'right'),
				array($iYear . ' Total Expenses', $SortFields['Total expenses'], '', 'right'),
				array($iYear . ' Total Salaries', $SortFields['Total salaries'], '', 'right'),
				array($iYear . ' Sales Expenses', $SortFields['Sales expenses'], '', 'right'),
				array($iYear . ' Deprec Expenses', $SortFields['Deprec expenses'], '', 'right'),
				array($iYear . ' Net', $SortFields['Net'], '', 'right'),
				array($iYear . ' Required Reserves', $SortFields['Required reserves'], '', 'right'),
				array($iYear . ' Total Cash Reserves', $SortFields['Total cash reserves'], '', 'right'),
				array($iYear . ' Daily Expenses', $SortFields['Daily expenses'], '', 'right'),
				array($iYear . ' Actual Days Reserves', $SortFields['Actual days reserves'], '', 'right'),
				array($iYear . ' Total Cash Assets', $SortFields['Total cash assets'], '', 'right'),
				array($iYear . ' Total Assets', $SortFields['Total assets'], '', 'right'),
				array($iYear . ' Current Liab', $SortFields['Current liab'], '', 'right'),
				array($iYear . ' Total Liab', $SortFields['Total liab'], '', 'right'),
				array($iYear . ' End Balance', $SortFields['End balance'], '', 'right')
			)
		);
		foreach ($rs as $k => $fields) {
			$oTotalExpenses = floatval($fields[8]);
			$oSalesExpenses = floatval($fields[10]);
			$oDeprecExpenses = floatval($fields[11]);
			$oTotalCashReserves = floatval($fields[13]);
			$oRequiredReserves = 0.0000;
			$oDailyExpenses = 0.0000;
			$oActualDaysReserves = 0.0000;
			$netexpenses = floatval($oTotalExpenses - $oSalesExpenses - $oDeprecExpenses);
			$oDailyExpenses = floatval($netexpenses / 365.0000);
			$oRequiredReserves = floatval($oDailyExpenses * 60.0000);
			if ($netexpenses > 0) // don't divide by zero
				$oActualDaysReserves = floatval($oTotalCashReserves / $oDailyExpenses);

			$report->WriteReportRow(
				array (
					$fields[0],
					/*$fields[1],*/
					$fields[2],
					$fields[3],
					FormatDate($fields[4]),
					intval($fields[5]),
					intval($fields[6]),
					intval($fields[7]),
					intval($fields[8]),
					intval($fields[9]),
					intval($fields[10]),
					intval($fields[11]),
					intval($fields[12]),
					intval($oRequiredReserves),
					intval($fields[13]),
					intval($oDailyExpenses),
					intval($oActualDaysReserves),
					intval($fields[14]),
					intval($fields[15]),
					intval($fields[16]),
					intval($fields[17]),
					intval($fields[18]),
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