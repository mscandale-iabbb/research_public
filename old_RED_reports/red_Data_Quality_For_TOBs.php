<?php

/*
 * 11/06/14 MJS - changed die() to AbortREDReport()
 * 11/11/14 MJS - fixed bugs
 * 09/01/15 MJS - added filter for searchable field in TOB record
 * 08/25/16 MJS - aligned column headers
 * 11/10/16 MJS - changed REQUEST to POST
 * 07/05/17 MJS - added input options, more columns, more query criteria
 * 07/06/17 MJS - added X button, Reason column, more query criteria
 * 07/07/17 MJS - modified query criteria
 * 07/10/17 MJS - added more query criteria
 * 07/12/17 MJS - added more query criteria
 * 07/13/17 MJS - modified query criteria and reason descriptions
 * 07/17/17 MJS - added more query criteria
 * 07/18/17 MJS - modified to use different table for misassigned TOBs
 * 07/21/17 MJS - added more vague TOBs
 * 10/10/17 MJS - fixed bug
 * 03/22/18 MJS - excluded out of business
 * 12/07/18 MJS - modified TOB 60784 and 60987
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
else if (! $_POST && $userBBBID == '2000') $iBBBID = '1066';
$iVague = NoApost($_REQUEST['iVague']);
if (! $iVague) $iVague = 'yes';
$iMalformed = NoApost($_REQUEST['iMalformed']);
if (! $iMalformed) $iMalformed = 'yes';
$iMisassigned = NoApost($_REQUEST['iMisassigned']);
if (! $iMisassigned) $iMisassigned = 'no';
$iSuppress = NoApost($_POST['iSuppress']);
$iMaxRecs = CleanMaxRecs($_POST['iMaxRecs']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];
$input_form = new input_form($conn);

if ($userBBBID == '2000') $howmany = 'all';
else $howmany = 'yoursonly';
$input_form->AddSelectField('iBBBID', 'BBB', $iBBBID, $input_form->BuildBBBCitiesArray($howmany) );
$SortFields = array(
	'ID' => 'b.BusinessID',
	'BBB city' => 'BBB.NicknameCity,b.BusinessName',
	'Business name' => 'b.BusinessName',
	'TOB' => 'b.TOBID,b.BusinessName',
	'TOB description' => 'y.yppa_text,b.BusinessName',
	'Rating' => 'r.BBBRatingSortOrder,b.BusinessName',
	'AB' => 'AB,b.BusinessName',
	'Reason' => 'Reason,b.BusinessName',
);
$input_form->AddRadio('iVague', 'Businesses with vague TOBs (99999, 60987, etc.)', $iVague, array(
		'Yes' => 'yes',
		'No' => 'no',
	)
);
$input_form->AddRadio('iMalformed', 'Businesses with malformed TOBs', $iMalformed, array(
		'Yes' => 'yes',
		'No' => 'no',
	)
);
$input_form->AddRadio('iMisassigned', 'Businesses with likely misassigned TOBs', $iMisassigned, array(
		'Yes' => 'yes',
		'No' => 'no',
	)
);
$input_form->AddTextField('iSuppress', '', $iSuppress, "visibility:hidden;" );
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddPagingOption();
$input_form->AddSourceOption();
$input_form->AddExportOptions();
$input_form->AddScheduledTaskOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	/* special option to suppress */
	if ($iSuppress) {
		$tmpfields = explode("|", $iSuppress);
		$tmpBBBID = $tmpfields[0];
		$tmpBusinessID = $tmpfields[1];
		$tmpTOBID = $tmpfields[2];
		$insert = "
			INSERT INTO BusinessTOBIDGood
				(BBBID, BusinessID, TOBID, DateCreated) VALUES (
				'{$tmpBBBID}',
				'{$tmpBusinessID}',
				'{$tmpTOBID}',
				GETDATE()
			)";
		$r = $conn->execute($insert);
	}

	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$column_OOB = "OutOfBusinessTypeId";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$column_OOB = "OutOfBusinessStatusTypeId";
	}

	$query = "
		SELECT DISTINCT TOP {$iMaxRecs}
			b.BBBID,
			BBB.NickNameCity + ', ' + BBB.State,
			b.BusinessID,
			b.BusinessName,
			b.TOBID,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.BBBRatingGrade,
			r.BBBRatingSortOrder, /* not displayed - SQL needs only when sorting by it */
			y.yppa_text,
			b.Website,
			case
				when '{$iVague}' = 'yes' and (b.TOBID like '99999%' or t.TOBID like '99999%' or b.TOBID like '60987-000' or
					b.TOBID like '60984%' or b.TOBID like '60989%' or b.TOBID like '61016%' or b.TOBID like '20087%' or
					b.TOBID like '50308%' or b.TOBID like '60784-000' or b.TOBID like '61047%' or b.TOBID like '80342%')
					then 'Not specific enough'
				when '{$iMalformed}' = 'yes' and (LEN(b.TOBID) != 9 or ( LEN(t.TOBID) >= 1 and LEN(t.TOBID) != 9 ) or b.TOBID is null) then 'Malformed'
				when '{$iMisassigned}' = 'yes' then bt.ReasonDescription 
			end as Reason
		FROM Business b WITH (NOLOCK)
		left outer join BusinessTOBID t WITH (NOLOCK) ON
			t.BBBID = b.BBBID AND t.BusinessID = b.BusinessID
		left outer join BusinessTOBID t2 WITH (NOLOCK) ON
			t2.BBBID = b.BBBID AND t2.BusinessID = b.BusinessID and t2.TOBID != t.TOBID
		left outer join BadBusinessTOBID bt WITH (NOLOCK) ON
			bt.BBBID = b.BBBID AND bt.BusinessID = b.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join tblYPPA y WITH (NOLOCK) on y.yppa_code = b.TOBID
		left outer join BusinessTOBIDGood g WITH (NOLOCK) ON
			g.BBBID = t.BBBID AND g.BusinessID = t.BusinessID --and g.TOBID = t.TOBID
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.IsReportable = 1 and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			t.PublishToCIBR = 1 and g.TOBID is null and
			(
				(
					'{$iVague}' = 'yes' and
					(
						b.TOBID like '99999%' or t.TOBID like '99999%' or b.TOBID like '60987%' or
						b.TOBID like '60984%' or b.TOBID like '60989%' or b.TOBID like '61016%' or b.TOBID like '20087%' or
						b.TOBID like '50308%' or b.TOBID like '60784%' or b.TOBID like '61047%' or b.TOBID like '80342%'
					)
				) or
				(
					'{$iMalformed}' = 'yes' and
					(
						LEN(b.TOBID) != 9 or
						( LEN(t.TOBID) >= 1 and LEN(t.TOBID) != 9 ) or
						b.TOBID is null
					)
				) or
				(
					'{$iMisassigned}' = 'yes' and bt.BusinessID is not null
				)
			)
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
				array('ID', $SortFields['ID'], '', 'left'),
				array('Name', $SortFields['Business name'], '', 'left'),
				//array('TOB', $SortFields['TOB'], '', 'left'),
				array('Description', $SortFields['TOB description'], '', 'left'),
				array('Website', '', '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Reason', $SortFields['Reason'], '', 'left'),
				array('', '', '', ''),
			)
		);
		$xcount = 0;

		$iPageNumber = $_POST['iPageNumber'];
		$iPageSize = $_POST['iPageSize'];
		if ($_POST['output_type'] > '') $iPageSize = count($rs);
		$TotalPages = round(count($rs) / $iPageSize, 0);
		if (count($rs) % $iPageSize > 0) {
			$TotalPages++;
		}
		if ($iPageNumber > $TotalPages) $iPageNumber = 1;

		foreach ($rs as $k => $fields) {
			$xcount++;

			if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
			if ($xcount > $iPageNumber * $iPageSize) break;

			$website = '';
			if ($fields[9]) {
				if (substr($fields[9],0,4) != "http") {
					$fields[9] = "http://" . $fields[9];
				}
				$website = "<a target=_detail href={$fields[9]}>Website</a>";
			}

			$suppress_button = "<a class=cancel_button_small " .
					"onclick=\" if (confirm('This TOB assignment has been reviewed and should be removed " .
					"from this list?')) { form1.iSuppress.value = '{$fields[0]}|{$fields[2]}|{$fields[4]}'; " .
					"form1.submit(); } else return false; \">X</a>";

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . NoApost($fields[1]) . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[2] .  ">" . NoApost($fields[2]) . "</a>",
					NoApost($fields[3]),
					//$fields[4],
					$fields[8],
					$website,
					$fields[6],
					$fields[5],
					$fields[10],
					$suppress_button
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