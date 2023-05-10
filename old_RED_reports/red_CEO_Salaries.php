<?php

/*
 * 01/07/16 MJS - new file
 * 05/24/16 MJS - added year to column header
 * 05/26/16 MJS - include inactive BBBs
 * 08/25/16 MJS - align column headers
 * 11/07/16 MJS - added new fields
 * 11/08/16 MJS - suppressed None value for new fields
 * 11/08/17 MJS - lock report based on global setting
 * 12/05/17 MJS - removed options
 * 12/11/17 MJS - added 1 option back in
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckCEOOnly($conn, $_SESSION['LoweredEmail']);

if ($SETTINGS['CEO_SALARIES_REPORT_LOCKED'] == '1') {
	die("<div class=main_section><div class=inner_section>
		<p class='red'>Update in Progress</p>
		<p>This report is currently being updated.  Please check back soon.</p>
		<p>Please <a href={$INTRANET_EXTERNAL_PATH}contact.php>Contact us</a> if you need assistance.</p>
		</div></div>"
	);
}

function GetLatestYearWithData() {
	global $conn;
	$mostrecentyear = 0;
	$query = "select top 1 [Year] from BBBFinancials WITH (NOLOCK) where
			CEOSalary >= 1 order by [Year] desc";
	$rs = $conn->execute($query);
	$rs->MoveFirst();
	while (! $rs->EOF) {
		$mostrecentyear = $rs->fields[0];
		$rs->MoveNext();
	}
	if ($mostrecentyear == '' || $mostrecentyear == 0 || $mostrecentyear < 2011) $mostrecentyear = date('Y') - 2;
	return $mostrecentyear;
}

// definitions (must come before input)

$RevenueEquation = "ROUND( ( CASE WHEN [TotalRevenue] < 3000000 THEN [TotalRevenue] ELSE 3000000 END ) / 1000000, 0, 1)";
$ABsEquation = "ROUND( ( CASE WHEN [Members] < 4000 THEN [Members] ELSE 4000 END ) / 1000, 0, 1)";
$YearsEquation = "ROUND( ( CASE WHEN [CEOYears] < 25 THEN [CEOYears] ELSE 25 END ) / 5, 0, 1)";
$EmployeesEquation = "ROUND( ( CASE WHEN [CEOEmployees] < 50 THEN [CEOEmployees] ELSE 50 END ) / 10, 0, 1)";
$PartTimeEmployeesEquation = "ROUND( ( CASE WHEN [CEOPartTimeEmployees] < 50 THEN [CEOPartTimeEmployees] ELSE 50 END ) / 10, 0, 1)";
$GroupByLabel[''] = '';
$GroupByLabel['SalesCategory'] = 'Sales Category';
$GroupByLabel['Region'] = 'Region';
$GroupByLabel[$RevenueEquation] = 'Total Revenue';
$GroupByLabel[$ABsEquation] = 'ABs';
$GroupByLabel[$YearsEquation] = 'Years Tenure';
$GroupByLabel['Benefit'] = 'Benefit';
$GroupByLabel['CEOBonus'] = 'Bonus';
$GroupByLabel['Factor'] = 'Bonus Factor';
$GroupByLabel[$EmployeesEquation] = 'Employees FT';
$GroupByLabel[$PartTimeEmployeesEquation] = 'Employees PT';

// get input

$iShowSource = $_POST['iShowSource'];
$mostrecentyear = GetLatestYearWithData();
$iYear = ValidYear( Numeric2( GetInput('iYear', $mostrecentyear) ) );
$iCriteria = $_REQUEST['iCriteria'];
if ($iCriteria == '') $iCriteria = 'Sales Category';
if ($iCriteria == 'Sales Category') $iGroupBy = 'SalesCategory';
else if ($iCriteria == 'Region') $iGroupBy = 'Region';
else if ($iCriteria == 'Revenue') $iGroupBy = $RevenueEquation;
else if ($iCriteria == 'ABs') $iGroupBy = $ABsEquation;
else if ($iCriteria == 'Years Tenure') $iGroupBy = $YearsEquation;
else if ($iCriteria == 'Benefits') $iGroupBy = 'Benefit';
else if ($iCriteria == 'Bonus') $iGroupBy = 'CEOBonus';
else if ($iCriteria == 'Bonus Factors') $iGroupBy = 'Factor';
else if ($iCriteria == 'Full Time Employees') $iGroupBy = $EmployeesEquation;
else if ($iCriteria == 'Part Time Employees') $iGroupBy = $PartTimeEmployeesEquation;

// build input form

$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Year', $iYear, "width:50px;", '', 'year');
$input_form->AddSelectField('iCriteria', 'Criteria', $iCriteria,
		array(
			'Sales Category' => 'Sales Category',
			'Region' => 'Region',
			'Revenue' => 'Revenue',
			'ABs' => 'ABs',
			/*
			'Years Tenure' => 'Years Tenure',
			'Benefits' => 'Benefits',
			'Bonus' => 'Bonus',
			'Bonus Factors' => 'Bonus Factors',
			'Full Time Employees' => 'Full Time Employees',
			'Part Time Employees' => 'Part Time Employees'
			*/
		), '', '', '', '');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	if ($iGroupBy) {
		$query_GroupBy_column = ", " . $iGroupBy;
		$query_GroupBy_phrase = " GROUP BY {$iGroupBy} ";
	}
	$query = "
		declare @temp_item table (
			BBBID varchar(10),
			Benefit varchar(100),
			Factor varchar(100)
		);
		declare @xpos smallint;
		declare @lastpoint smallint;
		declare @BBBID varchar(10);
		IF '{$iGroupBy}' = 'Benefit' BEGIN
			declare @Benefit varchar(100);
			declare @CEOBenefits varchar(1000);
			declare c cursor for
				SELECT f.BBBID, CEOBenefits
				FROM BBB WITH (NOLOCK)
				INNER JOIN BBBFinancials f WITH (NOLOCK) on
					f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and f.[Year] = '{$iYear}'
				WHERE
					BBB.BBBID != '2000' /* include inactive */ /*BBB.IsActive = 1*/ AND BBB.BBBBranchID = 0 AND
					[CEOSalary] > 0 AND [Members] > 0 AND [TotalRevenue] > 0;
			open c;
			fetch next from c into @BBBID, @CEOBenefits;
			while @@fetch_status = 0
			begin
				set @xpos = 0;
				set @lastpoint = 0;
				while @xpos <= LEN(@CEOBenefits) BEGIN
					SET @xpos = @xpos + 1;
					IF SUBSTRING(@CEOBenefits,@xpos,1) = ',' OR @xpos = LEN(@CEOBenefits) BEGIN
						IF @xpos = LEN(@CEOBenefits) SET @xpos = @xpos + 1;
						SET @Benefit = SUBSTRING(@CEOBenefits, @lastpoint, @xpos - @lastpoint);
						INSERT INTO @temp_item (BBBID, Benefit) VALUES (@BBBID, @Benefit);
						SET @lastpoint = @xpos + 1;
					END
				END
				fetch next from c into @BBBID, @CEOBenefits;
			end
			close c;
			deallocate c;
		END
		IF '{$iGroupBy}' = 'Factor' BEGIN
			declare @Factor varchar(100);
			declare @CEOBonusFactors varchar(1000);
			declare c2 cursor for
				SELECT f.BBBID, CEOBonusFactors
				FROM BBB WITH (NOLOCK)
				INNER JOIN BBBFinancials f WITH (NOLOCK) on
					f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and f.[Year] = '{$iYear}'
				WHERE
					BBB.BBBID != '2000' /* include inactive */ /*BBB.IsActive = 1*/ AND BBB.BBBBranchID = 0 AND
					[CEOSalary] > 0 AND [Members] > 0 AND [TotalRevenue] > 0
				;
			open c2;
			fetch next from c2 into @BBBID, @CEOBonusFactors;
			while @@fetch_status = 0
			begin
				set @xpos = 0;
				set @lastpoint = 0;
				while @xpos <= LEN(@CEOBonusFactors) BEGIN
					SET @xpos = @xpos + 1;
					IF SUBSTRING(@CEOBonusFactors,@xpos,1) = ',' OR @xpos = LEN(@CEOBonusFactors) BEGIN
						IF @xpos = LEN(@CEOBonusFactors) SET @xpos = @xpos + 1;
						SET @Factor = SUBSTRING(@CEOBonusFactors, @lastpoint, @xpos - @lastpoint);
						INSERT INTO @temp_item (BBBID, Factor) VALUES (@BBBID, @Factor);
						SET @lastpoint = @xpos + 1;
					END
				END
				fetch next from c2 into @BBBID, @CEOBonusFactors;
			end
			close c2;
			deallocate c2;
		END
		SELECT
			COUNT(*),
			AVG([CEOSalary]),
			AVG([CEOSalary] / [TotalRevenue]),
			AVG([TotalRevenue]),
			AVG([DuesRevenue])
			{$query_GroupBy_column}
		FROM BBB WITH (NOLOCK)
		INNER JOIN BBBFinancials f WITH (NOLOCK) on
			f.BBBID = BBB.BBBID and f.BBBBranchID = BBB.BBBBranchID and f.[Year] = '{$iYear}'
		LEFT OUTER JOIN @temp_item t ON t.BBBID = BBB.BBBID
		WHERE
			BBB.BBBID != '2000' /* include inactive */ /*BBB.IsActive = 1*/ AND BBB.BBBBranchID = 0 AND
			[CEOSalary] > 0 AND [Members] > 0 AND [TotalRevenue] > 0 AND
			('{$iGroupBy}' != 'Benefit' or CEOBenefits > '') AND
			('{$iGroupBy}' != 'Factor' or CEOBonusFactors > '') AND
			('{$iGroupBy}' != 'CEOBonus' or CEOBonus > '') AND
			('{$iGroupBy}' != '{$YearsEquation}' or CEOYears > '') AND
			('{$iGroupBy}' != '{$EmployeesEquation}' or CEOEmployees > '') AND
			('{$iGroupBy}' != '{$PartTimeEmployeesEquation}' or CEOPartTimeEmployees > '')
		{$query_GroupBy_phrase};
		";
	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		if ($iGroupBy == 'SalesCategory') $tmp_align = 'left';
		else $tmp_align = 'left';
		$headers = array (
			array($GroupByLabel[$iGroupBy], '', '', $tmp_align),
			array('BBBs', '', '', 'left'),
			array("Avg CEO Salary {$iYear}", '', '', 'left'),
			array('Avg Tot Revenue', '', '', 'left'),
			array('Avg Dues Revenue', '', '', 'left'),
			array('Avg Salary to Tot Rev', '', '', 'left'),
		);
		$report->WriteHeaderRow($headers);
		foreach ($rs as $k => $fields) {
			$Count = $fields[0];
			$Average = intval($fields[1]);
			$Ratio = FormatPercentage($fields[2]);
			$Revenue = intval($fields[3]);
			$Dues = intval($fields[4]);
			$oGroupBy = $fields[5];
			if ($Count >= 1 && $Count <= 2) {
				$Average = 'Withheld for Privacy';
				$Ratio = 'Withheld for Privacy';
				$Revenue = 'Withheld for Privacy';
				$Dues = 'Withheld for Privacy';
			}
			if ($GroupByLabel[$iGroupBy] == 'Total Revenue' && $oGroupBy == '0') $oGroupBy =
				"$" . $oGroupBy . " - $999,999";
			else if ($GroupByLabel[$iGroupBy] == 'Total Revenue' && $oGroupBy != '3') $oGroupBy =
				"$" . $oGroupBy . ",000,000 - $" . $oGroupBy . ",999,999";
			else if ($GroupByLabel[$iGroupBy] == 'Total Revenue' && $oGroupBy == '3') $oGroupBy =
				"$" . $oGroupBy . ",000,000+";

			else if ($GroupByLabel[$iGroupBy] == 'ABs' && $oGroupBy == '0') $oGroupBy =
				$oGroupBy . " - 999";
			else if ($GroupByLabel[$iGroupBy] == 'ABs' && $oGroupBy != '4') $oGroupBy =
				$oGroupBy . ",000 - " . $oGroupBy . ",999";
			else if ($GroupByLabel[$iGroupBy] == 'ABs' && $oGroupBy == '4') $oGroupBy =
				$oGroupBy . ",000+";

			else if ($GroupByLabel[$iGroupBy] == 'Years Tenure' && $oGroupBy != '5') $oGroupBy = ($oGroupBy * 5) . "-" . (($oGroupBy * 5) + 4);
			else if ($GroupByLabel[$iGroupBy] == 'Years Tenure' && $oGroupBy == '5') $oGroupBy = "25+";

			else if ($GroupByLabel[$iGroupBy] == 'Employees FT' && $oGroupBy == '5') $oGroupBy = "50+";
			else if ($GroupByLabel[$iGroupBy] == 'Employees FT' && $oGroupBy != '5') $oGroupBy = ($oGroupBy * 10) . " - " . (($oGroupBy * 10) + 9);

			else if ($GroupByLabel[$iGroupBy] == 'Employees PT' && $oGroupBy == '5') $oGroupBy = "50+";
			else if ($GroupByLabel[$iGroupBy] == 'Employees PT' && $oGroupBy != '5') $oGroupBy = ($oGroupBy * 10) . " - " . (($oGroupBy * 10) + 9);

			else if ($GroupByLabel[$iGroupBy] == 'Benefit' && $oGroupBy == '') $oGroupBy = "None";

			else if ($GroupByLabel[$iGroupBy] == 'Bonus' && $oGroupBy == 0 && $oGroupBy != '0') $oGroupBy = "Not Selected";
			else if ($GroupByLabel[$iGroupBy] == 'Bonus' && $oGroupBy == '0') $oGroupBy = "None";
			else if ($GroupByLabel[$iGroupBy] == 'Bonus' && $oGroupBy == '1') $oGroupBy = "0 - 4%";
			else if ($GroupByLabel[$iGroupBy] == 'Bonus' && $oGroupBy == '2') $oGroupBy = "5 - 9%";
			else if ($GroupByLabel[$iGroupBy] == 'Bonus' && $oGroupBy == '3') $oGroupBy = "10 - 14%";
			else if ($GroupByLabel[$iGroupBy] == 'Bonus' && $oGroupBy == '4') $oGroupBy = "15%+";

			else if ($GroupByLabel[$iGroupBy] == 'Bonus Factor' && $oGroupBy == '') $oGroupBy = "None";

			$row = array (
				$oGroupBy,
				$Count,
				$Average,
				$Revenue,
				$Dues,
				$Ratio,
			);
			$report->WriteReportRow($row, '', 'left');
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>