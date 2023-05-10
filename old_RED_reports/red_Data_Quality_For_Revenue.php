<?php

/*
 * 02/13/15 MJS - new proposed report
 * 09/02/15 MJS - fixed http
 * 08/25/16 MJS - aligned column headers
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);

$userBBBID = $BBBID;

$iBBBID = Numeric2($_REQUEST['iBBBID']);
if (! $_POST && $userBBBID != '2000') $iBBBID = $userBBBID;
else if (! $_POST && $userBBBID == '2000') $iBBBID = '1066';
$iMaxRecs = Numeric2($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_REQUEST['iShowSource'];

$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$SortFields = array(
	'Business name' => 'BusinessName',
	'ID' => 'BusinessID',
	'BBB city' => 'BBBCity,BusinessName',
	'BBB Revenue' => 'Revenue',
	'D&B Revenue' => 'Revenue2',
	/*
	'BBB Employees' => 'Employees',
	'D&B Employees' => 'Employees2',
	*/
	'TOB' => 'TOB',
	'Type' => 'Type',
	'Rating' => 'BBBRatingSortOrder,BusinessName',
	'AB' => 'AB,BusinessName',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT TOP {$iMaxRecs}
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State as BBBCity,
			b.BusinessID as BusinessID,
			REPLACE(b.BusinessName,'&#39;','''') as BusinessName,
			b.BOConlyGrossRevenue as Revenue,
			d.SALES as Revenue2,
			/*b.NumberOfEmployees as Employees2,*/
			/*d.EMPLOYEES_HERE as Employees,*/
			y.yppa_text as TOB,
			b.ReportType as Type,
			b.BBBRatingGrade,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.Website,
			Case
				when d.SALES < 1000 then 'much larger'
				when b.BOConlyGrossRevenue < 1000 then 'much smaller'
				when b.BOConlyGrossRevenue > d.SALES then cast(cast(b.BOConlyGrossRevenue / d.SALES as int) as varchar(8)) + 'x larger'
				else cast(cast(d.SALES / b.BOConlyGrossRevenue as int) as varchar(8)) + 'x smaller'
				end as Difference
		from Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		inner JOIN DandB d WITH (NOLOCK) ON d.BBBID = b.BBBID and d.BusinessID = b.BusinessID
		left outer join tblYPPA y WITH (NOLOCK) on y.yppa_code = b.TOBID
		where
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.IsReportable = 1 and b.PublishToCIBR = 1 and
			b.BOConlyGrossRevenue >= 1 and d.SALES >= 1 and
			(
				(
					(b.BOConlyGrossRevenue > (d.SALES * 5) or b.BOConlyGrossRevenue < (d.SALES / 5)) and
					ABS(b.BOConlyGrossRevenue - d.SALES) > 499000
				)
				or
				(
					(b.BOConlyGrossRevenue > (d.SALES * 4) or b.BOConlyGrossRevenue < (d.SALES / 4)) and
					ABS(b.BOConlyGrossRevenue - d.SALES) > 999000
				)
				or
				(
					(b.BOConlyGrossRevenue > (d.SALES * 3) or b.BOConlyGrossRevenue < (d.SALES / 3)) and
					ABS(b.BOConlyGrossRevenue - d.SALES) > 8999000
				)
				or
				(
					(b.BOConlyGrossRevenue > (d.SALES * 2) or b.BOConlyGrossRevenue < (d.SALES / 2)) and
					ABS(b.BOConlyGrossRevenue - d.SALES) > 14999000
				)
			)
		";
	if ($iSortBy > '') {
		$query .= " ORDER BY " . $iSortBy;
	}

	if ($_POST['use_saved'] == '1') {
		$rs = $_SESSION['rs'];
	}
	else {
		$rsraw = $conn->execute($query);
		if (! $rsraw) die("Routine ended unexpectedly - please try again");
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('#', '', '', 'right'),
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('ID', $SortFields['ID'], '', 'left'),
				array('Name', $SortFields['Business name'], '', 'left'),
				array('BBB Revenue', $SortFields['BBB Revenue'], '', 'right'),
				array('D&B Revenue', $SortFields['D&B Revenue'], '', 'right'),
				array('Difference', '', '', 'left'),
				/*array('BBB Employees', $SortFields['Employees2'], '', 'right'),*/
				/*array('D&B Employees', $SortFields['Employees'], '', 'right'),*/
				array('Type of Business', $SortFields['TOB'], '', 'left'),
				array('Rpt Type', $SortFields['Type'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Website', '', '', 'left'),
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
			
			if ($fields[10]) {
				$http = "";
				/*
				if (substr($fields[10],0,4) == 'http') $http = "http://";
				else $http = "";
				*/
				$website = "<a target=_new href=\"" . $http . $fields[10] . "\">Open</a>";
			}
			else $website = '';

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . NoApost($fields[1]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[2] .  ">" . NoApost($fields[2]) . "</a>",
					AddApost($fields[3]),
					intval($fields[4]),
					intval($fields[5]),
					$fields[11],
					$fields[6],
					$fields[7],
					$fields[8],
					$fields[9],
					$website,
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}

	
$page->write_pagebottom();

?>