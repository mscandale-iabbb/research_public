SELECT
	s.scam_id AS 'Scam ID',
	tblBBB.bureau_code + ' - ' + tblBBB.location AS 'BBB',
	st.scam_status AS 'Status',
	replace(replace(replace(replace(t.scam_type,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Type',
	i.scam_type_other AS 'Scam Type Other',
	replace(replace(replace(replace(via.scam_via,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Contact Method',
	replace(replace(replace(replace(pm.payment_method,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Payment Method',
	replace(replace(replace(replace(s.scam_name,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Name',
	replace(replace(replace(replace(cast(s.description AS VARCHAR(MAX)),char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Description',
	replace(replace(replace(replace(cast(s.keywords AS VARCHAR(MAX)),char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Keywords',
	sc.business_name AS 'Business Name',
	cast(s.CreatedOn as date) AS 'Created',
	i.dollar_value AS 'Amount Lost',
	i.dollar_attempt AS 'Amount Attempted',
	i.ipaddress AS 'IP Address',
	i.latitude AS 'Latitude',
	i.longitude AS 'Longitude',
	v.name_first AS 'Victim First Name',
	v.name_last AS 'Victim Last Name',
	v.phone AS 'Victim Phone',
	v.email AS 'Victim Email',
	a.agerange AS 'Victim Age Range',
	g.description AS 'Victim Gender',
	v.city AS 'Victim City',
	v.state AS 'Victim State',
	v.zip AS 'Victim Zip',
	v.country AS 'Victim Country',
	v.allowMedia AS 'Allow Media',
	v.isVictim AS 'Is Victim',
	v.isIndividual AS 'Individual',
	v.isActiveDuty AS 'Active Duty Military',
	v.isStudent AS 'Student',
	sc.address_1 AS 'Business Address',
	sc.address_2 AS 'Business Address 2',
	sc.city AS 'Business City',
	sc.state AS 'Business State',
	sc.zip AS 'Business Zip',
	sc.country AS 'Business Country',
	sc.phone AS 'Business Phone',
	sc.email AS 'Business Email',
	sc.url AS 'Business URL'
FROM BlueScam.dbo.tblScam s
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry i ON i.scam_id = s.scam_id
LEFT JOIN BlueScam.dbo.tblSCAM_Victim v ON v.victim_id = i.victim_id
LEFT JOIN BlueScam.dbo.tblSCAM_Scammer sc ON sc.scammer_id = i.scammer_id
LEFT JOIN BlueScam.dbo.tblSCAM_Type t ON t.scam_type_id = s.scam_type_id
LEFT JOIN BlueScam.dbo.tblBBB ON tblBBB.bbbid = i.bbbid
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry_Payment p ON p.scam_inquiry_id = i.pk_id
LEFT JOIN BlueScam.dbo.tblSCAM_PaymentMethod pm ON pm.payment_method_id = p.payment_method_id
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry_Via iv ON iv.scam_inquiry_id = i.pk_id
LEFT JOIN BlueScam.dbo.tblSCAM_Via via ON via.scam_via_id = iv.scam_via_id
LEFT JOIN BlueScam.dbo.tblSCAM_Status st ON st.status_id = s.status_id
LEFT JOIN BlueScam.dbo.tblSCAM_Victim_AgeRange a ON a.agerange_id = v.age
LEFT JOIN BlueScam.dbo.tblSCAM_Victim_Gender g ON g.gender = v.gender
WHERE
	s.status_id = 2 and
	--YEAR(s.CreatedOn) >= '2022' and
	--i.dollar_value > 0 and
	--v.allowMedia = '1'

	t.scam_type IN ('Online Purchase','Counterfeit Product','Other','Phishing','Credit Cards') and
	s.description NOT LIKE '%tickets%' and
	s.description NOT LIKE '%betting%' and
	s.description NOT LIKE '%puppy%' and
	s.description NOT LIKE '%directv%' and
	s.description NOT LIKE '%direct tv%' and
	s.description NOT LIKE '% xbox%' and
	s.keywords NOT LIKE '%tickets%' and
	s.keywords NOT LIKE '%betting%' and
	s.keywords NOT LIKE '%puppy%' and
	s.keywords NOT LIKE '%directv%' and
	s.keywords NOT LIKE '%direct tv%' and
  
	(
		contains(s.description, 'near((sports, collectible), 8)') or
		contains(s.description, 'near((sports, memorabilia), 20)') or
		s.description LIKE '%sports card%' or
		(s.description LIKE '%trading card%' and s.description NOT LIKE '%magic the gathering%') or
		s.description LIKE '%baseball card%' or
		s.description LIKE '%football card%' or
		s.description LIKE '%hockey card%' or
		s.description LIKE '%basketball card%' or
		(s.description LIKE '%jersey%' AND s.description NOT LIKE '%new[ -]jersey%' AND s.description NOT LIKE '%central jersey%' AND s.description NOT LIKE '%newjersey%' AND
				s.description NOT LIKE '%jerseyeasy%' AND s.description NOT LIKE '%jersey easy%' AND s.description NOT LIKE '%jersey city%') or
		s.description LIKE '%official team%' or

		s.description LIKE '% nfl[ .,]%' or
		s.description LIKE '% nba[ .,]%' or
		s.description LIKE '% mlb[ .,]%' or
		s.description LIKE '% nhl[ .,]%' or
		s.description LIKE '% ncaa[ .,]%' or
		s.description LIKE '%steelers%' or
		s.description LIKE '%yankees%' or
		s.description LIKE '%lakers%' or
		s.description LIKE '%raiders%' or
		s.description LIKE '%49ers%' or
		s.description LIKE '%dodgers%' or
		s.description LIKE '%phillies%' or
		s.description LIKE '%astros[ .,]%' or
		s.description LIKE '%cowboys%' or
		s.description LIKE '%red sox%' or

		(s.scam_name LIKE '%jersey%' AND s.scam_name NOT LIKE '%new[ -]jersey%' AND s.scam_name NOT LIKE '%central jersey%' AND s.scam_name NOT LIKE '%newjersey%' AND
				s.scam_name NOT LIKE '%jerseyeasy%' AND s.scam_name NOT LIKE '%jersey easy%' AND s.scam_name NOT LIKE '%jersey city%') or

		s.scam_name LIKE '% nfl %' OR s.scam_name LIKE 'nfl %' or
		s.scam_name LIKE '% nba %' OR s.scam_name LIKE 'nba %' or
		s.scam_name LIKE '% mlb %' OR s.scam_name LIKE 'mlb %' or
		s.scam_name LIKE '% nhl %' OR s.scam_name LIKE 'nhl %' or
		s.scam_name LIKE '%ncaa%' OR
		s.scam_name LIKE '%steelers%' or
		s.scam_name LIKE '%yankees%' or
		s.scam_name LIKE '%lakers%' or
		s.scam_name LIKE '%raiders%' or
		s.scam_name LIKE '%49ers%' or
		s.scam_name LIKE '%dodgers%' or
		s.scam_name LIKE '%phillies%' or
		s.scam_name LIKE '%astros%' or
		s.scam_name LIKE '%cowboys%' or
		s.scam_name LIKE '%red sox%' or

		s.keywords LIKE '% nfl[, ]%' OR s.keywords LIKE 'nfl[, ]%' or
		s.keywords LIKE '% nba[, ]%' OR s.keywords LIKE 'nba[, ]%' or
		s.keywords LIKE '% mlb[, ]%' OR s.keywords LIKE 'mlb[, ]%' or
		s.keywords LIKE '% nhl[, ]%' OR s.keywords LIKE 'nhl[, ]%' or

		(sc.business_name LIKE '%jersey%' AND sc.business_name NOT LIKE '%new[ -]jersey%' AND sc.business_name NOT LIKE '%central jersey%' AND sc.business_name NOT LIKE '%newjersey%' AND
				sc.business_name NOT LIKE '%jerseyeasy%' AND sc.business_name NOT LIKE '%jersey easy%' AND sc.business_name NOT LIKE '%jersey city%') or
		sc.business_name LIKE '% nfl %' OR sc.business_name LIKE 'nfl %' or
		sc.business_name LIKE '% nba %' OR sc.business_name LIKE 'nba %' OR
		sc.business_name LIKE '% mlb %' OR sc.business_name LIKE 'mlb %' or
		sc.business_name LIKE '% nhl %' OR sc.business_name LIKE 'nhl %' or
		sc.business_name LIKE '%ncaa%' or
		sc.business_name LIKE '%steelers%' or
		sc.business_name LIKE '%yankees%' or
		sc.business_name LIKE '%lakers%' or
		sc.business_name LIKE '%raiders%' or
		sc.business_name LIKE '%49ers%' or
		sc.business_name LIKE '%dodgers%' or
		sc.business_name LIKE '%phillies%' or
		sc.business_name LIKE '%astros%' or
		sc.business_name LIKE '%cowboys%' or
		sc.business_name LIKE '%red sox%'
	)
ORDER BY s.CreatedOn DESC
