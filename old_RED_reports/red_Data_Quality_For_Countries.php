<?php

/*
 * 11/02/17 MJS - new file - ***** THIS REPORT ISN'T NEEDED AND ISN'T COMPLETE *****
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
	/*
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
	*/

	$query = "
		SELECT DISTINCT TOP {$iMaxRecs}
			b.BBBID,
			BBB.NickNameCity,
			b.BusinessID,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
			b.BBBRatingGrade,
			b.BusinessName,
			b.StreetAddress,
			b.City,
			b.StateProvince,
			b.PostalCode,
			b.Country
		FROM Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join CORE.dbo.lnkBureauZip bz WITH (NOLOCK) on
			(
				(b.Country like 'us%' or b.Country like 'united st%') and
				bz.ZipCode = LEFT(b.PostalCode,5) and
				(bz.CountryCode like 'us%' or bz.CountryCode like 'united st%')
			) 
		left outer JOIN ForeignCountries fc WITH (NOLOCK) on b.Country = fc.Country
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		WHERE
			('{$iBBBID}' = '' or b.BBBID = '{$iBBBID}') and
			b.IsReportable = 1 and b.PublishToCIBR = 1 and
			fc.Country > ''
			--b.Country > '' and bz.CountryCode is null
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
				array('Business Name', $SortFields['Business name'], '', 'left'),
				array('AB', $SortFields['AB'], '', 'left'),
				array('Rating', $SortFields['Rating'], '', 'left'),
				array('Address', $SortFields['Street'], '', 'left'),
				array('Country', $SortFields['Country'], '', 'left'),
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

			/*
			$suppress_button = "<a class=cancel_button_small " .
				"onclick=\" if (confirm('This business name is spelled correctly and should be removed " .
				"from this list?')) { form1.iSuppress.value = '{$fields[0]}|{$fields[2]}'; " .
				"form1.submit(); } else return false; \">X</a>";
			*/

			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID={$fields[0]}" .
						">{$fields[1]}</a>",
					"<a target=detail href=red_Business_Details.php?iBBBID={$fields[0]}" .
						"&iBusinessID={$fields[2]}>{$fields[2]}</a>",
					$fields[5],
					$fields[3],
					$fields[4],
					$fields[6] . " " . $fields[7] . " " . $fields[8] . " " . $fields[9],
					$fields[10],
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