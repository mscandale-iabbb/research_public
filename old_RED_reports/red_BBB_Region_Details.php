<?php

/*
 * 09/27/16 MJS - new file
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();

$iRegion = NoApost($_GET['iRegion']);

echo "<div class='main_section roundedborder'>";
echo "<table class='report_table'>";
echo "<tr><th align=left>BBBs in {$iRegion} Region";
if ($iRegion) {
	$query = "
		SELECT
			BBB.NickNameCity + ', ' + BBB.State
		FROM BBB WITH (NOLOCK)
		WHERE
			BBB.Region = '{$iRegion}' and
			BBB.BBBBranchID = '0' and BBB.IsActive = '1'
		ORDER BY BBB.NickNameCity
		";

	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			echo "<tr><td>" . AddApost($fields[0]);
		}
	}
}
echo "<tr><td colspan=2 class='column_header thickpadding center'>";
echo "<a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a>";
echo "</table>";
echo "</div>";

$page->write_pagebottom();

?>