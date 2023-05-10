<?php

/*
 * 08/29/16 MJS - new file
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->Redirect('red_BBB_Service_Areas.php');

?>