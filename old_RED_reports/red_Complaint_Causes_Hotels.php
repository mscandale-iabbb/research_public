<?php

/*
 * 10/02/17 MJS - new file
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

$iBBBID = Numeric2($_POST['iBBBID']);
if (! $_POST && $userBBBID != '2000') $iBBBID = $userBBBID;
$iBusinessID = Numeric2($_POST['iBusinessID']);
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iClassification = NoApost($_POST['iClassification']);
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
if (! $iMaxRecs) $iMaxRecs = 100;
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$classifications = array(
	"ALL" => "",
	"Cancellation" => "CANCEL",
	"Billing" => "BILL",
	"Cleanliness" => "DIRTY",
	"Broken equipment/fixtures" => "BROKE",
	"Insects/pests" => "PESTS",
	"Noise" => "NOISE",
	"Theft" => "THEFT",
	"Safety from crime" => "SECUR",
	"Food" => "FOOD"
);

$input_form = new input_form($conn);

$howmany = 'all';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;", '', '', '' );
$input_form->AddDateField('iDateFrom','Complaints closed from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddMultipleSelectField('iClassification', 'Classification', $iClassification,
	$classifications, '', '', '', 'width:500px');
$SortFields = array(
	'ID' => 'c.ComplaintID',
	'BBB city' => 'BBB.NicknameCity,n.BusinessName',
	'Business name' => 'c.BusinessName'
);
$input_form->AddTextField('iMaxRecs', 'Maximum records', $iMaxRecs, "width:50px;", '', '', '' );
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

function GetClass($perc, $threshold) {
	if ($perc > $threshold) return 1;
	else return 0;
}
function Normalize($perc, $threshold) {
	if ($perc < $threshold) return (($perc / $threshold) / 2);
	if ($perc == $threshold) return 0.50;
	if ($perc > $threshold) return (0.50 + ((($perc - $threshold) / (1 - $threshold)) / 2));
}
function GetClassDescription($code) {
	global $classifications;
	foreach ($classifications as $k => $w) {
		if ($code == $w) return $k;	
	}
}

if ($_POST) {
	$query = "
		select top {$iMaxRecs}
			c.BBBID,
			BBB.NicknameCity,
			c.ComplaintID,
			'',
			c.BusinessName,
			t.ConsumerComplaint,
			t.DesiredOutcome,
			c.BusinessID
		from BusinessComplaint c WITH (NOLOCK)
		/*inner join Business b WITH (NOLOCK) on
			b.BBBID = c.BBBID and b.BusinessID = c.BusinessID*/
		left outer join BusinessComplaintText t WITH (NOLOCK) on
			c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
		left outer join BBB WITH (NOLOCK) ON
			BBB.BBBID = c.BBBID AND BBB.BBBBranchID = '0'
		inner join tblYPPA y WITH (NOLOCK) ON BusinessTOBID = y.yppa_code
		where
			('{$iBBBID}' = '' or c.BBBID = '{$iBBBID}') and
			('{$iBusinessID}' = '' or c.BusinessID = '{$iBusinessID}') and
			y.yppa_text = 'hotels' and
			c.DateClosed >= '{$iDateFrom}' and c.DateClosed <= '{$iDateTo}' and
			c.ComplaintID not like 'scam%' and
			c.CloseCode != '400' and
			len(t.ConsumerComplaint) > 75
		";
	if ($iSortBy) {
		$query .= " ORDER BY " . $iSortBy;
	}

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
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('Business Name', $SortFields['Business name'], '20%', 'left'),
				array('ID', $SortFields['ID'], '', 'left'),
				array('Classifications', '', '', 'left'),
				array('Most Common Words', '', '', 'left'),
			)
		);
		$xcount = 0;

		foreach ($rs as $k => $fields) {
			$iNarrative = $fields[5] . " " . $fields[6];
			$oClassifications = "";
			$anyhit = false;

			if ($iClassification == "" || strpos($iClassification,"CANCEL") !== false) {
				$x = shell_exec("python classify_hotels.py cancel '{$iNarrative}'");
				$hit = GetClass($x, 0.35);
				if ($hit) {
					$anyhit = true;
					$tallies["CANCEL"] += 1;
					$x = Normalize($x, 0.35);
					$oClassifications .= GetClassDescription("CANCEL") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"BILL") !== false) {
				$x = shell_exec("python classify_hotels.py bill '{$iNarrative}'");
				$hit = GetClass($x, 0.50);
				if ($hit) {
					$anyhit = true;
					$tallies["BILL"] += 1;
					$x = Normalize($x, 0.50);
					$oClassifications .= GetClassDescription("BILL") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"DIRTY") !== false) {
				$x = shell_exec("python classify_hotels.py dirty '{$iNarrative}'");
				$hit = GetClass($x, 0.30);
				if ($hit) {
					$anyhit = true;
					$tallies["DIRTY"] += 1;
					$x = Normalize($x, 0.30);
					$oClassifications .= GetClassDescription("DIRTY") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"BROKE") !== false) {
				$x = shell_exec("python classify_hotels.py broke '{$iNarrative}'");
				$hit = GetClass($x, 0.50);
				if ($hit) {
					$anyhit = true;
					$tallies["BROKE"] += 1;
					$x = Normalize($x, 0.50);
					$oClassifications .= GetClassDescription("BROKE") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"PESTS") !== false) {
				$x = shell_exec("python classify_hotels.py pests '{$iNarrative}'");
				$hit = GetClass($x, 0.50);
				if ($hit) {
					$anyhit = true;
					$tallies["PESTS"] += 1;
					$x = Normalize($x, 0.50);
					$oClassifications .= GetClassDescription("PESTS") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"NOISE") !== false) {
				$x = shell_exec("python classify_hotels.py noise '{$iNarrative}'");
				$hit = GetClass($x, 0.50);
				if ($hit) {
					$anyhit = true;
					$tallies["NOISE"] += 1;
					$x = Normalize($x, 0.50);
					$oClassifications .= GetClassDescription("NOISE") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"THEFT") !== false) {
				$x = shell_exec("python classify_hotels.py theft '{$iNarrative}'");
				$hit = GetClass($x, 0.50);
				if ($hit) {
					$anyhit = true;
					$tallies["THEFT"] += 1;
					$x = Normalize($x, 0.50);
					$oClassifications .= GetClassDescription("THEFT") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"SECUR") !== false) {
				$x = shell_exec("python classify_hotels.py secur '{$iNarrative}'");
				$hit = GetClass($x, 0.50);
				if ($hit) {
					$anyhit = true;
					$tallies["SECUR"] += 1;
					$x = Normalize($x, 0.50);
					$oClassifications .= GetClassDescription("SECUR") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if ($iClassification == "" || strpos($iClassification,"FOOD") !== false) {
				$x = shell_exec("python classify_hotels.py food '{$iNarrative}'");
				$hit = GetClass($x, 0.15);
				if ($hit) {
					$anyhit = true;
					$tallies["FOOD"] += 1;
					$x = Normalize($x, 0.15);
					$oClassifications .= GetClassDescription("FOOD") . " (" . FormatPercentage($x, 0) . "), ";
				}
			}

			if (! $anyhit) {
				continue;
			}

			if ($iClassification != "" and ! $anyhit) {
				continue;
			}
			$xcount += 1;
			if ($xcount >= $iMaxRecs) {
				break;
			}

			$topwords = '';
			$output = shell_exec("python complaint_similarity.py 'topwords' '{$fields[6]} {$fields[7]}' ''");
			if ($output) {
				$topwords = str_replace('|', ' ', $output);
			}

			// trim trailing comma
			if (substr($oClassifications, strlen($oClassifications) - 2, 2) == ', ') {
				$oClassifications= substr($oClassifications, 0, strlen($oClassifications) - 2);
			}

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}>{$fields[1]}</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID={$fields[0]}&iBusinessID={$fields[7]}>{$fields[4]}</a>",
					"<a target=detail href=red_Consumer_Details.php?iBBBID={$fields[0]}&iComplaintID={$fields[2]}>{$fields[2]}</a>",
					$oClassifications,
					$topwords
				)
			);
		}
	}
	$report->Close('suppress');
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>