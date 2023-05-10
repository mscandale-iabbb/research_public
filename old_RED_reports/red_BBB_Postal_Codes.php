<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 06/02/15 MJS - added field for Country
 * 03/17/16 MJS - added columns for establishments, persons, and households
 * 03/24/16 MJS - removed sort option for BBB city
 * 06/28/16 MJS - fixed bug with wrong country's postal code showing
 * 08/25/16 MJS - aligned column headers
 * 01/09/17 MJS - changed calls to define links and tabs
 * 07/26/17 MJS - changed sql for new schema
 * 01/30/18 MJS - refactored for APICore
 * 04/02/18 MJS - fixed schema ZipCode to PostalCodes
 * 01/04/19 MJS - changed to latest establishments field
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iBBBID = NoApost($_POST['iBBBID']);
if ($iBBBID == '' && $BBBID != '2000') $iBBBID = $BBBID;
else if ($iBBBID == '' && $BBBID == '2000') $iBBBID = '1066';
$iZip = NoApost($_POST['iZip']);
$iCountry = NoApost($_POST['iCountry']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];


$input_form = new input_form($conn);
$input_form->AddSelectField('iBBBID', 'BBB city', $iBBBID, $input_form->BuildBBBCitiesArray('', '') );
//$input_form->AddTextField('iCity', 'City', $iCity, "width:125px;");
//$input_form->AddTextField('iState', 'State', $iState, "width:25px;");
$input_form->AddTextField('iZip', 'Postal code', $iZip, "width:50px;");
$input_form->AddSelectField('iCountry','Country',$iCountry,
        array('' => '', 'United States' => 'USA', 'Canada' => 'CAN', 'Mexico' => 'MEX') );
$SortFields = array(
	'Postal code' => 'bz.ZipCode',
	'Estabs' => 'z.[2013Establishments]',
	'Persons' => 'z.[2013Persons]',
	'Households' => 'z.[2013Households]'
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {

	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_CityZip = "CORE.dbo.refcityzip";
		$table_BureauZip = "CORE.dbo.lnkBureauZip";
		$table_Bureau = "CORE.dbo.datBureau";
		$table_zipcode = "ZIPCode";
	}
	else {
		$table_CityZip = "APICore.dbo.srcCityZip";
		$table_BureauZip = "APICore.dbo.BureauZip";
		$table_Bureau = "APICore.dbo.Bureau";
		$table_zipcode = "PostalCodes";
	}
	$query = "
		SELECT TOP 395000
			bz.ZIPCode,
			(
				select top 1 cz.City + ', ' + cz.StateCode from
				{$table_CityZip} cz WITH (NOLOCK)
				where cz.{$table_zipcode} = bz.ZipCode and cz.CountryCode = bz.CountryCode
			),
			'BBB ' + BBB.NickNameCity + ', ' + BBB.State,
			z.[2016Establishments],
			z.[2013Persons],
			z.[2013Households]
		FROM {$table_BureauZip} bz WITH (NOLOCK) 
		INNER JOIN {$table_Bureau} bb WITH (NOLOCK) on bb.bureauid = bz.bureauid and bb.BureauBranchId = '0'
		INNER JOIN BBB WITH (NOLOCK) ON BBB.BBBBranchID = '0' and BBB.BBBID = bb.BureauCode
		LEFT OUTER JOIN {$table_CityZip} cz WITH (NOLOCK) ON cz.{$table_zipcode} = bz.ZIPCode AND cz.CountryCode = bz.CountryCode
		LEFT OUTER JOIN ZipEstablishments z WITH (NOLOCK) ON
			z.Zipcode = bz.ZipCode and bz.CountryCode = 'USA'
		WHERE
			LEN(bz.ZipCode) > 0 AND
			('{$iCountry}' = '' or bz.CountryCode = '{$iCountry}') and
			(
				/* zip search */
				(bz.ZipCode = '{$iZip}' and bz.ZipCode > '') OR
				/* bbb search */
				(
					('{$iZip}' = '') AND
					('{$iBBBID}' = '' OR BBB.BBBID = '{$iBBBID}')
				)
			)
		GROUP BY bz.ZipCode, bz.CountryCode, 'BBB ' + BBB.NickNameCity + ', ' + BBB.State,
			z.[2016Establishments], z.[2013Persons], z.[2013Households]
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
				array('Postal Code', $SortFields['Postal code'], '', 'left'),
				array('City', $SortFields['City'], '', 'left'),
				array('BBB', '', '', 'left'),
				array('Establishments 2016', $SortFields['Estabs'], '', 'right'),
				array('Persons 2013', $SortFields['Persons'], '', 'right'),
				array('Households 2013', $SortFields['Households'], '', 'right'),
			)
		);
		foreach ($rs as $k => $fields) {
			$report->WriteReportRow(
				array (
					$fields[0],
					AddApost($fields[1]),
					AddApost($fields[2]),
					$fields[3],
					$fields[4],
					$fields[5],
				)
			);
		}
	}
	$report->WriteTotalsRow(
		array (
			'Totals',
			'',
			'',
			array_sum( get_array_column($rs, 3) ),
			array_sum( get_array_column($rs, 4) ),
			array_sum( get_array_column($rs, 5) ),
		)
	);
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>
