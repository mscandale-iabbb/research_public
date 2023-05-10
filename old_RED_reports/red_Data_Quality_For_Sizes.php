<?php

/*
 * 10/27/14 MJS - added column for "Why?"
 * 10/28/14 MJS - completely re-wrote to base on Revenue
 * 02/13/15 MJS - this report was removed from RED menu
 * 02/03/17 MJS - changed calls to define links and tabs
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
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
		$rsraw = $conn->execute("$query");
		if (! $rsraw) die("Routine ended unexpectedly - please try again");
		$rs = $rsraw->GetArray();
		$_SESSION['rs'] = $rs;
	}

	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('#'),
				array('BBB', $SortFields['BBB city']),
				array('ID', $SortFields['ID']),
				array('Name', $SortFields['Business name']),
				array('BBB Revenue', $SortFields['BBB Revenue']),
				array('D&B Revenue', $SortFields['D&B Revenue']),
				array('Difference', ''),
				/*array('BBB Employees', $SortFields['Employees2']),*/
				/*array('D&B Employees', $SortFields['Employees']),*/
				array('Type of Business', $SortFields['TOB']),
				array('Rpt Type', $SortFields['Type']),
				array('Rating', $SortFields['Rating']),
				array('AB', $SortFields['AB']),
				array('Website', ''),
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
			
			if ($fields[10]) $website = "<a target=_new href=http://{$fields[10]}>Open</a>";
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
					/*"<a target=detail href=red_Size_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[2] . ">Why?</a>",*/
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