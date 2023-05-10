<?php

/*
 * 09/27/17 MJS - new file
 * 09/29/17 MJS - removed "summary" option
 * 10/02/17 MJS - changed default value for maxrecs, added commas to output
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
//else if (! $_POST && $userBBBID == '2000') $iBBBID = '1066';
$iBusinessID = Numeric2($_POST['iBusinessID']);
$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
//$iDetails = NoApost($_REQUEST['iDetails']);
if (! $iDetails) $iDetails = 'yes';
$iClassification = NoApost($_POST['iClassification']);
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
if (! $iMaxRecs) $iMaxRecs = 100;
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];

$classifications = array(
	"ALL" => "",
	/*"Booking/Billing - All" => "BOO/ALL",*/
	"Booking/Billing - Website" => "BOO/WEB",
	"Booking/Billing - Rewards" => "BOO/REW",
	"Booking/Billing - Luggage fees" => "BOO/LUG", 				
	/*"Disruption/Delay - All" => "DIS/ALL",*/
	"Disruption/Delay - Weather" => "DIS/WEA", 
	"Disruption/Delay - Mechanical" => "DIS/MEC", 
	"Disruption/Delay - Overbooking" => "DIS/OVR", 
	"Disruption/Delay - Check-in" => "DIS/CHK", 
	/*"In-Flight - All" => "INF/ALL",*/
	"In-Flight - Food/beverage" => "INF/FOO", 
	"In-Flight - Entertainment" => "INF/ENT", 
	"In-Flight - Seating" => "INF/SEA", 
	"In-Flight - Cabin/general" => "INF/CAB", 
	"In-Flight - Attendants" => "INF/ATT", 
	/*"Luggage - All" => "LUG/ALL",*/
	"Luggage - Damaged" => "LUG/DAM", 
	"Luggage - Lost" => "LUG/LOS", 
	"Luggage - Stolen" => "LUG/PILF",
	/*"Other" => "OTHER"*/
);

$input_form = new input_form($conn);

//if ($userBBBID == '2000') $howmany = 'all';
//else $howmany = 'yoursonly';
$howmany = 'all';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$input_form->AddTextField('iBusinessID', 'Business ID', $iBusinessID, "width:100px;", '', '', '' );
$input_form->AddDateField('iDateFrom','Complaints closed from',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
/*$input_form->AddRadio('iDetails', 'Details', $iDetails, array(
		'Details' => 'yes',
		'Summary' => 'no',
	)
);*/
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
			y.yppa_text like '%airline%' and
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

		if ($iDetails == 'yes') {
			$report->WriteHeaderRow(
				array (
					array('BBB', $SortFields['BBB city'], '', 'left'),
					array('Business Name', $SortFields['Business name'], '20%', 'left'),
					array('ID', $SortFields['ID'], '', 'left'),
					array('Classifications', '', '', 'left'),
					array('Most Common Words', '', '', 'left'),
				)
			);
		}
		/*
		else if ($iDetails == 'no') {
			$report->WriteHeaderRow(
				array (
					array('Type of Issue', '', '', 'left'),
					array('Complaints', '', '', 'right'),
					array('%', '', '', 'right'),
				)
			);
		}
		*/
		$xcount = 0;

		foreach ($rs as $k => $fields) {
			$iNarrative = $fields[5] . " " . $fields[6];
			$oClassifications = "";
			$anyhit = false;

			/*
			if ($iClassification == "" || strpos($iClassification,"BOO/ALL") !== false) {
				$x = shell_exec("python classify_airlines_booking.py all '{$iNarrative}'");
				$hit = GetClass($x, 0.40);
				if ($hit) {
					$anyhit = true;
					$tallies["BOO/ALL"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.40);
						$oClassifications .= GetClassDescription("BOO/ALL") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}
			*/

			if ($iClassification == "" || strpos($iClassification,"BOO/WEB") !== false) {
				$x = shell_exec("python classify_airlines_booking.py website '{$iNarrative}'");
				$hit = GetClass($x, 0.15);
				if ($hit) {
					$anyhit = true;
					$tallies["BOO/WEB"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.15);
						$oClassifications .= GetClassDescription("BOO/WEB") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"BOO/REW") !== false) {
				$x = shell_exec("python classify_airlines_booking.py rewards '{$iNarrative}'");
				$hit = GetClass($x, 0.20);
				if ($hit) {
					$anyhit = true;
					$tallies["BOO/REW"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.20);
						$oClassifications .= GetClassDescription("BOO/REW") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"BOO/LUG") !== false) {
				$x = shell_exec("python classify_airlines_booking.py luggage '{$iNarrative}'");
				$hit = GetClass($x, 0.20);
				if ($hit) {
					$anyhit = true;
					$tallies["BOO/LUG"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.20);
						$oClassifications .= GetClassDescription("BOO/LUG") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			/*
			if ($iClassification == "" || strpos($iClassification,"DIS/ALL") !== false) {
				$x = shell_exec("python classify_airlines_delay.py all '{$iNarrative}'");
				$hit = GetClass($x, 0.40);
				if ($hit) {
					$anyhit = true;
					$tallies["DIS/ALL"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.40);
						$oClassifications .= GetClassDescription("DIS/ALL") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}
			*/

			if ($iClassification == "" || strpos($iClassification,"DIS/WEA") !== false) {
				$x = shell_exec("python classify_airlines_delay.py weather '{$iNarrative}'");
				$hit = GetClass($x, 0.15);
				if ($hit) {
					$anyhit = true;
					$tallies["DIS/WEA"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.15);
						$oClassifications .= GetClassDescription("DIS/WEA") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"DIS/MEC") !== false) {
				$x = shell_exec("python classify_airlines_delay.py mechanical '{$iNarrative}'");
				$hit = GetClass($x, 0.03);
				if ($hit) {
					$anyhit = true;
					$tallies["DIS/MEC"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.03);
						$oClassifications .= GetClassDescription("DIS/MEC") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"DIS/OVR") !== false) {
				$x = shell_exec("python classify_airlines_delay.py overbooking '{$iNarrative}'");
				$hit = GetClass($x, 0.03);
				if ($hit) {
					$anyhit = true;
					$tallies["DIS/OVR"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.03);
						$oClassifications .= GetClassDescription("DIS/OVR") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"DIS/CHK") !== false) {
				$x = shell_exec("python classify_airlines_delay.py checkin '{$iNarrative}'");
				$hit = GetClass($x, 0.10);
				if ($hit) {
					$anyhit = true;
					$tallies["DIS/CHK"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.10);
						$oClassifications .= GetClassDescription("DIS/CHK") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			/*
			if ($iClassification == "" || strpos($iClassification,"INF/ALL") !== false) {
				$x = shell_exec("python classify_airlines_inflight.py all '{$iNarrative}'");
				$hit = GetClass($x, 0.15);
				if ($hit) {
					$anyhit = true;
					$tallies["INF/ALL"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.15);
						$oClassifications .= GetClassDescription("INF/ALL") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}
			*/

			if ($iClassification == "" || strpos($iClassification,"INF/FOO") !== false) {
				$x = shell_exec("python classify_airlines_inflight.py food '{$iNarrative}'");
				$hit = GetClass($x, 0.15);
				if ($hit) {
					$anyhit = true;
					$tallies["INF/FOO"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.15);
						$oClassifications .= GetClassDescription("INF/FOO") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"INF/ENT") !== false) {
				$x = shell_exec("python classify_airlines_inflight.py entertainment '{$iNarrative}'");
				$hit = GetClass($x, 0.05);
				if ($hit) {
					$anyhit = true;
					$tallies["INF/ENT"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.05);
						$oClassifications .= GetClassDescription("INF/ENT") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"INF/SEA") !== false) {
				$x = shell_exec("python classify_airlines_inflight.py seating '{$iNarrative}'");
				$hit = GetClass($x, 0.05);
				if ($hit) {
					$anyhit = true;
					$tallies["INF/SEA"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.05);
						$oClassifications .= GetClassDescription("INF/SEA") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"INF/CAB") !== false) {
				$x = shell_exec("python classify_airlines_inflight.py cabin '{$iNarrative}'");
				$hit = GetClass($x, 0.02);
				if ($hit) {
					$anyhit = true;
					$tallies["INF/CAB"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.02);
						$oClassifications .= GetClassDescription("INF/CAB") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"INF/ATT") !== false) {
				$x = shell_exec("python classify_airlines_inflight.py attendant '{$iNarrative}'");
				$hit = GetClass($x, 0.05);
				if ($hit) {
					$anyhit = true;
					$tallies["INF/ATT"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.05);
						$oClassifications .= GetClassDescription("INF/ATT") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			/*
			if ($iClassification == "" || strpos($iClassification,"LUG/ALL") !== false) {
				$x = shell_exec("python classify_airlines_luggage.py all '{$iNarrative}'");
				$hit = GetClass($x, 0.05);
				if ($hit) {
					$anyhit = true;
					$tallies["LUG/ALL"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.05);
						$oClassifications .= GetClassDescription("LUG/ALL") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}
			*/

			if ($iClassification == "" || strpos($iClassification,"LUG/DAM") !== false) {
				$x = shell_exec("python classify_airlines_luggage.py damaged '{$iNarrative}'");
				$hit = GetClass($x, 0.03);
				if ($hit) {
					$anyhit = true;
					$tallies["LUG/DAM"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.03);
						$oClassifications .= GetClassDescription("LUG/DAM") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"LUG/LOS") !== false) {
				$x = shell_exec("python classify_airlines_luggage.py lost '{$iNarrative}'");
				$hit = GetClass($x, 0.15);
				if ($hit) {
					$anyhit = true;
					$tallies["LUG/LOS"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.15);
						$oClassifications .= GetClassDescription("LUG/LOS") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if ($iClassification == "" || strpos($iClassification,"LUG/PILF") !== false) {
				$x = shell_exec("python classify_airlines_luggage.py stolen '{$iNarrative}'");
				$hit = GetClass($x, 0.03);
				if ($hit) {
					$anyhit = true;
					$tallies["LUG/PILF"] += 1;
					if ($iDetails == 'yes') {
						$x = Normalize($x, 0.03);
						$oClassifications .= GetClassDescription("LUG/PILF") . " (" . FormatPercentage($x, 0) . "), ";
					}
				}
			}

			if (! $anyhit) {
				continue;
			}

			/*
			if ($iClassification == "" || strpos($iClassification,"OTHER") !== false) {
				if (! $anyhit) {
					$tallies['OTHER'] += 1;
				}
			}
			*/

			if ($iDetails == 'yes') {

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
	}
	/*
	if ($iDetails == 'no') {
		//$tallies[''] = $xcount;
		$grandtotal = 0;
		foreach ($tallies as $k => $v) {
			$grandtotal += $v;
		}
		foreach ($classifications as $k => $v) {
			if ($k == "ALL") {
				continue;
			}
			if (! $tallies[$v]) {
				$tallies[$v] = 0;
			}
			$report->WriteReportRow( array( $k, $tallies[$v], FormatPercentage($tallies[$v] / $grandtotal)) );
		}
	}
	*/
	$report->Close('suppress');
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>