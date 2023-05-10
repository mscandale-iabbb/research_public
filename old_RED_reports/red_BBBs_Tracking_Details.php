<?php

/*
 * 03/20/17 MJS - new file
 * 03/21/17 MJS - added fields for payment type and new ab
 * 09/07/17 MJS - modified to show all records (not just paid in full) and added column
 * 11/21/17 MJS - exclude records with payment type IP
 * 11/21/17 MJS - added option for paid/unpaid/both
 * 01/30/18 MJS - refactored for APICore
 * 02/06/18 MJS - fixed bug in APICore query
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);

$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs();

$iBBBID = Numeric2($_REQUEST['iBBBID']);
$iMonthTo = Numeric2($_REQUEST['iMonthTo']);
$iYearTo = Numeric2($_REQUEST['iYearTo']);
$iType = NoApost($_POST['iType']);
if ($iType == 'Yes') {
	$iTypeYesChecked = 'Checked';
}
else if ($iType == 'No') {
	$iTypeNoChecked = 'Checked';
}
else {
	$iTypeBothChecked = 'Checked';
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

echo "
	<div class='main_section roundedborder'>

	<form id=form1 method=post action=red_BBBs_Tracking_Details.php>
	<table class='report_table'>
	
	<tr>
	<td class='labelback' width=15%>
	Paid
	<td class='table_cell'>
	<input type=radio id=iTypeYes name=iType value='Yes' {$iTypeYesChecked} />Paid &nbsp;
	<input type=radio id=iTypeNo name=iType value='No' {$iTypeNoChecked} />Unpaid &nbsp;
	<input type=radio id=iTypeBoth name=iType value='' {$iTypeBothChecked} />Both &nbsp;

	<tr>
	<td class='labelback' width=15%>
	&nbsp;
	<td class='table_cell'>
	<input type=hidden id=iBBBID name=iBBBID value='{$iBBBID}' />
	<input type=hidden id=iMonthTo name=iMonthTo value='{$iMonthTo}' />
	<input type=hidden id=iYearTo name=iYearTo value='{$iYearTo}' />
	<input type=submit class='submit_button_small' style='color:white' value='   Search   ' />

	<tr>
	<td colspan=2>
	<hr size=30 />

	</table>
	</form>	

	<table class='report_table'>
	<tr>
	<td class='labelback' width=40%>
	Business Name
	<td class='labelback' width=20%>
	Paid In Full
	<td class='labelback' width=20%>
	Plan Type
	<td class='labelback' width=20%>
	New AB
	";
if ($iMonthTo) {
	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_BusinessPayment = "CORE.dbo.datOrgBusinessPayment";
		$column_BBBID = "BBBID";
		$column_BusinessID = "BusinessID";
	}
	else {
		$table_BusinessPayment = "APICore.dbo.BusinessPayment";
		$column_BBBID = "BureauCode";
		$column_BusinessID = "SourceBusinessID";
	}
	$query = "
		declare @dateto date;
		set @dateto = CONVERT(datetime,
			'{$iMonthTo}' + '/1/' + '{$iYearTo}');

		/* set Date To to the last day of month */
		IF MONTH(@dateto) < 12 BEGIN
			SET @dateto = CONVERT(datetime,
				CAST(MONTH(@dateto) + 1 as varchar(2)) + '/' + '1/' + CAST(YEAR(@dateto) as varchar(4))
				) - 1;
		END
		IF MONTH(@dateto) = 12 BEGIN
			SET @dateto = CONVERT(datetime, '12/31/' + CAST(YEAR(@dateto) as varchar(4))
				) - 1;
		END
		
		/* subtract a month if running before the 6th day of the month */
		if DAY(GETDATE()) < 6 AND DATEDIFF(day,GETDATE(),@dateto) < 6 SET @dateto = DATEADD(month,-1,@dateto);
		
		/* don't allow future dates */
		IF @dateto > GETDATE() SET @dateto = GETDATE();
		
		declare @monthfrom int;
		declare @yearfrom int;
		SET @monthfrom = MONTH(@dateto);
		SET @yearfrom = YEAR(@dateto);
		declare @datefrom date;
		set @datefrom = CONVERT(datetime,
			cast(@monthfrom as varchar(2)) + '/1/' +
			cast(@yearfrom as varchar(4))	);

		SELECT
			b.BusinessName,
			p.PlanType,
			case when p.IsNewAB = '1' then 'Yes' else 'No' end,
			case when p.IsPaidInFull = '1' then 'Yes' else 'No' end
		FROM {$table_BusinessPayment} p WITH (NOLOCK)
		INNER JOIN CDW.dbo.Business b WITH (NOLOCK) ON b.BBBID = p.{$column_BBBID} and b.BusinessID = p.{$column_BusinessID}
		WHERE
			p.{$column_BBBID} = '{$iBBBID}' and
			p.DateOfBilling >= @datefrom and p.DateOfBilling <= @dateto and
			p.PlanType != 'IP' and
			(
				'{$iType}' = '' or
				('{$iType}' = 'Yes' and p.IsPaidInFull = '1') or
				('{$iType}' = 'No' and (p.IsPaidInFull = '0' or p.IsPaidInFull is null))
			)
		ORDER BY b.BusinessName
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			echo "
				<tr>
				<td class='table_cell' width=40%>
				{$fields[0]}
				<td class='table_cell' width=20%>
				{$fields[3]}
				<td class='table_cell' width=20%>
				{$fields[1]}
				<td class='table_cell' width=20%>
				{$fields[2]}
				";
		}
	}
}
echo "
	<tr><td colspan=4 class='column_header thickpadding center'>
	<a class='submit_button' style='color:#FFFFFF' href='javascript:window.close();'>Close Tab</a>
	</table>
	</div>
	";

?>