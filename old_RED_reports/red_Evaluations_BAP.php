<?php

/*
 * 04/07/16 MJS - new file
 * 04/08/16 MJS - added column for ones with Gray status
 * 08/26/16 MJS - align column headers
 * 12/26/17 MJS - refactored for section table
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
include 'links.php';
$page->write_header2();
include 'red_tabs.php';
$page->write_tabs($tabs);
$page->CheckCouncilOnly($BBBID);


$iYear = Numeric2($_POST['iYear']);
if (! $iYear && $EvaluationDate && $EvaluationDate != '1/1/') $iYear = GetYear($EvaluationDate) + 1;
else $iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') ) ) );
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Evaluation round year', $iYear, "width:50px;", '', 'year');
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$iYear--;

	if ($EvaluationDate == '' || $EvaluationDate == '1/1/') $EvaluationDate = "1/1/" . $iYear;
	$iEvaluationDate = $_REQUEST['iEvaluationDate'];
	if (! $iEvaluationDate) $iEvaluationDate = '1/1/' . $iYear;
	if ($iEvaluationDate) $EvaluationDate = $iEvaluationDate;
	session_register('EvaluationDate');
	$_SESSION['EvaluationDate'] = $EvaluationDate;

	$query = "
		create table #result (
			BBBID varchar(4), Yellow varchar(1000), Red varchar(1000), Green varchar(1000), Gray varchar(1000)
		);

		declare @BBBID as varchar(4);
		declare @StandardSection varchar(30);
		declare @StandardNumber smallint;
		declare @BAP varchar(20);
		declare @MetStatus varchar(10);
		declare @Yellow varchar(1000);
		declare @Red varchar(1000);
		declare @Green varchar(1000);
		declare @Gray varchar(1000);
		declare @temp varchar(max);
		
		declare c cursor for
			SELECT
				BBB.BBBID
			FROM EVAL_tblBBBEvaluation e WITH (NOLOCK)
			RIGHT OUTER JOIN BBB WITH (NOLOCK) ON e.BBBID = BBB.BBBIDFULL
			WHERE
				BBB.BBBBranchID = 0 AND IsActive = 1 AND EvaluationDate = '{$EvaluationDate}' and
				(
					SELECT COUNT(*) FROM EVAL_tblBBBStandardMet m WITH (NOLOCK) WHERE
						m.BBBID = BBB.BBBID AND m.EvaluationDate = e.EvaluationDate AND
						BAP > ''
				) >= 1
			;
		open c;
		fetch next from c into @BBBID;
		while @@fetch_status = 0
		begin
			set @Yellow = '';
			set @Red = '';
			set @Green = '';
			set @Gray = '';
			declare c2 cursor for
				SELECT
					s.StandardSection, s.StandardNumber, m.BAP, m.MetStatus /*m.BAPFollowUp*/
				FROM EVAL_tblBBBStandardMaster s WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBSection se WITH (NOLOCK) ON
					se.SectionName = s.StandardSection and se.[Year] = YEAR(s.EvaluationDate)
				INNER JOIN EVAL_tblBBBStandardMet m WITH (NOLOCK) ON
					m.StandardNumber = s.StandardNumber AND m.EvaluationDate = s.EvaluationDate
				WHERE
					m.BBBID = @BBBID AND m.EvaluationDate = '{$EvaluationDate}' AND m.BAP > ''
				ORDER BY m.MetStatus DESC, se.SectionNumber, SUBSTRING(CAST(s.StandardNumber as varchar(4)),2,1),
					CAST( SUBSTRING(CAST(s.StandardNumber as varchar(4)),3,2) as int);
			open c2;
			fetch next from c2 into @StandardSection, @StandardNumber, @BAP, @MetStatus;
			while @@fetch_status = 0
			begin
				set @temp =
					LTRIM(@StandardSection) + ' ' +
					cast(@StandardNumber as varchar(10)) + ' ' +
					LTRIM(substring(@BAP,1,1)) + ' ' +
					'<br/>';
				if @MetStatus = 'Yellow' set @Yellow = @Yellow + @temp;
				else if @MetStatus = 'Red' set @Red = @Red + @temp;
				else if @MetStatus = 'Green' set @Green = @Green + @temp;
				else if @MetStatus = 'Gray' set @Gray = @Gray + @temp;
				fetch next from c2 into @StandardSection, @StandardNumber, @BAP, @MetStatus;
			end
			close c2;
			deallocate c2;

			if @Yellow > '' set @Yellow = '<span style=''background-color:yellow; color:black; padding:2px;''>' + @Yellow + '</span>';
			if @Red > '' set @Red = '<span style=''background-color:red; color:black; padding:2px;''>' + @Red + '</span>';
			if @Green > '' set @Green = '<span style=''background-color:green; color:black; padding:2px;''>' + @Green + '</span>';
			if @Gray > '' set @Gray = '<span style=''background-color:gray; color:black; padding:2px;''>' + @Gray + '</span>';
			insert into #result values (@BBBID, @Yellow, @Red, @Green, @Gray);

			fetch next from c into @BBBID;
		end
		close c;
		deallocate c;
		
		select
			BBB.NicknameCity + ', ' + BBB.State,
			#result.Yellow,
			#result.Red,
			#result.Green,
			#result.Gray
		from #result
		inner join BBB WITH (NOLOCK) on BBB.BBBID = #result.BBBID and BBB.BBBBranchID = 0
		order by BBB.NicknameCity, BBB.State;
		
		drop table #result;
		";

	$rsraw = $conn->execute($query);
	if (! $rsraw) AbortREDReport($query);
	$rs = $rsraw->GetArray();
	$report = new report( $conn, count($rs) );
	$report->Open();
	if (count($rs) > 0) {
		$report->WriteHeaderRow(
			array (
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Requirements that Need Follow Up on Metrics', '', '', 'left'),
				array('Requirements with BAPs Requested', '', '', 'left'),
				array(''),
				array(''),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					/*"<a href={$link}>" . AddApost($fields[0]) . "</a>",*/
					$fields[0],
					$fields[1],
					$fields[2],
					$fields[3],
					$fields[4],
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