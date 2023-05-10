<?php

/*
 * 01/03/20 MJS - new file
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iInterest = NoApost($_REQUEST['iInterest']);
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddTextField('iInterest', 'Keyword', $iInterest, "width:175px;");
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
		$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
		$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$SortFields = array(
	'Person name' => 'p.LastName,p.FirstName',
	'Title' => 'ltrim(p.Title),p.LastName,p.FirstName',
	'BBB name' => 'BBB.Name,p.LastName,p.FirstName',
	'BBB city' => 'BBB.NicknameCity',
	'Phone' => 'ph.PhoneNumber,p.LastName,p.FirstName',
	'Email' => 'p.Email,p.LastName,p.FirstName'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "
		SELECT
			p.PreName,
			p.FirstName,
			p.MiddleName,
			p.LastName,
			p.PostName,
			REPLACE(p.Title,'/',' / '),
			BBB.NicknameCity,
			ph.PhoneNumber,
			ph.Extension,
			TimeZone,
			p.Email,
			p.InterestCommittees,
			p.InterestAreas
		FROM BBBPerson p WITH (NOLOCK)
		LEFT OUTER JOIN BBBPhone ph WITH (NOLOCK) ON
			ph.BBBIDFull = p.BBBIDFull and ph.PhoneID = p.PhoneID
		INNER JOIN BBB WITH (NOLOCK) ON p.BBBID = BBB.BBBID AND p.BBBBranchID = BBB.BBBBranchID
		WHERE
			(BBB.IsActive = '1' or BBB.BBBID = '2100') and
			(p.InterestCommittees > '' or p.InterestAreas > '') and
			('{$iInterest}' = '' or p.InterestCommittees LIKE '%{$iInterest}%' or p.InterestAreas LIKE '%{$iInterest}%') and
			('{$iRegion}' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
			('{$iSalesCategory}' = '' or
				SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "'))
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
		if ($output_type == "") {
			$report->WriteHeaderRow(
				array (
					array('Name', $SortFields['Person name'], '', 'left'),
					array('Title', $SortFields['Title'], '', 'left'),
					array('BBB', $SortFields['BBB name'], '', 'left'),
					array('Email', $SortFields['Email'], '', 'left'),
					array('Phone', $SortFields['Phone'], '10%', 'left'),
					array('Committtes of Interest', '', '', 'left'),
					array('Areas of Interest', '', '', 'left'),
				)
			);
		}
		else { // excel or word
			$report->WriteHeaderRow(
				array (
					array('Pre Name'),
					array('First Name'),
					array('Middle Name'),
					array('Last Name'),
					array('Post Name'),
					array('Title'),
					array('BBB'),
					array('Email'),
					array('Phone'),
					array('Committtes of Interest'),
					array('Areas of Interest'),
				)
			);
		}
		foreach ($rs as $k => $fields) {

			// person name
			$name = '';
			if (trim($fields[0])) $name = AddApost($fields[0]);
			if (trim($fields[1])) $name .= ' ' . AddApost($fields[1]);
			if (trim($fields[2])) $name .= ' ' . AddApost($fields[2]);
			if (trim($fields[3])) $name .= ' ' . AddApost($fields[3]);
			if (trim($fields[4])) $name .= ', ' . AddApost($fields[4]);
			$name = trim($name);
			
			// email
			$email = '';
			if ($output_type == '' && $fields[10]) $email = "<a href=mailto:" . $fields[10] . ">Email</a>";
			else $email = $fields[10];

			if ($output_type == "") {
				$report->WriteReportRow(
					array (
						$name,
						AddApost($fields[5]),  // title
						AddApost($fields[6]),  // bbb
						$email,
						FormatPhone($fields[7]) . ' ' . $fields[8] . ' ' . $fields[9],
						$fields[11],
						$fields[12],
					)
				);
			}
			else { // excel or word
				$report->WriteReportRow(
					array (
						$fields[0],
						$fields[1],
						$fields[2],
						$fields[3],
						$fields[4],
						AddApost($fields[5]),  // title
						AddApost($fields[6]),  // bbb
						$email,
						FormatPhone($fields[7]) . ' ' . $fields[8] . ' ' . $fields[9],
						$fields[11],
						$fields[12],
					)
				);
			}
		}
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>