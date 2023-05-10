<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 03/13/15 MJS - added 150 close code
 * 12/16/15 MJS - ensured Scam Tracker records won't appear
 * 08/25/16 MJS - align column headers
 * 11/09/17 MJS - changed sector to naics
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
$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iTOB = NoApost($_POST['iTOB']);
$iCountry = $_POST['iCountry'];
$iAB = NoApost($_REQUEST['iAB']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iBBBID', 'Processed by BBB', $iBBBID, $input_form->BuildBBBCitiesArray('all') );
$input_form->AddTextField('iTOB','TOB contains word/phrase',$iTOB);
$input_form->AddSelectField('iCountry', 'BBB country', $iCountry, $input_form->BuildBBBCountriesArray() );
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

function RunReportSection($conn, $query, $label, $output_type) {
	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	
	// tabular report n of 6
	if (count($rs) > 0) {
		$report = new report( $conn, count($rs) );
		$report->Open();
		if (count($rs) > 0) {
			$report->WriteHeaderRow(
				array (
					array('Sector', '', '', 'left'),
					array($label, '', '', 'right'),
				)
			);
	
			foreach ($rs as $k => $fields) {
				$report->WriteReportRow(
					array (
						$fields[0],
						$fields[1],
					)
				);
			}
		}
		$report->Close();
	}
	reset($rs);
	
	// pie chart n of 6
	if (count($rs) > 0 && $output_type == "") {
		foreach ($rs as $k => $fields) {
			$vals[] = $fields[1];
		}
	
		$totalval = array_sum($vals);
	
		$piechart = new piechart();
		$piechart->Open();
	
		$piechart->position = 0;
		foreach ($rs as $k => $fields) {
			$fields[0] = GetFirstWord($fields[0]);
			$newposition = $piechart->position + ($fields[1] / $totalval);
			if ( ($fields[1] / $totalval) >= 0.01) {
				$piechart->DrawSlice($piechart->position, $newposition,
						$fields[0] . ' ' . AddComma($fields[1])
				);
			}
			$piechart->position = $newposition;
		}
		$piechart->DrawTitle($label . ' by Industry Sector');
		$piechart->Close();
	}
	reset($rs);
	$vals = null;
	
	echo "</div>";
	
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

if ($_POST) {
	
	/* report 1 of 6 */

	$query = "
		declare @levels int;
		set @levels = 2;
		SELECT			
			n.naics_description,
			COUNT(*) as Complaints
		from BusinessComplaint c WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = c.BBBID and BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			c.ComplaintID not like 'scam%' and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			c.CloseCode IN ('110','111','112','120','121','122','150','200','300') and
			('{$iTOB}' = '' or y.yppa_text like '%{$iTOB}%') and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = '1') or
				('{$iAB}' = '0' and (b.IsBBBAccredited = '0' or b.IsBBBAccredited is null))
			) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
		order by Complaints desc
		";

	RunReportSection($conn, $query, 'Complaints', $output_type);

	/* report 2 of 6 */

	$query = "
		declare @levels int;
		set @levels = 2;
		SELECT
			n.naics_description,
			sum(CountTotal) as Inquiries
		from BusinessInquiry i WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = i.BBBID and b.BusinessID = i.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = i.BBBID and BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			i.DateOfInquiry >= '{$iDateFrom}' and i.DateOfInquiry <= '{$iDateTo}' and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			('{$iTOB}' = '' or y.yppa_text like '%{$iTOB}%') and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = '1') or
				('{$iAB}' = '0' and (b.IsBBBAccredited = '0' or b.IsBBBAccredited is null))
			) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
		order by Inquiries desc
		";

	RunReportSection($conn, $query, 'Inquiries', $output_type);
	
	/* report 3 of 6 */

	$query = "
		declare @levels int;
		set @levels = 2;
		SELECT
			n.naics_description,
			count(*) as AdReviews
		from BusinessAdReview a WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = a.BBBID and b.BusinessID = a.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = a.BBBID and BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			a.DateClosed >= '{$iDateFrom}' and a.DateClosed <= '{$iDateTo}' and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			('{$iTOB}' = '' or y.yppa_text like '%{$iTOB}%') and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = '1') or
				('{$iAB}' = '0' and (b.IsBBBAccredited = '0' or b.IsBBBAccredited is null))
			) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
		order by AdReviews desc
		";

	RunReportSection($conn, $query, 'Ad Reviews', $output_type);

	/* report 4 of 6 */
	
	$query = "
		declare @levels int;
		set @levels = 2;
		SELECT
			n.naics_description,
			count(*) as Investigations
		from BusinessInvestigation i WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = i.BBBID and b.BusinessID = i.BusinessID
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = i.BBBID and BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			i.DateOfInvestigation >= '{$iDateFrom}' and i.DateOfInvestigation <= '{$iDateTo}' and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			('{$iTOB}' = '' or y.yppa_text like '%{$iTOB}%') and
			(
				('{$iAB}' = '') or
				('{$iAB}' = '1' and b.IsBBBAccredited = '1') or
				('{$iAB}' = '0' and (b.IsBBBAccredited = '0' or b.IsBBBAccredited is null))
			) and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
		order by Investigations desc
		";

	RunReportSection($conn, $query, 'Investigations', $output_type);

	/* report 5 of 6 */

	$query = "
		declare @levels int;
		set @levels = 2;
		select
			n.naics_description,
			COUNT(*) as ABs
		from Business b WITH (NOLOCK)
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			b.IsBBBAccredited = '1' and b.IsReportable = '1' and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			('{$iTOB}' = '' or y.yppa_text like '%{$iTOB}%') and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
		order by ABs desc
		";

	RunReportSection($conn, $query, 'ABs', $output_type);

	/* report 6 of 6 */

	$query = "
		declare @levels int;
		set @levels = 2;
		SELECT
			n.naics_description,
			count(distinct b.BusinessID) as NewABs
		from Business b WITH (NOLOCK)
		inner join BusinessProgramParticipation p WITH (NOLOCK) on
			p.BBBID = b.BBBID AND p.BusinessID = b.BusinessID and
			(p.BBBProgram = 'Membership' or p.BBBProgram = 'BBB Accredited Business')
		inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
		inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID and BBB.BBBBranchID = '0' AND BBB.IsActive = '1'
		where
			p.DateFrom >= '{$iDateFrom}' and p.DateFrom <= '{$iDateTo}' and
			b.TOBID != '99999-000' and
			LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
			('{$iBBBID}' = '' or BBB.BBBID = '{$iBBBID}') and
			('{$iTOB}' = '' or y.yppa_text like '%{$iTOB}%') and
			('{$iCountry}' = '' or BBB.Country = '{$iCountry}')
		group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
		order by NewABs desc
		";
	
	RunReportSection($conn, $query, 'New ABs', $output_type);

}

$page->write_pagebottom();

?>