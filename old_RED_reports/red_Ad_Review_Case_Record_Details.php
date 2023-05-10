<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 08/25/16 MJS - aligned column headers
 * 01/03/17 MJS - changed calls to define links and tabs
 * 11/15/17 MJS - added option for NAICS
 * 02/28/18 MJS - cleaned up code
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);


$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iBusinessName = NoApost($_REQUEST['iBusinessName']);
$iNAICS = NoApost($_POST['iNAICS']);
$iTOB = NoApost($_REQUEST['iTOB']);
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iNotBBBID = Numeric2($_REQUEST['iNotBBBID']);
$iMaxRecs = CleanMaxRecs($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddTextField('iBusinessName', 'Business name', $iBusinessName, "width:175px;");
$input_form->AddSelectField('iNAICS', 'Industry', $iNAICS, $input_form->BuildNAICSGroupArray() );
$input_form->AddTextField('iTOB','TOB contains word/phrase',$iTOB);
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddSelectField('iNotBBBID', 'Not processed by BBB', $iNotBBBID, $input_form->BuildBBBCitiesArray('all') );
$SortFields = array(
	'Business name' => 'b.BusinessName',
	'Business postal code' => 'b.PostalCode',
	'BBB city' => 'NicknameCity',
	'Close code' => 'a.CloseCode',
	'Date closed' => 'a.DateClosed',
	'ID' => 'a.AdReviewID',
	'Nature' => 'a.NatureOfReview',
	'TOB code' => 'b.TOBID',
	'TOB description' => 'tblYPPA.yppa_text'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT TOP {$iMaxRecs}
			b.BBBID,
			b.BusinessID,
			REPLACE(b.BusinessName,'&#39;',''''),
			b.PostalCode, 
			BBB.NickNameCity + ', ' + BBB.State,
			b.TOBID + ' ' + tblYPPA.yppa_text,
			a.DateClosed,
			a.NatureOfReview,
			a.CloseCode,
			a.AdReviewID
		FROM BusinessAdReview a WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on a.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		inner join Business b WITH (NOLOCK) on b.BBBID = a.BBBID and b.BusinessID = a.BusinessID
		left outer join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		WHERE
			a.DateClosed >= '{$iDateFrom}' and a.DateClosed <= '{$iDateTo}' and
			b.BusinessName LIKE '{$iBusinessName}%' and
			('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
			('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
			('{$iBBBID}' = '' or a.BBBID = '{$iBBBID}') and
			('{$iNotBBBID}' = '' or a.BBBID <> '{$iNotBBBID}') and
			('{$iCloseCode}' = '' or a.CloseCode = '{$iCloseCode}')
			";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) AbortREDReport($query);
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('#', '', '', 'right'),
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('Zip', $SortFields['Business postal code'], '', 'left'),
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('TOB', $SortFields['TOB code'], '', 'left'),
				array('Closed', $SortFields['Date closed'], '', 'left'),
				array('Nature', $SortFields['Nature'], '', 'left'),
				array('Code', $SortFields['Close code'], '', 'left'),
				array('Case ID', $SortFields['ID'], '', 'left'),
			)
		);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_REQUEST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;

		foreach ($rs as $k => $fields) {
			$xcount++;

			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;

			// business zip
			$business_zip = $fields[3];
			if (substr($fields[3],5,1) == '-') {
				$business_zip = substr($fields[3],0,5);
			}

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[1]}>{$fields[2]}</a>",
					$business_zip,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[4]) . "</a>",
					$fields[5],
					FormatDate($fields[6]),
					$fields[7],
					$fields[8],
					$fields[9]
				),
				'sized'
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