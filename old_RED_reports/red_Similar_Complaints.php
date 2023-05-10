<?php

/*
 * 09/19/17 MJS - new file
 * 09/20/17 MJS - added input form, changed parameters passed to script
 * 09/21/17 MJS - fixed bug whereby 0 value for iMinMatch was resetting to 1
 * 09/26/17 MJS - added field for most common words in text
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();


$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iComplaintID = NoApost($_REQUEST['iComplaintID']);
$iWords = NoApost($_POST['iWords']);
$iMonths = Numeric2($_POST['iMonths']);
if (! $iMonths) {
	$iMonths = 12;
}
$iMaxRecs = Numeric2($_POST['iMaxRecs']);
if (! $iMaxRecs) {
	$iMaxRecs = 50;
}
$iMinMatch = Numeric2($_POST['iMinMatch']);
if (! $_POST) {
	$iMinMatch = 1;
}

if (! $iBBBID || ! $iComplaintID) {
	die("No record selected");
}

function ShowField($label, $value) {
	echo "
		<tr>
		<td class='labelback' width=15%>
		{$label}
		<td class='table_cell'>
		{$value}
		";
}
function ShowDivider() {
	echo "
		<tr>
		<td colspan=2>
		<hr size=30 />
		";
}

$query = "
	SELECT
		c.ComplaintID,
		t.ConsumerComplaint,
		t.DesiredOutcome,
		c.BusinessID
	FROM BusinessComplaint c WITH (NOLOCK)
	left outer join BusinessComplaintText t WITH (NOLOCK) ON
		t.BBBID = c.BBBID AND t.ComplaintID = c.ComplaintID
	WHERE
		c.BBBID = '{$iBBBID}' and
		c.ComplaintID = '{$iComplaintID}'
	";
$rsraw = $conn->execute($query);
$rs = $rsraw->GetArray();
if (count($rs) > 0) {
	foreach ($rs as $k => $fields) {		
		$narrative = $fields[1];
		$outcome = $fields[2];
		$oBusinessID = $fields[3];
	}
}

if (! $iWords) {
	$output = shell_exec("python complaint_similarity.py 'words' '{$narrative} {$outcome}' ''");
	$words = str_replace('|', ' ', $output);
}
else {
	$words = $iWords;
}

echo "
	<div class='main_section roundedborder'>
	<table class='report_table'>
	";
ShowField("Complaint", "<a href=red_Consumer_Details.php?iBBBID={$iBBBID}&iComplaintID={$iComplaintID}>" . $fields[0] . "</a>");
ShowField("Narrative", $narrative);
if ($outcome) {
	ShowField("Desired outcome", $outcome);
}
echo "
	<form id=form1 method=post>

	<tr>
	<td class='labelback' width=15%>
	Words to search
	<td class='table_cell'>
	<textarea id=iWords name=iWords rows=5 style='width:90%'>{$words}</textarea>

	<tr>
	<td class='labelback' width=15%>
	Complaints closed in past
	<td class='table_cell'>
	<input type=text id=iMonths name=iMonths style='width:2%' value='{$iMonths}' /> months

	<tr>
	<td class='labelback' width=15%>
	Search
	<td class='table_cell'>
	<input type=text id=iMaxRecs name=iMaxRecs style='width:2%' value='{$iMaxRecs}' /> records

	<tr>
	<td class='labelback' width=15%>
	Show only if
	<td class='table_cell'>
	<input type=text id=iMinMatch name=iMinMatch style='width:2%' value='{$iMinMatch}' />% or more of words found

	<tr>
	<td class='labelback' width=15%>
	&nbsp;
	<td class='table_cell'>
	<input type=hidden id=iBBBID name=iBBBID value='{$iBBBID}' />
	<input type=hidden id=iComplaintID name=iComplaintID value='{$iComplaintID}' />
	<input type=submit class='submit_button' style='color:white' value='   Search   ' />

	</form>	
	";
ShowDivider();

if ($_POST && strlen($narrative) > 70) {
	// check other complaints same company
	$query = "
		SELECT TOP {$iMaxRecs}
			c.ComplaintID,
			t.ConsumerComplaint,
			t.DesiredOutcome,
			c.DateClosed
		FROM BusinessComplaint c WITH (NOLOCK)
		LEFT OUTER JOIN BusinessComplaintText t WITH (NOLOCK) ON
			t.BBBID = c.BBBID AND t.ComplaintID = c.ComplaintID
		WHERE
			c.BBBID = '{$iBBBID}' and
			c.BusinessiD = '{$oBusinessID}' and
			c.ComplaintID != '{$iComplaintID}' and
			(len(t.ConsumerComplaint) > 70 or len(t.DesiredOutcome) > 70) and
			c.DateClosed is not null and
			c.DateClosed >= GETDATE() - ({$iMonths} * 30)
		ORDER BY
			SUBSTRING(t.ConsumerComplaint,45,1), SUBSTRING(t.ConsumerComplaint,15,1) DESC,
			SUBSTRING(t.ConsumerComplaint,25,1), SUBSTRING(t.ConsumerComplaint,35,1) DESC 
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			//$narrative2 = strip_tags($fields[1]);
			//$outcome2 = strip_tags($fields[2]);
			$narrative2 = " " . $fields[1] . " ";
			$outcome2 = " " . $fields[2] . " ";
			$output = shell_exec("python complaint_similarity.py 'similarity' '{$words}' '{$narrative2} {$outcome2}'");
			$xvals = explode('|', $output);
			$ratio = $xvals[0];
			$xvals[0] = '';
			foreach ($xvals as $x) {
				$narrative2 = str_ireplace(" " . $x . " ", " <span class=red>{$x}</span> ", $narrative2);
				$outcome2 = str_ireplace(" " . $x . " ", " <span class=red>{$x}</span> ", $outcome2);
			}
			if (($ratio * 100) >= $iMinMatch) {
				ShowField("Complaint",
					"<a href=red_Consumer_Details.php?iBBBID={$iBBBID}&iComplaintID={$fields[0]}>" .
					$fields[0] . "</a>" .
					" &nbsp; &nbsp;  " .
					"Closed " . FormatDate($fields[3]) .
					" &nbsp; &nbsp;  " .
					"<span class=red>" . FormatPercentage($ratio) . " of words found" . "</span>"
				);
				$output = shell_exec("python complaint_similarity.py 'topwords' '{$fields[1]} {$fields[2]}' ''");
				if ($output) {
					$topwords = str_replace('|', ' ', $output);
					ShowField("Most common words", $topwords);
				}
				ShowField("Narrative", $narrative2);
				if ($outcome2) {
					ShowField("Desired outcome", $outcome2);
				}

				ShowDivider();
			}
		}
	}
}

echo "
	<tr><td colspan=2 class='column_header thickpadding center'>
	<a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a>
	</table>
	</div>
	";

$page->write_pagebottom();

?>