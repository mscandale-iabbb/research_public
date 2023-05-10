<?php

/*
 * 10/09/17 MJS - new file
 * 11/07/17 MJS - pulled from different field
 * 11/13/17 MJS - added option for purchase price
 * 11/13/17 MJS - added link to graph
 * 11/15/17 MJS - fixed column header, made new default
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
$iField = NoApost($_POST['iField']);
if (! $_POST) {
	$iField = "PurchasePrice";
}
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iState = NoApost($_POST['iState']);
$iCstate = NoApost($_POST['iCstate']);
$iCountry = $_POST['iCountry'];
$iMaxRecs = Numeric2($_REQUEST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = 'Amount DESC';
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddRadio('iField', 'Field', $iField, array('Purchase Price' => 'PurchasePrice', 'Amount Disputed' => 'AmountDisputed') );
$input_form->AddDateField('iDateFrom','Complaints closed from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddMultipleSelectField('iState', 'Business state', $iState,
	$input_form->BuildStatesArray(''), '', '', '', 'width:350px');
$input_form->AddMultipleSelectField('iCstate', 'Consumer state', $iCstate,
	$input_form->BuildStatesArray(''), '', '', '', 'width:350px');
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$SortFields = array(
	'Industry code' => 'n.naics_code',
	'Industry description' => 'n.naics_description',
	'Complaints' => 'xcount',
	'Median amount' => 'Amount',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @levels int;
		set @levels = 6;
		declare @threshold int;
		set @threshold = 200;
		if '{$iBBBID}' > '' or '{$iState}' > '' or '{$iCstate}' > '' set @threshold = 20;

		create table #Temp (
			Amount		decimal(18,2),
			xrownum		int,
			xcount		int,
			naics_code	int
		);
		
		insert into #Temp
		select
			cast(replace(c.{$iField},',','') as decimal(18,2)),
			ROW_NUMBER() over (partition by substring(cast(y.naics_code as varchar(6)),1,@levels)
				order by substring(cast(y.naics_code as varchar(6)),1,@levels), cast(replace(c.{$iField},',','') as decimal(18,2))),
			COUNT(*) over (partition by substring(cast(y.naics_code as varchar(6)),1,@levels)
				order by substring(cast(y.naics_code as varchar(6)),1,@levels)),
			substring(cast(y.naics_code as varchar(6)),1,@levels)
		from Business b WITH (NOLOCK)
		inner join BusinessComplaint c WITH (NOLOCK) ON
			c.BBBID = b.BBBID and c.BusinessID = b.BusinessID and
			c.CloseCode != '400' and
			c.ComplaintID not like 'scam%'
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = '0'
		where
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			('{$iBBBID}' = '' or c.BBBID = '{$iBBBID}') and
			ISNUMERIC(replace(c.{$iField},',','')) = 1 and cast(replace(c.{$iField},',','') as decimal(18,2)) > '0' and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iState}' = '' or b.StateProvince IN ('" . str_replace(",", "','", $iState) . "')) and
			('{$iCstate}' = '' or c.ConsumerStateProvince IN ('" . str_replace(",", "','", $iCstate) . "')) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')

		select top {$iMaxRecs}
			n.naics_code,
			n.naics_description,
			#Temp.Amount as 'Median',
			#Temp.xcount as 'Count'
		from #Temp
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = #Temp.naics_code
		where
			#Temp.xrownum = ceiling(#Temp.xcount / 2.0) and
			#Temp.xcount >= @threshold
		";
	if ($iSortBy) {
		$query .= " ORDER BY {$iSortBy};";
	}
	$query .= " drop table #Temp ";

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
				array('Industry Code', $SortFields['Industry code'], '', 'left'),
				array('Industry Description', $SortFields['Industry description'], '', 'left'),
				array('Median ' . $iField, $SortFields['Median amount'], '', 'right'),
				array('Complaints', $SortFields['Complaints'], '', 'right'),
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

			$desc = "<a target=_new href='red_Industry_Values_Chart.php?" .
				"iField={$iField}&" .
				"iDateFrom={$iDateFrom}&" .
				"iDateTo={$iDateTo}&" .
				"iState={$iState}&" .
				"iCstate={$iCstate}&" .
				"iCountry={$iCountry}&" .
				"iNAICS={$fields[0]}&" .
				"iMedian={$fields[2]}&" .
				"iCount={$fields[3]}&" .
				"iTitle={$fields[1]}&" .
				"iBBBID={$iBBBID}" .
				"'>" . AddApost($fields[1]) . "</a>";
			$report->WriteReportRow(
				array (
					$xcount,
					strval($fields[0]),
					$desc,
					round($fields[2], 0),
					$fields[3],
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