<?php

/*
 * 11/18/15 MJS - new file, split from original
 * 11/19/15 MJS - used SQL prepared statement for query
 * 11/19/15 MJS - added options for batching
 * 11/19/15 MJS - refactored $input into class property
 * 11/20/15 MJS - added code for system shell mode
 * 11/20/15 MJS - refactored $_POST and $_REQUEST into $this->input for shell and Curl modes
 * 08/25/16 MJS - align column headers
 */

class red_Top_Businesses_By_Inquiries {

	private $conn;
	private $input;

	function __construct($conn, $input) {
		$this->conn = $conn;
		$this->input = $input;
	}

	function FetchData() {

		if ($this->input['iBatch']) {
			return array();
		}

		/*
		$query = "SELECT TOP {$input['iMaxRecs']}
				sum(CountTotal) as Inquiries,
				b.BBBID,
				b.BusinessID,
				REPLACE(b.BusinessName,'&#39;','''') as BusinessName,
				BBB.NickNameCity + ', ' + BBB.State,
				b.TOBID + ' ' + tblYPPA.yppa_text,
				Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
				b.BBBRatingGrade,
				b.SizeOfBusiness
			from BusinessInquiry i WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) on b.BBBID = i.BBBID and b.BusinessID = i.BusinessID
			inner join BBB WITH (NOLOCK) on i.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
			inner join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
			left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
			left outer join tblSizesOfBusiness s WITH (NOLOCK) ON s.SizeOfBusiness = b.SizeOfBusiness
			WHERE
				i.DateOfInquiry >= '{$input['iDateFrom']}' and i.DateOfInquiry <= '{$input['iDateTo']}' and
				('{$input['iBBBID']}' = '' or i.BBBID = '{$input['iBBBID']}') and
				('{$input['iTOB']}' = '' or tblYPPA.yppa_text like '%{$input['iTOB']}%') and
				(
					('{$input['iAB']}' = '') or
					('{$input['iAB']}' = '1' and b.IsBBBAccredited = 1) or
					('{$input['iAB']}' = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) and
				('{$input['iRating']}' = '' or b.BBBRatingGrade IN ('" . str_replace(",", "','", $input['iRating']) . "')) and
				('{$input['iSize']}' = '' or b.SizeOfBusiness IN ('" . str_replace(",", "','", $input['iSize']) . "')) and
				('{$input['iCountry']}' = '' or BBB.Country = '{$input['iCountry']}')
			GROUP BY b.BBBID, b.BusinessID, BBB.NickNameCity, BBB.State, b.BusinessName,
				b.TOBID, tblYPPA.yppa_text, b.IsBBBAccredited, b.BBBRatingGrade, r.BBBRatingSortOrder,
				b.SizeOfBusiness, s.SizeOfBusinessSortOrder
			";
		*/
		$query = "SELECT TOP {$this->input['iMaxRecs']}
				sum(CountTotal) as Inquiries,
				b.BBBID,
				b.BusinessID,
				REPLACE(b.BusinessName,'&#39;','''') as BusinessName,
				BBB.NickNameCity + ', ' + BBB.State,
				b.TOBID + ' ' + tblYPPA.yppa_text,
				Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end as AB,
				b.BBBRatingGrade,
				b.SizeOfBusiness
			from BusinessInquiry i WITH (NOLOCK)
			inner join Business b WITH (NOLOCK) on b.BBBID = i.BBBID and b.BusinessID = i.BusinessID
			inner join BBB WITH (NOLOCK) on i.BBBID = BBB.BBBID AND BBB.BBBBranchID = '0'
			inner join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
			left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
			left outer join tblSizesOfBusiness s WITH (NOLOCK) ON s.SizeOfBusiness = b.SizeOfBusiness
			WHERE
				i.DateOfInquiry >= ? and i.DateOfInquiry <= ? and
				(? = '' or i.BBBID = ?) and
				(? = '' or tblYPPA.yppa_text like ?) and
				(
					(? = '') or
					(? = '1' and b.IsBBBAccredited = 1) or
					(? = '0' and (b.IsBBBAccredited = 0 or b.IsBBBAccredited is null))
				) and
				(? = '' or b.BBBRatingGrade IN ('" . str_replace(",", "','", $this->input['iRating']) . "')) and
				(? = '' or b.SizeOfBusiness IN ('" . str_replace(",", "','", $this->input['iSize']) . "')) and
				(? = '' or BBB.Country = ?)
			GROUP BY b.BBBID, b.BusinessID, BBB.NickNameCity, BBB.State, b.BusinessName,
				b.TOBID, tblYPPA.yppa_text, b.IsBBBAccredited, b.BBBRatingGrade, r.BBBRatingSortOrder,
				b.SizeOfBusiness, s.SizeOfBusinessSortOrder
				";
		if ($this->input['iSortBy']) $query .= " ORDER BY " . $this->input['iSortBy'];
		$queryparams = array(
			$this->input['iDateFrom'], $this->input['iDateTo'], $this->input['iBBBID'], $this->input['iBBBID'],
			$this->input['iTOB'], '%' . $this->input['iTOB'] . '%', $this->input['iAB'], $this->input['iAB'], $this->input['iAB'],
			$this->input['iRating'], $this->input['iSize'], $this->input['iCountry'], $this->input['iCountry'],
		);

		if ($this->input['use_saved'] == '1') {
			$rs = $_SESSION['rs'];
		}
		else {
			// system shell and Curl can't use Prepared Statements, merge params back into statement instead
			if ($this->input['shell'] || $_SERVER['HTTP_COOKIE'] == 'ALLOW_SYSTEM_CALLS') {
				$query = AddSQLParams($query, $queryparams);
				$rsraw = $this->conn->execute($query);
				if (! $rsraw) AbortREDReport($query);
			}
			// web client uses Prepared Statements
			else {
				$query = $this->conn->prepare($query);
				$rsraw = $this->conn->execute($query, $queryparams);
				if (! $rsraw) AbortREDReport($query[0] . explode(' ',$queryparams));
			}
			$rs = $rsraw->GetArray();
			$_SESSION['rs'] = $rs;
		}

		return $rs;
	}
 
	function WriteReport($rs) {

		$report = new report( $this->conn, count($rs) );
		if ($this->input['iBatch']) {
			$report->Batch($this->input);
			return;
		}
		$report->Open();
		if (count($rs) > 0) {
			$report->WriteHeaderRow(
				array (
					array('#', '', '', 'right'),
					array('Inquiries', $SortFields['Inquiries'], '', 'right'),
					array('Business Name', $SortFields['Business name'], '', 'left'),
					array('BBB', $SortFields['BBB city'], '', 'left'),
					array('TOB', $SortFields['TOB code'], '', 'left'),
					array('AB', $SortFields['AB'], '', 'left'),
					array('Rating', $SortFields['Rating'], '', 'left'),
					array('Size', $SortFields['Size'], '', 'left')
				)
			);
			$xcount = 0;

			$iPageNumber = $this->input['iPageNumber'];
			$iPageSize = $this->input['iPageSize'];
			if ($this->input['output_type']) $iPageSize = count($rs);
			$TotalPages = round(count($rs) / $iPageSize, 0);
			if (count($rs) % $iPageSize > 0) {
				$TotalPages++;
			}
			if ($iPageNumber > $TotalPages) $iPageNumber = 1;
	
			foreach ($rs as $k => $fields) {
				$xcount++;
	
				if ($xcount < ( ( ($iPageNumber - 1) * $iPageSize) + 1 ) ) continue;
				if ($xcount > $iPageNumber * $iPageSize) break;
	
				$report->WriteReportRow(
					array (
						$xcount,
						$fields[0],
						"<a target=detail href=red_Business_Details.php?iBBBID=" . $fields[1] .
							"&iBusinessID=" . $fields[2] .  ">" . AddApost($fields[3]) . "</a>",
						"<a target=detail href=red_BBB_Details.php?iBBBID=" . $fields[1] .
							">" . AddApost($fields[4]) . "</a>",
						AddApost($fields[5]),
						$fields[6],
						$fields[7],
						$fields[8]
						)
					);
			}
			$report->WriteTotalsRow(
				array (
					'Total',
					array_sum( get_array_column($rs, 0) ),
					'',
					''
					)
				);
		}
		$report->Close();
		if ($iShowSource) {
			$report->WriteSource($query);
		}
	}
}

?>