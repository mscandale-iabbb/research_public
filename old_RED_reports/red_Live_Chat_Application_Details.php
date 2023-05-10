<?php

/*
 * 02/01/16 MJS - new file
 * 06/16/17 MJS - added new fields
 */

include '../intranet/init_allow_vendors.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();


function ShowField($label, $value) {
	echo "<tr>";
	echo "<td class='labelback' width=25%>";
	echo $label;
	echo "<td class='table_cell'>";
	echo $value;
}

function BitToX($value) {
	if ($value == 1) return 'X';
	else return '';
}

$salary_descriptions = array(
	'1' => 'Under $25k',
	'2' => '$25k to $35k',
	'3' => '$35k to $45k',
	'4' => '$45k to $55k',
	'5' => '$55k to $65k',
	'6' => '$65k to $75k',
	'7' => '$75k to $90k',
	'8' => 'Over $90k'
);


$iLastName = NoApost($_GET['iLastName']);


echo "<div class='main_section roundedborder'>";
echo "<table class='report_table'>";
if ($_GET) {
	$query = "SELECT
			LastName, Title, Phone, BBB, Email, Years, Salary,
			AdReview1, AdReview2, Complaints1, Complaints2, CharityReview1, CharityReview2,
			Investigations1, Investigations2, Accreditation1, Accreditation2, Reports1, Reports2,
			CMS1, CMS2, Online1, Online2, Other1, Other2, OtherText, Experience, [Database], Training, CEOName,
			CustomerReviews1, CustomerReviews2, Brand1, Brand2, ScamTracker1, ScamTracker2,
			epom1, epom2, epi1, epi2, ga1, ga2
		FROM EmployeeApplication WITH (NOLOCK)
		WHERE LastName = '{$iLastName}'
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			ShowField("Name", AddApost($fields[0]));
			ShowField("Title", AddApost($fields[1]));
			ShowField("Phone", AddApost($fields[2]));
			ShowField("BBB", AddApost($fields[3]));
			ShowField("Email", AddApost($fields[4]));
			ShowField("Years of service", $fields[5]);
			ShowField("Salary range", $salary_descriptions[$fields[6]]);

			ShowField("Accreditation primary expertise", BitToX($fields[15]));
			ShowField("Accreditation secondary expertise", BitToX($fields[16]));
			ShowField("Ad review primary expertise", BitToX($fields[7]));
			ShowField("Ad review secondary expertise", BitToX($fields[8]));
			ShowField("Brand primary expertise", BitToX($fields[32]));
			ShowField("Brand secondary expertise", BitToX($fields[33]));
			ShowField("Business reviews/reporting primary expertise", BitToX($fields[17]));
			ShowField("Business reviews/reporting secondary expertise", BitToX($fields[18]));
			ShowField("Charity review primary expertise", BitToX($fields[11]));
			ShowField("Charity review secondary expertise", BitToX($fields[12]));
			ShowField("CMS primary expertise", BitToX($fields[19]));
			ShowField("CMS secondary expertise", BitToX($fields[20]));
			ShowField("Complaints primary expertise", BitToX($fields[9]));
			ShowField("Complaints secondary expertise", BitToX($fields[10]));
			ShowField("Customer reviews primary expertise", BitToX($fields[30]));
			ShowField("Customer reviews secondary expertise", BitToX($fields[31]));
			ShowField("Investigations primary expertise", BitToX($fields[13]));
			ShowField("Investigations review secondary expertise", BitToX($fields[14]));
			ShowField("Online products primary expertise", BitToX($fields[21]));
			ShowField("Online products secondary expertise", BitToX($fields[22]));
			ShowField("Scam Tracker primary expertise", BitToX($fields[34]));
			ShowField("Scam Tracker secondary expertise", BitToX($fields[35]));

			/* IT-related fields */
			ShowField("Epom primary expertise", BitToX($fields[36]));
			ShowField("Epom secondary expertise", BitToX($fields[37]));
			ShowField("Epi primary expertise", BitToX($fields[38]));
			ShowField("Epi secondary expertise", BitToX($fields[39]));
			ShowField("GA primary expertise", BitToX($fields[40]));
			ShowField("GA secondary expertise", BitToX($fields[41]));

			ShowField("Other primary expertise", BitToX($fields[23]));
			ShowField("Other secondary expertise", BitToX($fields[24]));
			ShowField("Other expertise description", AddApost($fields[25]));

			ShowField("Relevant experience", AddApost($fields[26]));
			ShowField("Database experience", AddApost($fields[27]));
			ShowField("Training", AddApost($fields[28]));
			ShowField("CEO certification", AddApost($fields[29]));

			//ShowField("Narrative", strip_tags($fields[14]));
		}
	}
}
echo "<tr><td colspan=2 class='column_header thickpadding center'>";
echo "<a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a>";
echo "</table>";
echo "</div>";

$page->write_pagebottom();

?>