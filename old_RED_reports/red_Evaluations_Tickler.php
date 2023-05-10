<?php

/*
 * 04/01/16 MJS - new file
 * 04/04/16 MJS - changed 7 days to 14, changed sort order
 * 04/05/16 MJS - re-wrote query, added columns
 * 04/05/16 MJS - added detection of EvaluationDate, pass and receive parameters from Evaluations System
 * 04/06/16 MJS - fixed bug with EvaluationDate
 * 04/07/16 MJS - fixed bug with duplicates showing
 * 04/12/16 MJS - excluded standards that have already been marked as met/green
 * 04/13/16 MJS - excluded standards marked as gray
 * 08/26/16 MJS - align column headers
 * 11/16/16 MJS - changed link to evaluations
 * 12/26/17 MJS - refactored for section table
 * 05/07/19 MJS - added iabbb
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


$iDays = Numeric2($_POST['iDays']);
if (! $iDays) $iDays = 14;
$iYear = Numeric2($_POST['iYear']);
if (! $iYear && $EvaluationDate && $EvaluationDate != '1/1/') $iYear = GetYear($EvaluationDate) + 1;
else $iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') ) ) );
$iSortBy = NoApost($_POST['iSortBy']);
if (! $iSortBy) $iSortBy = "LastCBBBCommentDate DESC";
$iSentBy = NoApost($_POST['iSentBy']);
if (! $iSentBy) $iSentBy = 'all';
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddTextField('iDays', "Check for BBBs who haven't replied in", $iDays, "width:5%;", '', 'number');
$input_form->AddNote('days');
$input_form->AddTextField('iYear', 'Evaluation round year', $iYear, "width:50px;", '', 'year');
$input_form->AddRadio('iSentBy', 'Sent by', $iSentBy, array(
		'All' => 'all',
		'djohnson@council.bbb.org' => 'djohnson@council.bbb.org',
		'vlockett@council.bbb.org' => 'vlockett@council.bbb.org',
	)
);
$SortFields = array(
	'Last CBBB comment date' => 'LastCBBBCommentDate',
	'BBB city' => 'BBB.NicknameCity',
	'Last BBB comment date' => 'LastBBBCommentDate',
	'Standard' => 'c.StandardNumber',
	'Section' => 'se.SectionName',
	'Sent by' => 'c.SentBy',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
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

	$query = "SELECT
			BBB.NickNameCity + ', ' + BBB.State,
			c.DateSent as LastCBBBCommentDate,
			bc.DateSent as LastBBBCommentDate,
			BBB.BBBID,
			c.StandardNumber,
			se.SectionName,
			c.SentBy
		FROM BBB WITH (NOLOCK)
		INNER JOIN EVAL_tblBBBEvaluation e WITH (NOLOCK) ON
			e.BBBID = BBB.BBBID AND e.EvaluationDate = '{$EvaluationDate}'
		INNER JOIN EVAL_tblBBBReviewComment c WITH (NOLOCK) ON
			c.BBBID = e.BBBID AND c.EvaluationDate = e.EvaluationDate AND
			(c.SentBy like '%council%' or c.SentBy like '%@iabbb%') AND
			/* no later comments (meaning this is the last comment) for this standard */
			(
				select count(*) from EVAL_tblBBBReviewComment c2 WITH (NOLOCK) where
					c2.BBBID = c.BBBID and c2.EvaluationDate = c.EvaluationDate and
					c2.StandardNumber = c.StandardNumber and
					c2.CommentID > c.CommentID
			) = 0
		INNER JOIN EVAL_tblBBBStandardMaster st WITH (NOLOCK) ON
			st.EvaluationDate = c.EvaluationDate and st.StandardNumber = c.StandardNumber
		INNER JOIN EVAL_tblBBBSection se WITH (NOLOCK) ON se.SectionName = st.StandardSection and
			 se.[Year] = YEAR(st.EvaluationDate)
		LEFT OUTER JOIN EVAL_tblBBBReviewComment bc WITH (NOLOCK) ON
			bc.BBBID = e.BBBID AND bc.EvaluationDate = e.EvaluationDate AND
			bc.StandardNumber = c.StandardNumber and
			NOT bc.SentBy like '%council%' AND NOT bc.SentBy like '%@iabbb%' AND
			/* no later bbb comments (meaning this is the last bbb comment) for this standard */
			(
				select count(*) from EVAL_tblBBBReviewComment bc2 WITH (NOLOCK) where
					bc2.BBBID = bc.BBBID and bc2.EvaluationDate = bc.EvaluationDate and
					bc2.StandardNumber = bc.StandardNumber and
					NOT bc2.SentBy like '%council%' and NOT bc2.SentBy like '%@iabbb%' and
					bc2.CommentID > bc.CommentID
			) = 0
		LEFT OUTER JOIN EVAL_tblBBBStandardMet m WITH (NOLOCK) ON
			m.EvaluationDate = e.EvaluationDate and m.BBBID = BBB.BBBIDFull and
			m.StandardNumber = st.StandardNumber
		WHERE
			BBB.BBBBranchID = 0 AND (BBB.IsActive = 1 OR BBB.BBBID = '2000') and
			e.DateCompleted is null and
			(m.MetStatus is null or m.MetStatus in ('yellow','red')) and
			(bc.DateSent is null or bc.DateSent <= GETDATE() - {$iDays}) and
			(bc.DateSent is null or bc.DateSent < c.DateSent) and
			('{$iSentBy}' = 'all' or c.SentBy = '{$iSentBy}')
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
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Section', $SortFields['Section'], '', 'left'),
				array('Standard', $SortFields['Standard'], '', 'left'),
				array('Last CBBB Comment Date', $SortFields['Last CBBB comment date'], '', 'left'),
				array('Sent By', $SortFields['Sent by'], '', 'left'),
				array('Last BBB Comment Date', $SortFields['Last BBB comment date'], '', 'left')
			)
		);
		$xcount = 0;
		foreach ($rs as $k => $fields) {
			$xcount++;
			$link = "ev_edit.php?BBBID={$fields[3]}&EvaluationDate=1/1/{$iYear}&iSection={$fields[5]}#Standard-{$fields[4]}";
			$report->WriteReportRow(
				array (
					$xcount,
					"<a href={$link}>" . AddApost($fields[0]) . "</a>",
					$fields[5],
					FormatStandardNumber($fields[4]),
					FormatDate($fields[1]),
					$fields[6],
					FormatDate($fields[2]),
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