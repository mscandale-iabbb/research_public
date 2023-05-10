<?php

/*
 * 10/27/14, MJS - new file 
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
//include 'links.php';
$page->write_header2();
//include 'red_tabs.php';
//$page->write_tabs($tabs);

/*
$size_details = array(
	'Micro-small' => 'typically under 10 employees and under $1 million revenue',
	'Small' => 'typically 10 to 50 employees and $1-$20 million revenue',
	'Medium' => 'typically 50 to 500 employees and $20-$100 million revenue',
	'Large' => 'typically 500 to 5,000 employees and $100 million to $1 billion revenue',
	'Giant' => 'typically 5,000 to 20,000 employees and $1-$10 billion revenue',
	'Mega-Giant' => 'typically 20,000 to 40,000 employees and $10-$50 billion revenue',
	'Colossal' => 'typically 40,000+ employees and $50+ billion revenue',
	'Mega-Colossal' => 'typically ',
);

$employee_ranges = array(
	array('Micro-small', 0, 9),
	array('Small', 10, 49),
	array('Medium', 50, 499),
	array('Large', 500, 4999),
	array('Giant', 5000, 19999),
	array('Mega-Giant', 20000, 39999),
	array('Colossal', 40000, 9999999999),
	array('Mega-Colossal', 9999999999, 9999999999),
);

$revenue_ranges = array(
	array('Micro-small', 1, 999999),
	array('Small', 1000000, 19999999),
	array('Medium', 20000000, 99999999),
	array('Large', 100000000, 999999999),
	array('Giant', 1000000000, 9999999999),
	array('Mega-Giant', 10000000000, 49999999999),
	array('Colossal', 50000000000, 999999999999),
	array('Mega-Colossal', 999999999999, 999999999999),
);
*/

$iBBBID = Numeric2($_GET['iBBBID']);
$iBusinessID = Numeric2($_GET['iBusinessID']);

echo "<div class='main_section roundedborder'>";
echo "<div class='inner_section'>";
if ($_GET) {
	$query = "SELECT
			b.BusinessID as BusinessID,
			REPLACE(b.BusinessName,'&#39;','''') as BusinessName,
			b.SizeOfBusiness as Size,
			b.NumberOfEmployees as Employees2,
			d.SALES as Revenue,
			d.EMPLOYEES_HERE as Employees,
			y.yppa_text as TOB,
			b.ReportType as Type,
			b.Website
		from Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		inner JOIN DandB d WITH (NOLOCK) ON d.BBBID = b.BBBID and d.BusinessID = b.BusinessID
		left outer join tblYPPA y WITH (NOLOCK) on y.yppa_code = b.TOBID
		where
			b.BBBID = '" . $iBBBID . "' and b.BusinessID = '" . $iBusinessID . "'
		";

	$rsraw = $conn->execute("$query");
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			$oBBBEmployees = $fields[3];
			$oRevenue = $fields[4];
			$oDBEmployees = $fields[5];
			$oWebsite = $fields[8];
			$oSizeDetails = $size_details[$fields[2]];
			$oEstimatedEmployees = round($oRevenue / 100000);
			$oEstimatedRevenue = round($oBBBEmployees * 100000);
			foreach ($employee_ranges as $r) {
				if ($oDBEmployees >= $r[1] && $oDBEmployees <= $r[2]) $oDBEmployeesGuess = $r[0];
				if ($oBBBEmployees >= $r[1] && $oBBBEmployees <= $r[2]) $oBBBEmployeesGuess = $r[0];
				if ($oEstimatedEmployees >= $r[1] && $oEstimatedEmployees <= $r[2]) $oEstimatedEmployeesGuess = $r[0];
			}
			foreach ($revenue_ranges as $r) {
				if ($oRevenue >= $r[1] && $oRevenue <= $r[2]) $oRevenueGuess = $r[0];
				if ($oEstimatedRevenue >= $r[1] && $oEstimatedRevenue <= $r[2]) $oEstimatedRevenueGuess = $r[0];
			}

			// add commas
			$oBBBEmployees = AddComma($oBBBEmployees);
			$oRevenue = AddComma($oRevenue);
			$oDBEmployees = AddComma($oDBEmployees);
			$oEstimatedEmployees = AddComma($oEstimatedEmployees);
			$oEstimatedRevenue = AddComma($oEstimatedRevenue);

			echo "
				<table width=100%>
				<tr><td class='bold' width=20%>Name <td>{$fields[1]}
				<tr><td class='bold'>ID <td>{$fields[0]}
				<tr><td class='bold'>TOB <td>{$fields[6]}
				<tr><td class='bold'>Type <td>{$fields[7]}
				<tr><td>&nbsp;
				<!--
				<tr><td class='bold'>Size <td><span class='red bold'>{$fields[2]}</span> ({$size_details[$fields[2]]})
				<tr><td>&nbsp;
				-->
				<tr><td class='bold'>Employees per BBB <td>{$oBBBEmployees} <!--(typically {$oBBBEmployeesGuess})-->
				<tr><td class='bold'>Employees per D&B <td>{$oDBEmployees} <!--(typically {$oDBEmployeesGuess})-->
				<tr><td class='bold'>Revenue per D&B <td>$ {$oRevenue} <!--(typically {$oRevenueGuess})-->
				<tr><td>&nbsp;
				<tr><td class='bold'>Estimated employees based on revenue <td>{$oEstimatedEmployees} <!--(typically {$oEstimatedEmployeesGuess})-->
				<tr><td>&nbsp;
				<tr><td class='bold'>Estimated revenue based on BBB employees <td>$ {$oEstimatedRevenue} <!--(typically {$oEstimatedRevenueGuess})-->
				</table>
				";
			if ($oWebsite) echo "<p> &nbsp; </p>
				<p>You may want to check their website at <a target=_new href=http://{$oWebsite}>{$oWebsite}</a> to
				look for more clues.</p>";
		}
	}
}
echo "<p> &nbsp; </p>";
echo "<p><a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a></p>";
echo "</div>";
echo "</div>";

$page->write_pagebottom();

?>