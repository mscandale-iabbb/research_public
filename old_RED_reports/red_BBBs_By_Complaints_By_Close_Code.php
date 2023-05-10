<?php

/*
 * 11/03/14 MJS - changed die() to AbortREDReport()
 * 03/13/15 MJS - added close code 150
 * 06/05/15 MJS - removed close codes 111, 112, 121, and 122
 * 07/15/15 MJS - fixed bug in totals column for Not Processed Rate
 * 12/15/15 MJS - ensured Scam Tracker records won't appear, cleaned up code
 * 07/12/16 MJS - user's BBB shows in special format
 * 07/13/16 MJS - changed color of special format
 * 07/26/16 MJS - excluded complaints with blank close codes
 * 08/24/16 MJS - aligned column headers
 * 01/09/17 MJS - changed calls to define links and tabs
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->DefineLinks('main');
$page->write_header2();
$tabs = $page->DefineTabs('red');
$page->write_tabs($tabs);

$iDateFrom = CleanDate( GetInput('iDateFrom', '1/1/' . date('Y')) );
$iDateTo = CleanDate( GetInput('iDateTo', date( 'n/j/Y', GetEndOfLastMonth() ) ) );
$iAB = NoApost($_REQUEST['iAB']);
$iSortBy = NoApost($_POST['iSortBy']);
$iShowSource = $_POST['iShowSource'];
$iRegion = NoApost($_POST['iRegion']);
$iSalesCategory = NoApost($_POST['iSalesCategory']);
$iState = NoApost($_POST['iState']);


$input_form = new input_form($conn);
$input_form->AddDateField('iDateFrom','Closed dates',$iDateFrom);
$input_form->AddDateField('iDateTo',' to ',$iDateTo,'sameline');
$input_form->AddSelectField('iAB','AB status',$iAB, array('Both' => '', 'AB' => '1', 'Non-AB' => '0') );
$input_form->AddMultipleSelectField('iRegion', 'BBB region', $iRegion,
	$input_form->BuildBBBRegionsArray(), '', '', '', 'width:400px');
$input_form->AddMultipleSelectField('iSalesCategory', 'BBB sales category', $iSalesCategory,
	$input_form->BuildBBBSalesCategoriesArray(), '', '', '', 'width:100px');
$input_form->AddMultipleSelectField('iState', 'BBB state', $iState,
	$input_form->BuildStatesArray('bbbs'), '', '', '', 'width:350px');
$SortFields = array(
	'BBB city' => 'NicknameCity',
	'Sales category' => 'SalesCategory,NicknameCity',
	'BBB region' => 'Region,NicknameCity',
	'Resolved complaints' => 'Resolved',
	'Unresolved complaints' => 'Unresolved',
	'Resolution rate' => 'ResRate',
	'Not processed' => 'NotProcessed',
	'Not processed rate' => 'NotProcessedRate',
	'Closed 110' => 'Closed110',
	'Closed 120' => 'Closed120',
	'Closed 150' => 'Closed150',
	'Closed 200' => 'Closed200',
	'Closed 300' => 'Closed300',
	'Closed 400' => 'Closed400',
	'Closed 500' => 'Closed500',
	'Closed 600' => 'Closed600',
	'Closed 999' => 'Closed999',
	'Total' => 'Total',
);
$input_form->AddSortOptions($iSortBy, $SortFields);
$input_form->AddExportOptions();
$input_form->AddSourceOption();
$input_form->AddSubmitButton();
$input_form->Close();

if ($_POST) {
	$query = "SELECT
		BBB.BBBID,
		NickNameCity + ', ' + State,
		tblRegions.RegionAbbreviation,
		SalesCategory,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode IN ('110','150') and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Resolved,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode IN ('120','200') and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Unresolved,
		(
			cast (
				(select count(*) from BusinessComplaint c WITH (NOLOCK)
				inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
				where c.BBBID = BBB.BBBID AND
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.CloseCode IN ('110','150') and
				(
					('" . $iAB . "' = '') or
					('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
					('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) )
			as decimal(14,2) )
			/
			cast (
				(select count(*) from BusinessComplaint c WITH (NOLOCK)
				inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
				where c.BBBID = BBB.BBBID AND
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.CloseCode <= 200 and
				c.CloseCode is not null and c.CloseCode > 0 and
				(
					('" . $iAB . "' = '') or
					('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
					('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) )
			as decimal(14,2) )
		) as ResRate,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode IN ('500','600','999') and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as NotProcessed,
		(
			cast (
				(select count(*) from BusinessComplaint c WITH (NOLOCK)
				inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
				where c.BBBID = BBB.BBBID AND
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.CloseCode IN ('500','600','999') and
				(
					('" . $iAB . "' = '') or
					('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
					('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) )
			as decimal(14,2) )
			/
			cast (
				(select count(*) from BusinessComplaint c WITH (NOLOCK)
				inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
				where c.BBBID = BBB.BBBID AND
				c.DateClosed >= '" . $iDateFrom . "' and
				c.DateClosed <= '" . $iDateTo . "' and
				c.CloseCode IN ('110','120','150','200','300','500','600','999') and
				(
					('" . $iAB . "' = '') or
					('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
					('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) )
			as decimal(14,2) )
		) as NotProcessedRate,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '110' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed110,
		0 as Closed111,
		0 as Closed112,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '120' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed120,
		0 as Closed121,
		0 as Closed122,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '150' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed150,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '200' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed200,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '300' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed300,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '400' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed400,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '500' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed500,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '600' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed600,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode = '999' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Closed999,
		(
			select count(*) from BusinessComplaint c WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) ON b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
			where c.BBBID = BBB.BBBID AND
			c.DateClosed >= '" . $iDateFrom . "' and
			c.DateClosed <= '" . $iDateTo . "' and
			c.CloseCode in ('110','120','150','200','300','400','500','600','999') and
			c.ComplaintID not like 'scam%' and
			(
				('" . $iAB . "' = '') or
				('" . $iAB . "' = '1' and b.IsBBBAccredited = 1) or
				('" . $iAB . "' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
			)
		) as Total
		FROM BBB WITH (NOLOCK)
		inner join tblRegions WITH (NOLOCK) ON tblRegions.RegionCode = BBB.Region
		WHERE
		BBB.BBBBranchID = 0 and BBB.IsActive = '1' and
		(
			select count(*) from BusinessComplaint c2 WITH (NOLOCK)
			where c2.BBBID = BBB.BBBID AND
			c2.DateClosed >= '" . $iDateFrom . "' and
			c2.DateClosed <= '" . $iDateTo . "' and
			c2.CloseCode <= 300 and
			c2.ComplaintID not like 'scam%'
		) > 0 and
		('" . $iRegion . "' = '' or Region IN ('" . str_replace(",", "','", $iRegion) . "')) and
		('" . $iSalesCategory . "' = '' or
			SalesCategory IN ('" . str_replace(",", "','", $iSalesCategory) . "')) and
		('" . $iState . "' = '' or State IN ('" . str_replace(",", "','", $iState) . "'))
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
				array('BBB City', $SortFields['BBB city'], '', 'left'),
				array('Reg', $SortFields['BBB region'], '', 'left'),
				array('Sls Cat', $SortFields['Sales category'], '', 'right'),
				array('Resolved', $SortFields['Resolved complaints'], '', 'right'),
				array('Unres', $SortFields['Unresolved complaints'], '', 'right'),
				array('Res Rate', $SortFields['Resolution rate'], '', 'right'),
				array('Not Proc', $SortFields['Not processed'], '', 'right'),
				array('Not P Rate', $SortFields['Not processed rate'], '', 'right'),
				array('Closed 110', $SortFields['Closed 110'], '', 'right'),
				array('', ''),
				array('Closed 120', $SortFields['Closed 120'], '', 'right'),
				array('', ''),
				array('Closed 150', $SortFields['Closed 150'], '', 'right'),
				array('', ''),
				array('Closed 200', $SortFields['Closed 200'], '', 'right'),
				array('', ''),
				array('Closed 300', $SortFields['Closed 300'], '', 'right'),
				array('', ''),
				array('Closed 400', $SortFields['Closed 400'], '', 'right'),
				array('', ''),
				array('Closed 500', $SortFields['Closed 500'], '', 'right'),
				array('', ''),
				array('Closed 600', $SortFields['Closed 600'], '', 'right'),
				array('', ''),
				array('Closed 999', $SortFields['Closed 999'], '', 'right'),
				array('', ''),
				array('Total', $SortFields['Total'], '', 'right'),
			)
		);
		foreach ($rs as $k => $fields) {
			if ($fields[0] == $BBBID) $class = "bold darkgreen";
			else $class = "";
			$report->WriteReportRow(
				array (
					"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[0] .
						"><span class='{$class}'>" . AddApost($fields[1]) . "</span></a>",
					$fields[2],
					$fields[3],
					$fields[4],
					$fields[5],
					FormatPercentage($fields[6]),
					$fields[7],
					FormatPercentage($fields[8]),
					$fields[9],
					FormatPercentage($fields[9] / $fields[22]),
					$fields[12],
					FormatPercentage($fields[12] / $fields[22]),
					$fields[15],
					FormatPercentage($fields[15] / $fields[22]),
					$fields[16],
					FormatPercentage($fields[16] / $fields[22]),
					$fields[17],
					FormatPercentage($fields[17] / $fields[22]),
					$fields[18],
					FormatPercentage($fields[18] / $fields[22]),
					$fields[19],
					FormatPercentage($fields[19] / $fields[22]),
					$fields[20],
					FormatPercentage($fields[20] / $fields[22]),
					$fields[21],
					FormatPercentage($fields[21] / $fields[22]),
					$fields[22],
				),
				'',
				$class
			);
		}
		$report->WriteTotalsRow(
			array (
				'Totals',
				'',
				'',
				array_sum( get_array_column($rs, 4) ),
				array_sum( get_array_column($rs, 5) ),
				FormatPercentage(
					array_sum( get_array_column($rs, 4) ) /
					( array_sum( get_array_column($rs, 4) ) + array_sum( get_array_column($rs, 5) ) )
				),
				array_sum( get_array_column($rs, 7) ),
				FormatPercentage(
					array_sum( get_array_column($rs, 7) )
					/
					(
						array_sum( get_array_column($rs, 9) ) +
						array_sum( get_array_column($rs, 12) ) +
						array_sum( get_array_column($rs, 15) ) +
						array_sum( get_array_column($rs, 16) ) +
						array_sum( get_array_column($rs, 17) ) +
						array_sum( get_array_column($rs, 19) ) +
						array_sum( get_array_column($rs, 20) ) +
						array_sum( get_array_column($rs, 21) )
					)
				),
				array_sum( get_array_column($rs, 9) ),
				'',
				array_sum( get_array_column($rs, 12) ),
				'',
				array_sum( get_array_column($rs, 15) ),
				'',
				array_sum( get_array_column($rs, 16) ),
				'',
				array_sum( get_array_column($rs, 17) ),
				'',
				array_sum( get_array_column($rs, 18) ),
				'',
				array_sum( get_array_column($rs, 19) ),
				'',
				array_sum( get_array_column($rs, 20) ),
				'',
				array_sum( get_array_column($rs, 21) ),
				'',
				array_sum( get_array_column($rs, 22) ),
			)
		);
	}
	$report->Close();
	if ($iShowSource) {
		$report->WriteSource($query);
	}
}

$page->write_pagebottom();

?>