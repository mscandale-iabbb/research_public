<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 11/18/15 MJS - refactored, splitting into include file that defines class, adding object for report execution
 * 11/18/15 MJS - added option for batch processing
 * 11/19/15 MJS - more tweaking for batch option
 * 11/20/15 MJS - more tweaking for shell option
 * 11/20/15 MJS - added setting of request parameters iPageNumber, iPageSize, iBatch, and use_saved
 */

include '../intranet/common_includes.php';

include 'red_Top_Businesses_By_Inquiries.inc.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


if (! $input['shell']) {
	$input['iDateFrom'] = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
	$input['iDateTo'] = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
	$input['iCountry'] = $_POST['iCountry'];
	$input['iTier'] = NoApost($_POST['iTier']);
	$input['iBBBID'] = Numeric2($_REQUEST['iBBBID']);
	$input['iTOB'] = NoApost($_POST['iTOB']);
	$input['iConsumerBBBID'] = Numeric2($_REQUEST['iConsumerBBBID']);
	$input['iState'] = NoApost($_POST['iState']);
	$input['iAB'] = NoApost($_REQUEST['iAB']);
	$input['iSize'] = NoApost($_REQUEST['iSize']);
	$input['iRating'] = NoApost($_REQUEST['iRating']);
	$input['iMaxRecs'] = CleanMaxRecs($_REQUEST['iMaxRecs']);
	$input['iSortBy'] = NoApost($_POST['iSortBy']);
	if (! $input['iSortBy']) $input['iSortBy'] = 'Inquiries DESC';
	$input['iShowSource'] = $_POST['iShowSource'];
	$input['iPageNumber'] = Numeric2($_REQUEST['iPageNumber']);
	$input['iPageSize'] = Numeric2($_REQUEST['iPageSize']);
	$input['iBatch'] = NoApost($_REQUEST['iBatch']);
	$input['use_saved'] = NoApost($_REQUEST['use_saved']);
}

$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Dates',$input['iDateFrom']);
$input_form->AddDateField('iDateTo',' to ',$input['iDateTo'],'sameline');
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $input['iBBBID'], $input_form->BuildBBBCitiesArray('all') );
$input_form->AddTextField('iTOB','TOB contains word/phrase',$input['iTOB']);
$input_form->AddSelectField('iCountry', 'BBB country', $input['iCountry'], $input_form->BuildBBBCountriesArray() );
$input_form->AddMultipleSelectField('iSize', 'Business size', $input['iSize'],
	$input_form->BuildSizesArray('all'), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iRating', 'Business rating', $input['iRating'],
	$input_form->BuildRatingsArray('all'), '', '', '', 'width:300px');
$input_form->AddSelectField('iAB','AB status',$input['iAB'], array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input['SortFields'] = array(
	'Inquiries' => 'Inquiries',
	'Business name' => 'BusinessName',
	'BBB city' => 'BBB.NicknameCity,b.BusinessName',
	'TOB code' => 'b.TOBID,b.BusinessName',
	'TOB description' => 'tblYPPA.yppa_text,b.BusinessName',
	'AB' => 'AB,b.BusinessName',
	'Rating' => 'r.BBBRatingSortOrder,b.BusinessName',
	'Size' => 's.SizeOfBusinessSortOrder,b.BusinessName'
);
$input_form->AddSortOptions($input['iSortBy'], $input['SortFields']);
$input_form->AddPagingOption();
$input_form->AddExportOptions('batch');
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST || $input['shell']) {
	$report = new red_Top_Businesses_By_Inquiries($conn, $input);
	$rs = $report->FetchData();
	$report->WriteReport($rs);
}

?>