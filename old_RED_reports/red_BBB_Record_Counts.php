<?php
include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
$page->write_header2();

echo "<div class='main_section roundedborder'>";
echo "<div class='inner_section'>";
echo "Redirecting to new report...";
echo "</div>";
echo "</div>";

$page->write_pagebottom();

?>
<script language=javascript>
setTimeout(
	function redirect() {
		window.location = 'red_BBB_Data_Accuracy.php';
	},
	2000
);
</script>
