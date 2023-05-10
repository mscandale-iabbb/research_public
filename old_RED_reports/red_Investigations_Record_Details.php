<?php

/*
 * 11/03/14 MJS - added validation for MaxRecs, changed die() to AbortREDReport()
 * 08/25/16 MJS - align column headers
 * 11/02/17 MJS - suppressed non-reportable businesses
 * 11/15/17 MJS - added option for NAICS
 * 11/17/17 MJS - added reportable businesses back in but made in gray
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iBusinessName = NoApost($_REQUEST['iBusinessName']);
$iNAICS = NoApost($_POST['iNAICS']);
$iTOB = NoApost($_REQUEST['iTOB']);
$iBBBID = Numeric2($_REQUEST['iBBBID']);
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
$SortFields = array(
	'Business name' => 'b.BusinessName',
	'Business postal code' => 'b.PostalCode',
	'BBB city' => 'NicknameCity',
	'Date of investigation' => 'i.DateOfInvestigation',
	'Nature' => 'i.NatureOfInvestigation',
	'TOB description' => 'tblYPPA.yppa_text',
	'Reportable' => 'Reportable',
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
			i.DateOfInvestigation,
			i.NatureOfInvestigation,
			tblYPPA.yppa_text,
			case when b.IsReportable = '1' then 'Yes' else 'No' end as Reportable
		FROM BusinessInvestigation i WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on i.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
		inner join Business b WITH (NOLOCK) on b.BBBID = i.BBBID and b.BusinessID = i.BusinessID
		left outer join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		WHERE
			i.DateOfInvestigation >= '{$iDateFrom}' and
			i.DateOfInvestigation <= '{$iDateTo}' and
			b.BusinessName LIKE '{$iBusinessName}%' and
			('{$iNAICS}' = '' or substring(cast(tblYPPA.naics_code as varchar(6)),1,2) = '{$iNAICS}') and
			('{$iTOB}' = '' or tblYPPA.yppa_text like '%{$iTOB}%') and
			('{$iBBBID}' = '' or i.BBBID = '{$iBBBID}')
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
				array('Date', $SortFields['Date of investigation'], '', 'left'),
				array('Nature', $SortFields['Nature'], '', 'left'),
				array('TOB', $SortFields['TOB description'], '', 'left'),
				array('Reportable', $SortFields['Reportable'], '', 'left')
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

			if ($fields[8] == 'Yes') {
				$class = "";
			}
			else {
				$class = "darkgrayback";
			}
			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID={$fields[1]}>{$fields[2]}</a>",
					$business_zip,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . AddApost($fields[4]) . "</a>",
					FormatDate($fields[5]),
					$fields[6],
					$fields[7],
					$fields[8]
				),
				'',
				$class
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