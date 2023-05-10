<?php

/*
 * 07/12/18 MJS - new file
 * 10/26/18 MJS - added column % unreviewed
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);


$iMonthFrom = ValidMonth( Numeric2( GetInput('iMonthFrom',1) ) );
$iYearFrom = ValidYear( Numeric2( GetInput('iYearFrom',date('Y')) ) );
$iMonthTo = ValidMonth( Numeric2( GetInput('iMonthTo',date('n') - 1) ) );
$iYearTo = ValidYear( Numeric2( GetInput('iYearTo',date('Y')) ) );
$iBBBID = NoApost($_POST['iBBBID']);
if ($iMonthTo == 0) {
	$iMonthTo = 12;
	$iYearTo--;
	$iMonthFrom = $iMonthTo;
	$iYearFrom = $iYearTo;
}
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = "UnreviewedMatches DESC";
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iMonthFrom', 'Months', $iMonthFrom, "width:35px;", '', 'month');
$input_form->AddTextField('iYearFrom', ' / ', $iYearFrom, "width:50px;", 'sameline', 'year');
$input_form->AddTextField('iMonthTo', '&nbsp; to &nbsp;', $iMonthTo, "width:35px;", 'sameline', 'month');
$input_form->AddTextField('iYearTo', ' / ', $iYearTo, "width:50px;", 'sameline', 'year');
$input_form->AddMultipleSelectField('iBBBID', 'BBBs', $iBBBID,
	$input_form->BuildBBBCitiesArray('all'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'BBB ID' => 'BBB.BBBID',
	'UnreviewedMatches' => 'UnreviewedMatches',
	'UnreviewedNonFNRMatches' => 'UnreviewedNonFNRMatches',
	'TotalMatches' => 'TotalMatches',
	'FNRMatches' => 'FNRMatches',
	'NonFNRMatches' => 'NonFNRMatches',
	'UnreviewedFNRMatches' => 'UnreviewedFNRMatches',
	'ProportionUnreviewed' => 'ProportionUnreviewed'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		declare @datefrom date;
		set @datefrom = CONVERT(datetime, '{$iMonthFrom}' + '/1/' + '{$iYearFrom}');

		declare @dateto date;
		declare @tomonth int;
		declare @toyear int;
		set @tomonth = '{$iMonthTo}';
		set @toyear = '{$iYearTo}';
		if @tomonth = 12 BEGIN
			set @tomonth = 1;
			set @toyear = @toyear + 1;
		END
		else set @tomonth = @tomonth + 1;
		set @dateto = CONVERT(datetime, cast(@tomonth as varchar(2)) + '/1/' + cast(@toyear as varchar(4)) ) - 1;

		SELECT
			BBB.BBBID,
			BBB.NicknameCity,
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and ch.ComplaintID is null and b.BBBRatingGrade in ('F','NR') and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as UnreviewedFNRMatches,
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and ch.ComplaintID is null and b.BBBRatingGrade NOT in ('F','NR') and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as UnreviewedNonFNRMatches,
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and ch.ComplaintID is null and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as UnreviewedMatches,
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and b.BBBRatingGrade in ('F','NR') and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as FNRMatches,
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and b.BBBRatingGrade not in ('F','NR') and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as NonFNRMatches,
			(
				SELECT
					count(*)
				FROM ScamBusinessMatch m WITH (NOLOCK)
				INNER JOIN BusinessComplaint c WITH (NOLOCK) ON c.BBBID = m.BBBID and c.ComplaintID = m.ComplaintID
				INNER JOIN Business b WITH (NOLOCK) ON b.BBBID = m.MatchBBBID and b.BusinessID = m.MatchBusinessID
				INNER JOIN BBB BBB2 WITH (NOLOCK) on BBB2.BBBID = b.BBBID and BBB2.BBBBranchID = '0'
				LEFT OUTER JOIN BusinessComplaintChecked ch WITH (NOLOCK) ON ch.BBBID = c.BBBID and ch.ComplaintID = c.ComplaintID
				left outer join CORE.dbo.datOrg o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				WHERE
					BBB2.BBBID = BBB.BBBID and
					c.DateClosed >= @datefrom and c.DateClosed <= @dateto and
					c.ComplaintID like 'scam%' and
					(OutOfBusinessTypeId is null or OutOfBusinessTypeId = '') and
					(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
					b.IsReportable = '1'
			) as TotalMatches
		FROM BBB WITH (NOLOCK)
		WHERE
			BBB.BBBBranchID = '0' and BBB.IsActive = '1' and
			('{$iBBBID}' = '' or BBB.BBBID IN ('" . str_replace(",", "','", $iBBBID) . "'))
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
				array('#', '', '', 'right'),
				array('BBB ID', $SortFields['BBB ID'], '', 'left'),
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Unreviewed F/NR Matches', $SortFields['UnreviewedFNRMatches'], '', 'right'),
				array('Unreviewed Non-F/NR Matches', $SortFields['UnreviewedNonFNRMatches'], '', 'right'),
				array('Total Unreviewed Matches', $SortFields['UnreviewedMatches'], '', 'right'),				
				array('F/NR Matches', $SortFields['FNRMatches'], '', 'right'),
				array('Non-F/NR Matches', $SortFields['NonFNRMatches'], '', 'right'),
				array('Total Matches', $SortFields['TotalMatches'], '', 'right'),
				array('Proportion Unreviewed', '', '', 'right'),
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			if ($fields[0] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					$xcount,
					$fields[0],
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						"><span class='{$class}'>" . AddApost($fields[1]) . "</span></a>",
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					$fields[6],
					$fields[7],
					FormatPercentage($fields[4] / $fields[7], 0),
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