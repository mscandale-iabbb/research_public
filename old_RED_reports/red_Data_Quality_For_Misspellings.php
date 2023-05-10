<?php

/*
 * 07/14/17 MJS - new file
 * 09/26/17 MJS - added option X to suppress
 * 03/22/18 MJS - excluded out of business
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
	'BBB city' => 'BBB.NicknameCity,n.BusinessName',
	'Business name' => 'n.BusinessName',
	'Rating' => 'r.BBBRatingSortOrder,n.BusinessName',
	'AB' => 'AB,n.BusinessName',
	'Reason' => 'Reason,n.BusinessName',
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
		$insert = "
			INSERT INTO BusinessNameGood
				(BBBID, BusinessID, DateCreated)
			VALUES (
				'{$tmpBBBID}',
				'{$tmpBusinessID}',
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
			n.BusinessName,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.BBBRatingGrade,
			case
				when n.BusinessName like '%applaince%' then 'applaince|Appliance'
				when n.BusinessName like '%assocaites%' then 'assocaites|Associates'
				when n.BusinessName like '%equestrain%' then 'equestrain|Equestrian'
				when n.BusinessName like '%restuarant%' then 'restuarant|Restaurant'
				when n.BusinessName like '%specailist%' then 'specailist|Specialist'
				when n.BusinessName like '%repiar%' then 'repiar|Repair'
				when n.BusinessName like '%maintanance%' then 'maintanance|Maintenance'
				when n.BusinessName like '%maintanence%' then 'maintanence|Maintenance'
				when n.BusinessName like '%maintenence%' then 'maintenence|Maintenance'
				when n.BusinessName like '%appriasal%' then 'appriasal|Appraisal'
				when n.BusinessName like '%managemant%' then 'managemant|Management'
				when n.BusinessName like '%janatorial%' then 'janatorial|Janitorial'
				when n.BusinessName like '%associaton%' then 'associaton|Association'
				when n.BusinessName like '%renovaton%' then 'renovaton|Renovation'
				when n.BusinessName like '%conditoning%' then 'conditoning|Conditioning'
				when n.BusinessName like '%corporaton%' then 'corporaton|Corporation'
				when n.BusinessName like '%restoraton%' then 'restoraton|Restoration'
				when n.BusinessName like '%constructon%' then 'constructon|Construction'
				when n.BusinessName like '%natonal%' then 'natonal|National'
				when n.BusinessName like '%communicaton%' then 'communicaton|Communication'
				when n.BusinessName like '%profesionals%' then 'profesionals|Professionals'
				when n.BusinessName like '%profesional %' then 'profesional |Professional'
				when n.BusinessName like '%martail%' then 'martail|Martial'
				when n.BusinessName like '%insurence%' then 'insurence|Insurance'
				when n.BusinessName like '%conracting%' then 'conracting|Contracting'
				when n.BusinessName like '%laundramat%' then 'laundramat|Laundromat'
				when n.BusinessName like '%premeir %' then 'premeir |Premier'
				when n.BusinessName like '%distibution%' then 'distibution|Distribution'
				when n.BusinessName like '%manufactoring%' then 'manufactoring|Manufacturing'
				when n.BusinessName like '%distibuting%' then 'distibuting|Distributing'
				when n.BusinessName like '%cemetary%' then 'cemetary|Cemetery'
				when n.BusinessName like '%accomodations%' then 'accomodations|Accommodations'
				when n.BusinessName like '%effeciency%' then 'effeciency|Efficiency'
				when n.BusinessName like '%buisness%' then 'buisness|Business'
				when n.BusinessName like '%begining%' then 'begining|Beginning'
				when n.BusinessName like '%chauffer%' then 'chauffer|Chauffeur'
				when n.BusinessName like '%commitee%' then 'commitee|Committee'
				when n.BusinessName like '%foriegn%' then 'foriegn|Foreign'
				when n.BusinessName like '%freinds%' then 'freinds|Friends'
				when n.BusinessName like '%independant%' then 'independant|Independent'
				when n.BusinessName like '%independance%' then 'independance|Independence'
				when n.BusinessName like '%gaurd %' then 'gaurd |Guard'
				when n.BusinessName like '%gaurdian%' then 'gaurdian|Guardian'
				when n.BusinessName like '%knowlege%' then 'knowlege|Knowledge'
				when n.BusinessName like '% millenium%' then 'millenium|Millennium'
				when n.BusinessName like '%tatoo%' then 'tatoo|Tattoo'
				when n.BusinessName like '%peircing%' then 'peircing|Piercing'
				when n.BusinessName like '%tommorrow%' then 'tommorrow|Tomorrow'
				when n.BusinessName like '%tommorow%' then 'tommorow|Tomorrow'
				when n.BusinessName like '%prefered%' then 'prefered|Preferred'
				when n.BusinessName like '%referal%' then 'referal|Referral'
			end as 'Reason'
		FROM BusinessName n WITH (NOLOCK)
		inner join Business b WITH (NOLOCK) ON b.BBBID = n.BBBID AND b.BusinessID = n.BusinessID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join BusinessNameGood bng WITH (NOLOCK) ON bng.BBBID = n.BBBID and bng.BusinessID = n.BusinessID
		left outer join {$table_Org} o WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.IsReportable = 1 and
			({$column_OOB} is null or {$column_OOB} = '') and
			(b.BOConlyIsOutOfBusiness is null or b.BOConlyIsOutOfBusiness = '0') and
			bng.BusinessID is null and
			n.PublishToCIBR = 1 and (
				n.BusinessName like '%applaince%' or
				n.BusinessName like '%assocaites%' or
				n.BusinessName like '%equestrain%' or
				n.BusinessName like '%restuarant%' or
				n.BusinessName like '%specailist%' or
				n.BusinessName like '%repiar%' or
				n.BusinessName like '%maintanance%' or
				n.BusinessName like '%maintanence%' or
				n.BusinessName like '%maintenence%' or
				n.BusinessName like '%appriasal%' or
				n.BusinessName like '%managemant%' or
				n.BusinessName like '%janatorial%' or
				n.BusinessName like '%associaton%' or
				n.BusinessName like '%renovaton%' or
				n.BusinessName like '%conditoning%' or
				n.BusinessName like '%corporaton%' or
				n.BusinessName like '%restoraton%' or
				n.BusinessName like '%constructon%' or
				n.BusinessName like '%natonal%' or
				n.BusinessName like '%communicaton%' or
				n.BusinessName like '%profesionals%' or
				n.BusinessName like '%profesional %' or
				n.BusinessName like '%martail%' or
				n.BusinessName like '%insurence%' or
				n.BusinessName like '%conracting%' or
				n.BusinessName like '%laundramat%' or
				n.BusinessName like '%premeir %' or
				n.BusinessName like '%distibution%' or
				n.BusinessName like '%manufactoring%' or
				n.BusinessName like '%distibuting%' or
				n.BusinessName like '%cemetary%' or
				n.BusinessName like '%accomodations%' or
				n.BusinessName like '%effeciency%' or
				n.BusinessName like '%buisness%' or
				n.BusinessName like '%begining%' or
				n.BusinessName like '%chauffer%' or
				n.BusinessName like '%commitee%' or
				n.BusinessName like '%foriegn%' or
				n.BusinessName like '%freinds%' or
				n.BusinessName like '%independant%' or
				n.BusinessName like '%independance%' or
				n.BusinessName like '%gaurd %' or
				n.BusinessName like '%gaurdian%' or
				n.BusinessName like '%knowlege%' or
				n.BusinessName like '% millenium%' or
				n.BusinessName like '%tatoo%' or
				n.BusinessName like '%peircing%' or
				n.BusinessName like '%tommorow%' or
				n.BusinessName like '%prefered%' or
				n.BusinessName like '%referal%'
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
				array('#', '', '', 'right'),
				array('BBB', $SortFields['BBB city'], '', 'left'),
				array('ID', $SortFields['ID'], '', 'left'),
				array('Word', $SortFields['Reason'], '', 'left'),
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
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

			// bold misspelled word
			$bad_word = explode("|",$fields[6])[0];
			$pos = stripos($fields[3], $bad_word);
			$original_word = substr($fields[3], $pos, strlen($bad_word));
			$fields[3] = str_ireplace($bad_word, "<span class=red>{$original_word}</span>", $fields[3]);

			$good_word = "<span class=xgreen>" . explode("|",$fields[6])[1] . "</span>";

			$suppress_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This business name is spelled correctly and should be removed " .
				"from this list?')) { form1.iSuppress.value = '{$fields[0]}|{$fields[2]}'; " .
				"form1.submit(); } else return false; \">X</a>";

			$report->WriteReportRow(
				array (
					$xcount,
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						">" . $fields[1] . "</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[0] .
						"&iBusinessID=" . $fields[2] .  ">" . $fields[2] . "</a>",
					$good_word,
					$fields[3],
					$fields[4],
					$fields[5],
					$suppress_button
				)
			);
		}
	}
	$report->Close();
	if ($iShowSource > '') {
		$report->WriteSource($query);
	}
}
	
$page->write_pagebottom();

?>