<?php

/*
 * 11/16/17 MJS - new file
 * 11/20/17 MJS - refactored for CBBBDuesRate table
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);
$page->CheckCouncilOnly($BBBID);

$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y') - 2) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y') - 2) ) );
$iSortBy = $_POST['iSortBy'];
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iYearFrom', 'Year range', $iYearFrom, "width:50px;", '', 'number min=2013');
$input_form->AddTextField('iYearTo', '&nbsp; to &nbsp;', $iYearTo, "width:50px;", 'sameline', 'number min=2013');
$input_form->AddNote('This report only covers 2013 and later data.');
$SortFields = array(
	'BBB city' => 'NicknameCity',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			BBB.BBBID,
			BBB.NickNameCity + ', ' + BBB.State
		";
	for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
		$query .= "
			,
			rate{$i}.DuesRate
		";
	}
	for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
		$query .= "
			,
			(f{$i}.BBBAdjustedRevenue + f{$i}.FoundationAdjustedRevenue),
			cast(f{$i}.BBBAdjustedRevenue + f{$i}.FoundationAdjustedRevenue as decimal(14,2)) * rate{$i}.DuesRate,
			(cast(f{$i}.BBBAdjustedRevenue + f{$i}.FoundationAdjustedRevenue as decimal(14,2)) * rate{$i}.DuesRate) / 12
		";
	}
	$query .= "
		FROM BBB WITH (NOLOCK)
		";
	for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
		$query .= " LEFT OUTER JOIN BBBRevenueForm f{$i} WITH (NOLOCK) ON f{$i}.BBBID = BBB.BBBID AND f{$i}.[Year] = '{$i}' ";
		$query .= " INNER JOIN CBBBDuesRate rate{$i} WITH (NOLOCK) ON rate{$i}.DuesYear = '{$i}' ";
	}
	$query .= "
		WHERE
			BBB.BBBBranchID = '0' and BBB.BBBID != '2000' and
			(BBB.IsActive = '1' /*or f.BBBID is not null*/)
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
		$out_headers = array();
		$out_headers[] = array('BBB City', $SortFields['BBB city'], '', 'left');
		for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
			$out_headers[] = array("Rate {$i}", '', '', 'right');
		}
		for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
			$out_headers[] = array("Tot Adj Rev {$i}", '', '', 'right');
		}
		for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
			$out_headers[] = array("Tot Dues {$i}", '', '', 'right');
		}
		for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
			$out_headers[] = array("Month Dues {$i}", '', '', 'right');
		}
		$report->WriteHeaderRow($out_headers);
		foreach ($rs as $k => $fields) {
			$out_columns = array();
			$out_columns[] = "<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>" . AddApost($fields[1]) . "</a>";
			$j = 0;
			$year_range = $iYearTo - $iYearFrom + 1;
			for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
				$j++;
				$num = 1 + $j;
				$out_columns[] = FormatPercentage(floatval($fields[$num]), 2);
			}
			$j = 0;
			for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
				$j++;
				$num = 2 + $year_range + (($j - 1) * 3);
				$out_columns[] = intval($fields[$num]);
			}
			$j = 0;
			for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
				$j++;
				$num = 3 + $year_range + (($j - 1) * 3);
				$out_columns[] = intval($fields[$num]);
			}
			$j = 0;
			for ($i = $iYearTo; $i >= $iYearFrom; $i--) {
				$j++;
				$num = 4 + $year_range + (($j - 1) * 3);
				$out_columns[] = intval($fields[$num]);
			}
			$report->WriteReportRow($out_columns, '');
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>