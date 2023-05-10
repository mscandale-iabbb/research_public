<?php

/*
 * 11/30/16 MJS - new file
 * 07/26/18 MJS - added waived status
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


$oTotalMaxPoints = 70;

$color['Green'] = "#80FF80";
$color['Yellow'] = "#FFFF80";
$color['Red'] = "#FF8080";
$color['Gray'] = "#CCCCCC";
$color['White'] = "#FFFFFF";
$color[''] = "#FFFFFF";

function GetStandardsArray() {
	global $conn;
	global $iYear;

	$query = "
		SELECT StandardNumber,
			StandardSection,
			MaxPoints
		FROM EVAL_tblBBBStandardMaster WITH (NOLOCK) WHERE
			EvaluationDate = '1/1/{$iYear}' AND
			(BestPractice = '0' or BestPractice is NULL)
		ORDER BY SUBSTRING(CAST(StandardNumber as varchar(4)),1,1), StandardNumber
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	foreach ($rs as $k => $fields) {
		$oStandardNumber = $fields[0];
		$oStandardSection = $fields[1];
		$oMaxPoints = $fields[2];
		if (! $oStandardSection) $oStandardSection = 'Planning';
		$standards[] = [
			'oStandardNumber' => $oStandardNumber,
			'oStandardSection' => $oStandardSection,
			'oMaxPoints' => $oMaxPoints
		];
	}
	return $standards;
}

$iYear = Numeric2($_POST['iYear']);
if (! $iYear && $EvaluationDate && $EvaluationDate != '1/1/') $iYear = GetYear($EvaluationDate) + 1;
else $iYear = ValidYear( Numeric2( GetInput('iYear',date('Y') ) ) );
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$input_form = new input_form($conn);
$input_form->AddTextField('iYear', 'Evaluation round year', $iYear, "width:50px;", '', 'year');
$SortFields = array(
	'BBB city' => 'BBB.NicknameCity',
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

	$standards = GetStandardsArray();

	$query = "
		SELECT
			BBB.NickNameCity + ', ' + BBB.State,
			EvaluationDate,
			EVAL_tblBBBEvaluation.BBBID
		FROM EVAL_tblBBBEvaluation WITH (NOLOCK)
		RIGHT OUTER JOIN BBB WITH (NOLOCK) ON EVAL_tblBBBEvaluation.BBBID = BBB.BBBIDFULL
		WHERE
			BBB.BBBBranchID = 0 AND IsActive = 1 AND
			EvaluationDate = '1/1/{$iYear}'
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

		$header_row = array();
		$count = 0;
		$header_row[$count] = array('BBB City', $SortFields['BBB city'], '', 'left');
		foreach ($standards as $k => $standards_fields) {
			$count++;
			$header_row[$count] = array(substr($standards_fields['oStandardSection'],0,4) . ' ' .
				FormatStandardNumber($standards_fields['oStandardNumber']), '', '', 'left');
		}
		$report->WriteHeaderRow($header_row);
		foreach ($rs as $k => $fields) {
			$oNickNameCity = AddApost($fields[0]);
			$oEvaluationDate = FormatDate($fields[1]);
			$oBBBID = $fields[2];
			$oTotalPoints = 0;

			$row = array();
			$count = 0;
			$row[$count] = $oNickNameCity;
			foreach ($standards as $k => $standards_fields) {
				$count++;

				$oPoints = 0;
				$subquery3 = "
					SELECT SUM(Points) FROM EVAL_tblBBBStandardMet WITH (NOLOCK) WHERE
						BBBID = '{$oBBBID}' AND EvaluationDate = '{$oEvaluationDate}' AND
						StandardNumber = '{$standards_fields['oStandardNumber']}'
					";
				$rs3 = $conn->execute($subquery3);
				$rs3->MoveFirst();
				while (! $rs3->EOF) {
					$oPoints = $rs3->fields[0];
					$rs3->MoveNext();
				}
				if (! $oPoints) $oPoints = 0;
				$oTotalPoints += abs(intval($oPoints));

				$oMetStatus = '';
				$oBAP = '';
				$subquery4 = "
					SELECT MetStatus, BAP
					FROM EVAL_tblBBBStandardMet WITH (NOLOCK) WHERE
						BBBID = '{$oBBBID}' AND EvaluationDate = '{$oEvaluationDate}' AND
						StandardNumber = '{$standards_fields['oStandardNumber']}'
					";
				$rs4 = $conn->execute($subquery4);
				$rs4->MoveFirst();
				while (! $rs4->EOF) {
					$oMetStatus = $rs4->fields[0];
					$oBAP = $rs4->fields[1];
					$rs4->MoveNext();
				}
				if (! trim($oMetStatus)) $oMetStatus = 'White';
				if (strlen($oBAP) > 1) $oPoints = substr($oBAP,0,1);

				$oStatus = "<span style='background-color:{$color[$oMetStatus]}; padding:2px;'>";
				if ($oPoints == "1") $oStatus .= "x";  //"&#8730;"
				else if ($oPoints == -1) $oStatus .= "w";  //
				else if ($oPoints == 0) $oStatus .= "-";  //&#151;"
				$oStatus .= "</span>";

				$row[$count] = $oStatus;
			}
			$count++;
			$row[$count] = $oTotalPoints;
			$count++;
			$row[$count] = $oTotalMaxPoints;

			$report->WriteReportRow($row);
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>