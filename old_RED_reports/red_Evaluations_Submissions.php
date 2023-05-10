<?php

/*
 * 12/01/16 MJS - new file
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

	$query = "
		SELECT
			EVAL_tblBBBEvaluation.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			case when EvaluationSubmitted = '1' then 'Yes' else 'No' end as EvaluationSubmitted,
			DateSubmitted,
			SubmittedBy
		FROM EVAL_tblBBBEvaluation WITH (NOLOCK)
		INNER JOIN BBB WITH (NOLOCK) ON
			BBB.BBBID = EVAL_tblBBBEvaluation.BBBID AND BBB.BBBBranchID = '0'
		WHERE
			EvaluationDate = '{$iEvaluationDate}' AND IsActive = 1
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
			array(
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Submitted', '', '', 'left'),
				array('When Submitted', '', '', 'left'),
				array('Blank Fields', '', '', 'right'),
			)
		);
		foreach ($rs as $k => $fields) {
			$oBBBID = $fields[0];
			$oNickNameCity = $fields[1];
			$oEvaluationSubmitted = $fields[2];
			$oDateSubmitted = FormatDate($fields[3]);
			$oSubmittedBy = $fields[4];
			if (! $oSubmittedBy) $oSubmittedBy = "Unknown";

			// Count how many missing fields

			$oNumberOfMissingFields = 0;
			$subquery1 = "
				SELECT
					st.StandardNumber, st.StandardDescription, se.SectionName
				FROM EVAL_tblBBBStandardMaster st WITH (NOLOCK)
				INNER JOIN EVAL_tblBBBSection se WITH (NOLOCK) ON
					se.SectionName = st.StandardSection and se.[Year] = YEAR(st.EvaluationDate)
				WHERE
					st.EvaluationDate = '{$iEvaluationDate}' and
					(st.BestPractice = 0 or st.BestPractice is null)
				ORDER BY se.SectionNumber,
				SUBSTRING(CAST(StandardNumber as varchar(4)),2,1), StandardNumber
			";
			$rs1 = $conn->execute($subquery1);
			$rs1->MoveFirst();
			while (! $rs1->EOF) {
				$oStandardNumber = $rs1->fields[0];
				$oStandardDescription = $rs1->fields[1];
				$oSectionName = $rs1->fields[2];
	
				$subquery2 = "
					SELECT
						f.FieldName, f.FieldDataType,
						v.FieldValueText, v.FieldValueDate, v.FieldValueNumber, v.FieldValueMoney,
						v.FieldValueYesNo, v.FieldValueDecimal, v.FieldValueFile,
						f.FieldNumber
					FROM EVAL_tblBBBStandardField f WITH (NOLOCK)
					LEFT OUTER JOIN EVAL_tblBBBStandardFieldValue v WITH (NOLOCK) ON
						v.BBBID = '{$oBBBID}' AND
						v.EvaluationDate = f.EvaluationDate AND
						v.StandardNumber = f.StandardNumber AND
						v.FieldNumber = f.FieldNumber
					WHERE
						f.EvaluationDate = '{$iEvaluationDate}' and
						f.StandardNumber = '{$oStandardNumber}' and
						(f.NotRequired = 0 or f.NotRequired is null) and
						(f.CBBBOnly is null or f.CBBBOnly = '0')
					ORDER BY f.FieldNumber
					";
				$rs2 = $conn->execute($subquery2);
				$rs2->MoveFirst();
				while (! $rs2->EOF) {
					$oFieldName = $rs2->fields[0];
					$oFieldDataType = $rs2->fields[1];
					$oFieldValueText = $rs2->fields[2];
					$oFieldValueDate = $rs2->fields[3];
					$oFieldValueNumber = $rs2->fields[4];
					$oFieldValueMoney = $rs2->fields[5];
					$oFieldValueYesNo = $rs2->fields[6];
					$oFieldValueDecimal = $rs2->fields[7];
					$oFieldValueFile = $rs2->fields[8];
					$oFieldNumber = $rs2->fields[9];

					switch ($oFieldDataType) {
						case "YesNo":
							$oFieldValue[$oFieldDataType] = $oFieldValueYesNo;
							break;
						case "Text":
							$oFieldValue[$oFieldDataType] = $oFieldValueText;
							break;
						case "File":
							$oFieldValue[$oFieldDataType] = $oFieldValueFile;
							$oFieldName = substr($oFieldName,1);   // strip leading letter i
						break;
						case "Number":
							$oFieldValue[$oFieldDataType] = $oFieldValueNumber;
							break;
						case "Money":
							$oFieldValue[$oFieldDataType] = $oFieldValueMoney;
							break;
						case "Decimal":
							$oFieldValue[$oFieldDataType] = $oFieldValueMoney;
							break;
						case "Date":
							$oFieldValue[$oFieldDataType] = $oFieldValueDate;
							break;
					}

					// custom logic to suppress certain standards
					$suppress = 0;
					// 2013 round
					if ($oStandardNumber == '822' && $oEvaluationDate == '1/1/2012' && $oPassedMetrics2011['822'] == 1) $suppress = 1;
					if ($oStandardNumber == '824' && $oFieldNumber >= '1' && $oFieldNumber <= '3' && $oEvaluationDate == '1/1/2012' &&
					$oPassedMetrics2011['824'] == 1) $suppress = 1;
					if ($oStandardNumber == '825' && $oEvaluationDate == '1/1/2012' && $oPassedMetrics2011['825'] == 1) $suppress = 1;
					if ($oStandardNumber == '826' && $oEvaluationDate == '1/1/2012' && $oPassedMetrics2011['826'] == 1) $suppress = 1;
					// 2015 round
					if ($oStandardNumber == '822' && $oEvaluationDate == '1/1/2014' && $oPassedMetrics2013['822'] == 1) $suppress = 1;
					if ($oStandardNumber == '824' && $oFieldNumber >= '1' && $oFieldNumber <= '3' && $oEvaluationDate == '1/1/2014' &&
					$oPassedMetrics2013['824'] == 1) $suppress = 1;
					if ($oStandardNumber == '825' && $oEvaluationDate == '1/1/2014' && $oPassedMetrics2013['825'] == 1) $suppress = 1;
					if ($oStandardNumber == '826' && $oEvaluationDate == '1/1/2014' && $oPassedMetrics2013['826'] == 1) $suppress = 1;

					if ( ( trim($oFieldValue[$oFieldDataType]) == '' || trim($oFieldValue[$oFieldDataType]) == '0' ) && $suppress == 0 )
						$oNumberOfMissingFields++;

					$rs2->MoveNext();
				}
				$rs1->MoveNext();
			}

			$report->WriteReportRow(
				array(
					$oNickNameCity,
					$oEvaluationSubmitted,
					$oDateSubmitted,
					$oNumberOfMissingFields
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