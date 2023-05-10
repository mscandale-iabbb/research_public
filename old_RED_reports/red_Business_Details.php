<?php

/*
 * 09/29/14 MJS - added fields for inquiry and complaint counts 
 * 03/13/15 MJS - added 150 close code, changed 122 from resolved to unresolved
 * 09/24/15 MJS - fixed layout
 * 12/16/15 MJS - ensured Scam Tracker records won't appear
 * 10/25/16 MJS - added systemwide AB, cleaned up code
 * 06/29/17 MJS - showed nonsearchable secondary locations, phones, and names in gray text
 * 09/18/17 MJS - added customer review fields, cleaned up code
 * 09/21/17 MJS - modified to show customer review fields even if 0s
 * 11/06/17 MJS - added fields service area, inc state, inc year, and description
 * 11/21/17 MJS - added field for custom text
 * 12/05/17 MJS - added more fields
 * 01/30/18 MJS - refactored for APICore
 * 02/06/18 MJS - fixed StarRatingScore column
 * 03/16/18 MJS - changed words Business Review to Business Profile
 * 03/18/19 MJS - added field for community member
 * 04/11/19 MJS - changed to use CORE for urls
 */

include '../intranet/common_includes.php';

$page = new page();
$page->write_pagetop($SITE_TITLE);
$page->write_header1($SITE_TITLE);
$page->write_header2();
$page->write_tabs($tabs);

$iBBBID = Numeric2($_GET['iBBBID']);
$iBusinessID = Numeric2($_GET['iBusinessID']);

function ShowField($label, $value) {
	echo "
		<tr>
		<td class='labelback'>
			{$label}
		<td class='table_cell'>
			{$value}
		";
}

echo "
	<div class='main_section roundedborder'>
	<table class='report_table'>
	";
if ($_GET) {
	if ($SETTINGS['CORE_OR_APICORE'] == 'CORE') {
		$table_Org = "CORE.dbo.datOrg";
		$table_Type = "CORE.dbo.refType";
		$table_CustomText = "CORE.dbo.datOrgCustomText";
		$table_License = "CORE.dbo.datOrgLicense";
		$column_LegalType = "LegalOrgTypeId";
		$column_OOB = "OutOfBusinessTypeId";
		$column_Bank = "BankrupcyTypeId";
		$column_Section = "SectionTypeId";
		$column_Status = "LicenseStatusTypeId";
		$column_LicenseIssued = "DateIssued";
		$column_LicenseExpired = "DateExpires";
		$column_Description = "Description";
		$column_Incorp = "IncorpStateCode";
		$column_IncorpYear = "o.IncorpYear";
		$column_Quote = "IsRequestQuoteActive";
		$column_Rating = "StarRating";
		$link_org_custom = "o.orgId = c.orgId";
		$link_org_license = "o.orgId = l.orgId";
	}
	else {
		$table_Org = "APICore.dbo.Organization";
		$table_Type = "APICore.dbo.srcType";
		$table_CustomText = "APICore.dbo.BusinessCustomText";
		$table_License = "APICore.dbo.LicenseDetail";
		$column_LegalType = "LegalOrganizationTypeId";
		$column_OOB = "OutOfBusinessStatusTypeId";
		$column_Bank = "BankruptcyTypeId";
		$column_Section = "CustomTextSectionId";
		$column_Status = "LicenseStatusId";
		$column_LicenseIssued = "IssueDate";
		$column_LicenseExpired = "ExpirationDate";
		$column_Description = "OrganizationDescription";
		$column_Incorp = "IncorporationStateCode";
		$column_IncorpYear = "YEAR(o.IncorporationDate)";
		$column_Quote = "IsRequestAQuoteActive";
		$column_Rating = "StarRatingScore";
		$link_org_custom = "o.BureauCode = c.BureauCode and o.SourceBusinessId = c.SourceBusinessId";
		$link_org_license = "o.BureauCode = l.BureauCode and o.SourceBusinessId = l.SourceBusinessId";
	}
	$query = "
		SELECT
			BBB.NickNameCity + ', ' + BBB.State,
			b.BusinessID,
			b.BusinessName,
			b.StreetAddress,
			b.StreetAddress2,
			b.City,
			b.StateProvince,
			b.PostalCode,
			b.MailingAddress,
			b.MailingAddress2,
			b.MailingCity,
			b.MailingStateProvince,
			b.MailingPostalCode,
			b.Phone,
			b.Email,
			b.Website,
			b.TOBid + ' ' + tblYPPA.yppa_text,
			b.DateBusinessStarted,
			Case When b.IsBBBAccredited = '1' then 'Yes' else 'No' end,
			Case When b.IsBillable = '1' then 'Yes' else 'No' end,
			Case When b.IsInBBBOnline = '1' then 'Yes' else 'No' end,
			b.BBBRatingGrade,
			b.NumberOfEmployees,
			b.NumberOfPartTimeEmployees,
			Case When b.IsHQ = '1' then 'Yes' else 'No' end,
			b.SizeOfBusiness,
			Case When b.IsCharity = '1' then 'Yes' else 'No' end,
			Case When b.IsReportable = '1' then 'Yes' else 'No' end,
			Case When b.PublishToCIBR = '1' then 'Yes' else 'No' end,
			b.ReportURL,
			b.ReportType,
			b.ReportingBBBID,
			b.ReportingBusinessID,
			b.CDWLastUpdate,
			b.CDWLastUser,
			legaltype.NameFull, /*b.BusinessStructure*/
			Case When sab.BBBID is not null then 'Yes' else 'No' end,
			o.ServingArea,
			o.{$column_Incorp},
			{$column_IncorpYear},
			o.{$column_Description},
			oobtype.NameFull,
			bankrupttype.NameFull,
			case when o.{$column_Quote} = '1' then 'Yes' else 'No' end,
			case when b.IsCommunityMember = '1' then 'Yes' else 'No' end,
			o.OrgID
		FROM Business b WITH (NOLOCK)
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join {$table_Org} o WITH (NOLOCK) on o.BureauCode = b.BBBID and o.SourceBusinessId = b.BusinessID
		left outer join tblYPPA WITH (NOLOCK) ON b.TOBID = tblYPPA.yppa_code
		left outer join SystemwideAB sab WITH (NOLOCK) on b.BBBID = sab.BBBID AND b.BusinessID = sab.BusinessID
		left outer join {$table_Type} legaltype WITH (NOLOCK) on legaltype.TypeId = o.{$column_LegalType}
		left outer join {$table_Type} oobtype WITH (NOLOCK) on oobtype.TypeId = o.{$column_OOB}
		left outer join {$table_Type} bankrupttype WITH (NOLOCK) on bankrupttype.TypeId = o.{$column_Bank}
		WHERE
			b.BBBID = '{$iBBBID}' and
			b.BusinessID = '{$iBusinessID}'
		";
	$rsraw = $conn->execute($query);
	$rs = $rsraw->GetArray();
	if (count($rs) > 0) {
		foreach ($rs as $k => $fields) {
			ShowField("BBB city", AddApost($fields[0]) );
			ShowField("Business ID", $fields[1]);
			ShowField("Business name", AddApost($fields[2]) );

			// additional names
			$subquery = "
				SELECT
					n.BusinessName,
					n.PublishToCIBR
				FROM BusinessName n WITH (NOLOCK) WHERE
					n.BBBID = '{$iBBBID}' and n.BusinessID = '{$iBusinessID}' and
					(n.IsPrimaryName = 0 or n.IsPrimaryName is null)
				ORDER BY n.BusinessName
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$names = "";
				foreach ($srs as $k => $row) {
					if ($srs->fields[1] != '1') {
						$names .= "<span class=gray01>";
					}
					$names .= $srs->fields[0] . "<br/>";
					if ($srs->fields[1] != '1') {
						$names .= "</span>";
					}
				}
				if ($srs->RecordCount() > 6) {
					$names = "<div style='height:100px; overflow:auto;'>" . $names . "</div>";
				}
				ShowField("Additional names", $names);
			}

			ShowField("Address", AddApost(
				$fields[3] . ' ' . $fields[4] . ' ' .  $fields[5] . ' ' . $fields[6] . ' ' . $fields[7]) );
			if ($fields[10] > '' && $fields[10] != $fields[5]) {
				ShowField("Mailing address", AddApost(
					$fields[8] . ' ' . $fields[9] . ' ' .
					$fields[10] . ' ' . $fields[11] . ' ' . $fields[12]) );
			}

			// secondary addresses
			$subquery = "
				SELECT 
					a.StreetAddress,
					a.StreetAddress2,
					a.City,
					a.StateProvince,
					a.PostalCode,
					a.PublishToCIBR
				FROM BusinessAddress a WITH (NOLOCK) WHERE
					a.BBBID = '{$iBBBID}' and a.BusinessID = '{$iBusinessID}' and
					(a.IsPrimaryAddress = 0 or a.IsPrimaryAddress is null)
					order by a.StreetAddress
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$addresses = "";
				foreach ($srs as $k => $row) {
					if ($srs->fields[5] != '1') {
						$addresses .= "<span class=gray01>";
					}
					$addresses .= $srs->fields[0] . " " . $srs->fields[1] . " " .
						$srs->fields[2] . " " . $srs->fields[3] . " " . $srs->fields[4] .
						"<br/>";
					if ($srs->fields[5] != '1') {
						$addresses .= "</span>";
					}
				}
				if ($srs->RecordCount() > 6) {
					$addresses = "<div style='height:100px; overflow:auto;'>" .
						$addresses . "</div>";
				}
				ShowField("Additional addresses", $addresses);
			}

			// contact persons
			$subquery = "
				SELECT
					p.Prefix,
					p.FirstName,
					p.MiddleName,
					p.LastName,
					p.Suffix,
					p.Title,
					p.Email
				FROM BusinessContact p WITH (NOLOCK) WHERE
					p.BBBID = '{$iBBBID}' and p.BusinessID = '{$iBusinessID}'
				ORDER BY p.LastName
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$persons = "";
				foreach ($srs as $k => $row) {
					$persons .= trim($srs->fields[0] . " " . $srs->fields[1] . " " .
						$srs->fields[2] . " " . $srs->fields[3] . " " . $srs->fields[4]);
					if ($srs->fields[5] > '') $persons .= ", " . $srs->fields[5];
					if ($srs->fields[6] > '') $persons .= "&nbsp; " .
						"<a href=mailto:" . $srs->fields[6] .  ">" .
						$srs->fields[6] . "</a>";
					$persons .= "<br/>";
				}
				if ($srs->RecordCount() > 6) {
					$persons = "<div style='height:100px; overflow:auto;'>" .
						$persons . "</div>";
				}
				ShowField("Persons", $persons);
			}

			ShowField("Phone", FormatPhone($fields[13]) );

			// additional phones
			$subquery = "
				SELECT 
					p.Phone,
					p.PublishToCIBR
				FROM BusinessPhone p WITH (NOLOCK) WHERE
					p.BBBID = '{$iBBBID}' and p.BusinessID = '{$iBusinessID}' and
					(p.IsPrimaryPhone = 0 or p.IsPrimaryPhone is null)
				ORDER BY p.Phone
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$phones = "";
				foreach ($srs as $k => $row) {
					if ($srs->fields[1] != '1') {
						$phones .= "<span class=gray01>";
					}
					$phones .= FormatPhone($srs->fields[0]) .  "<br/>";
					if ($srs->fields[1] != '1') {
						$phones .= "</span>";
					}
				}
				if ($srs->RecordCount() > 6) {
					$phones = "<div style='height:100px; overflow:auto;'>" .
						$phones . "</div>";
				}
				ShowField("Additional phones", $phones);
			}

			ShowField("Email", $fields[14]);

			// additional emails
			$subquery = "
				SELECT 
					e.Email
				FROM BusinessEmail e WITH (NOLOCK) WHERE
					e.BBBID = '{$iBBBID}' and e.BusinessID = '{$iBusinessID}' and
					(e.IsPrimaryEmail = 0 or e.IsPrimaryEmail is null)
				ORDER BY e.Email
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$emails = "";
				foreach ($srs as $k => $row) {
					$emails .= $srs->fields[0] .  "<br/>";
				}
				if ($srs->RecordCount() > 6) {
					$emails = "<div style='height:100px; overflow:auto;'>" .
						$emails . "</div>";
				}
				ShowField("Additional emails", $emails);
			}

			ShowField("Website", $fields[15]);

			// additional urls
			/*
			$subquery = "
				SELECT 
					u.URL
				FROM BusinessURL u WITH (NOLOCK) WHERE
					u.BBBID = '{$iBBBID}' and u.BusinessID = '{$iBusinessID}' and
					(u.IsPrimaryURL = 0 or u.IsPrimaryURL is null)
				ORDER BY u.URL
				";
			*/
			$oOrgID = $fields[45];
			$subquery = "
				SELECT 
					u.URL
				FROM CORE.dbo.lnkOrgURL lu WITH (NOLOCK)
				INNER JOIN CORE.dbo.atrURL u on u.URLID = lu.URLID and u.URL is not null
				WHERE
					lu.OrgID = '{$oOrgID}' and lu.URLTypeID not in ('717','718','719','721','738','739') and
					(lu.isPrimary = 0 or lu.isPrimary is null)
				ORDER BY u.URL
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$urls = "";
				foreach ($srs as $k => $row) {
					$urls .= $srs->fields[0] .  "<br/>";
				}
				if ($srs->RecordCount() > 6) {
					$urls = "<div style='height:100px; overflow:auto;'>" .
						$urls . "</div>";
				}
				ShowField("Additional URLs", $urls);
			}

			ShowField("TOB", $fields[16]);

			// additional tobs
			$subquery = "
				SELECT 
					t.TOBid + ' ' + tblYPPA.yppa_text
				FROM BusinessTOBID t WITH (NOLOCK)
				INNER JOIN tblYPPA WITH (NOLOCK) ON t.TOBID = tblYPPA.yppa_code
				WHERE
					t.BBBID = '{$iBBBID}' and t.BusinessID = '{$iBusinessID}' and
					(t.IsPrimaryTOBID = 0 or t.IsPrimaryTOBID is null)
				ORDER BY t.TOBID
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				$tobs = "";
				foreach ($srs as $k => $row) {
					$tobs .= $srs->fields[0] . "<br/>";
				}
				if ($srs->RecordCount() > 6) {
					$tobs = "<div style='height:100px; overflow:auto;'>" .
						$tobs . "</div>";
				}
				ShowField("Additional TOBs", $tobs);
			}

			// custom text fields
			$subquery = "
				select
					r.NameFull, c.CustomText
				from {$table_CustomText} c WITH (NOLOCK)
				inner join {$table_Org} o WITH (NOLOCK) on {$link_org_custom}
				inner join CDW.dbo.Business b WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				inner join {$table_Type} r WITH (NOLOCK) on r.TypeId = c.{$column_Section}
				where
					b.BBBID = '{$iBBBID}' and b.BusinessID = '{$iBusinessID}' and
					r.NameFull != 'Overview'
				ORDER BY r.NameFull
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				foreach ($srs as $k => $row) {
					//$customs .= $srs->fields[0] . "<br/>";
					ShowField($srs->fields[0], $srs->fields[1]);
				}
			}

			// license information
			$subquery = "
				select
					r.NameFull,
					l.LicenseNumber,
					l.{$column_LicenseIssued},
					l.{$column_LicenseExpired}
				from {$table_License} l WITH (NOLOCK)
				inner join {$table_Org} o WITH (NOLOCK) on {$link_org_license}
				inner join CDW.dbo.Business b WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessId
				left outer join {$table_Type} r WITH (NOLOCK) on r.TypeId = l.{$column_Status}
				where
					b.BBBID = '{$iBBBID}' and b.BusinessID = '{$iBusinessID}'
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				foreach ($srs as $k => $row) {
					ShowField("License status", $srs->fields[0]);
					ShowField("License number", $srs->fields[1]);
					ShowField("License issued", FormatDate($srs->fields[2]));
					ShowField("License expires", FormatDate($srs->fields[3]));
				}
			}

			ShowField("Date business started", FormatDate($fields[17]) );
			ShowField("AB", $fields[18]);
			ShowField("Billable", $fields[19]);
			ShowField("Systemwide AB", $fields[36]);
			ShowField("Community member", $fields[44]);
			ShowField("Dynamic seal", $fields[20]);
			ShowField("Request-A-Quote", $fields[43]);
			ShowField("Rating", $fields[21]);
			ShowField("Operational status", $fields[41]);
			ShowField("Bankruptcy status", $fields[42]);
			ShowField("Employees", $fields[22]);
			if ($fields[23] > 0) {
				echo "full-time";
				echo "&nbsp; &nbsp;";
				echo $fields[23];
				echo "part-time";
			}
			ShowField("Legal type", $fields[35]);
			ShowField("Service area", $fields[37]);
			ShowField("Incorporation state", $fields[38]);
			ShowField("Incorporation year", $fields[39]);
			ShowField("Product/service", $fields[40]);
			ShowField("HQ", $fields[24]);
			ShowField("Size", $fields[25]);
			ShowField("Charity", $fields[26]);
			ShowField("Reportable", $fields[27]);
			if ($fields[29] > '') {
				echo "&nbsp; &nbsp;";
				if (substr($fields[29],0,4) != 'http') {
					$protocol = "https://";
				}
				echo "<a target=_new href=" . $protocol . $fields[29] . ">";
				echo "Business Profile";
				echo "</a>";
			}
			ShowField("Searchable", $fields[28]);
			ShowField("Report type", $fields[30]);
			if ($fields[31] > '') {
				echo "&nbsp; &nbsp; &nbsp;";
				echo "claimed by BBB " . $fields[31];
				echo " ";
				echo "for business ID " . $fields[32];
			}
			ShowField("Record last updated", FormatDate($fields[33]) . ' ' . $fields[34]);

			// inquiries
			$subquery = "
				select sum(i.CountTotal)
				from BusinessInquiry i WITH (NOLOCK) where
					i.BBBID = '{$iBBBID}' and i.BusinessID = '{$iBusinessID}' and
					i.DateOfInquiry >= GETDATE() - 1095 and i.DateOfInquiry <= GETDATE()
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				ShowField("Inquiries, past 3 years", AddComma($srs->fields[0]));
			}

			// complaints 3 years
			$subquery = "
				select count(*)
				from BusinessComplaint c WITH (NOLOCK) where
					c.BBBID = '{$iBBBID}' and c.BusinessID = '{$iBusinessID}' and
					c.DateClosed >= GETDATE() - 1095 and c.DateClosed <= GETDATE() and
					CloseCode IN ('110','111','112','120','121','122','150','200','300') and
					c.ComplaintID not like 'scam%'
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				ShowField("Complaints reported, past 3 years", AddComma($srs->fields[0]));
			}

			// complaints resolved 3 years
			$subquery = "
				select count(*)
				from BusinessComplaint c WITH (NOLOCK) where
					c.BBBID = '{$iBBBID}' and c.BusinessID = '{$iBusinessID}' and
					c.DateClosed >= GETDATE() - 1095 and c.DateClosed <= GETDATE() and
					CloseCode IN ('110','111','112','121', /*'122'*/ '150') and
					c.ComplaintID not like 'scam%'
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				ShowField("Complaints resolved, past 3 years", AddComma($srs->fields[0]));
			}

			// complaints unresolved 3 years
			$subquery = "
				select count(*)
				from BusinessComplaint c WITH (NOLOCK) where
					c.BBBID = '{$iBBBID}' and c.BusinessID = '{$iBusinessID}' and
					c.DateClosed >= GETDATE() - 1095 and c.DateClosed <= GETDATE() and
					CloseCode IN ('120','122','200') and
					c.ComplaintID not like 'scam%'
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				ShowField("Complaints unresolved, past 3 years", AddComma($srs->fields[0]));
			}

			// complaints 1 year
			$subquery = "
				select count(*)
				from BusinessComplaint c WITH (NOLOCK) where
					c.BBBID = '{$iBBBID}' and c.BusinessID = '{$iBusinessID}' and
					c.DateClosed >= GETDATE() - 365 and c.DateClosed <= GETDATE() and
					CloseCode IN ('110','111','112','120','121','122','150','200','300') and
					c.ComplaintID not like 'scam%'
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				ShowField("Complaints reported, past 1 year", AddComma($srs->fields[0]));
			}

			// complaints resolved 1 year
			$subquery = "
				select count(*)
				from BusinessComplaint c WITH (NOLOCK) where
					c.BBBID = '{$iBBBID}' and c.BusinessID = '{$iBusinessID}' and
					c.DateClosed >= GETDATE() - 365 and c.DateClosed <= GETDATE() and
					CloseCode IN ('110','111','112','121', /*'122'*/ '150') and
					c.ComplaintID not like 'scam%'
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				ShowField("Complaints resolved, past 3 years", AddComma($srs->fields[0]));
			}

			// complaints unresolved 1 year
			$subquery = "
				select count(*)
				from BusinessComplaint c WITH (NOLOCK) where
					c.BBBID = '{$iBBBID}' and c.BusinessID = '{$iBusinessID}' and
					c.DateClosed >= GETDATE() - 365 and c.DateClosed <= GETDATE() and
					CloseCode IN ('120','122','200') and
					c.ComplaintID not like 'scam%'
				";
			$srs = $conn->execute($subquery);
			if ($srs->RecordCount() > 0) {
				ShowField("Complaints unresolved, past 3 years", AddComma($srs->fields[0]));
			}

			// customer reviews
			$subquery = "
				SELECT
					(TotalPositive + TotalNegative + TotalNeutral),
					(TotalPositive), (TotalNegative), (TotalNeutral),
					{$column_Rating}
				FROM {$table_Org} o WITH (NOLOCK)
				INNER JOIN Business b WITH (NOLOCK) on b.BBBID = o.BureauCode and b.BusinessID = o.SourceBusinessID
				WHERE
					b.BBBID = '{$iBBBID}' and b.BusinessID = '{$iBusinessID}'
				";
			$srs = $conn->execute($subquery);
			$total_cr = $srs->fields[0];
			$pos_cr = $srs->fields[1];
			$neg_cr = $srs->fields[2];
			$neut_cr = $srs->fields[3];
			$star_rating = $srs->fields[4];
			if ($total_cr == '') {
				$total_cr = 0;
			}
			if ($pos_cr == '') {
				$pos_cr = 0;
			}
			if ($neg_cr == '') {
				$neg_cr = 0;
			}
			if ($neut_cr == '') {
				$neut_cr = 0;
			}
			if ($star_rating == '') {
				$star_rating = 0.00;
			}
			if ($srs->RecordCount() >= 0) {
				ShowField("Customer Reviews total, as of today", AddComma($total_cr));
				ShowField("Customer Reviews positive, as of today", AddComma($pos_cr));
				ShowField("Customer Reviews negative, as of today", AddComma($neg_cr));
				ShowField("Customer Reviews neutral, as of today", AddComma($neut_cr));
				ShowField("Composite score, as of today", AddComma($star_rating));
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