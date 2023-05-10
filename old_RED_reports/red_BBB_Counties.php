<?php

/*
 * 07/12/16 MJS - new file
 * 07/13/16 MJS - changed search for county field to matches logic
 * 08/25/16 MJS - aligned column headers
 * 01/03/17 MJS - changed calls to define links and tabs
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = NoApost($_POST['iBBBID']);
if (! $_POST && $BBBID != '2000') $iBBBID = $BBBID;
else if (! $_POST && $BBBID == '2000') $iBBBID = '1066';
$iCounty = NoApost($_POST['iCounty']);
$iState = NoApost($_POST['iState']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('all', '') );
$input_form->AddTextField('iCounty', 'County', $iCounty, "width:100px;");
$input_form->AddSelectField('iState', 'BBB state', $iState, $input_form->BuildStatesArray(),
	'', '', '', 'width:350px');
$SortFields = array(
	'County' => 'CountyNameProper',
	'BBB' => 'NicknameCity,CountyNameProper',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT
			'BBB ' + BBB.NickNameCity + ', ' + BBB.State,
			c.CountyNameProper + ', ' + c.CountyState
		FROM BBBCounty c WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) ON BBB.BBBIDFull = c.BBBIDFull
		WHERE
			CountyNameProper > '' AND
			(
				(
					'{$iCounty}' > '' and
					CountyNameProper like '%{$iCounty}%'
				) or
				CountyState = '{$iState}' or
				(
					'{$iCounty}' = '' and
					'{$iState}' = '' and
					('{$iBBBID}' = '' OR c.BBBIDFull = '{$iBBBID}')
				)
			)
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
				array('BBB', $SortFields['BBB'], '', 'left'),
				array('County', $SortFields['County'], '', 'left'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					AddApost($fields[0]),
					AddApost($fields[1]),
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