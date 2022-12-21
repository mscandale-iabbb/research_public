SELECT
	s.scam_id AS 'Scam ID',
	tblBBB.bureau_code AS 'BBB',
	st.scam_status AS 'Status',
	t.scam_type AS 'Scam Type',
	i.scam_type_other AS 'Scam Type Other',
	--c.contact_method,
	via.scam_via AS 'Contact Method',
	pm.payment_method AS 'Payment Method',
	s.scam_name AS 'Scam Name',
	s.description AS 'Scam Description',
	s.keywords AS 'Scam Keywords',
	s.CreatedOn AS 'Created',
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
	--v.zip_4 AS 'Victim Zip+4',
	v.country AS 'Victim Country',
	v.allowMedia AS 'Allow Media',
	v.isVictim AS 'Is Victim',
	v.isIndividual AS 'Individual',
	v.isActiveDuty AS 'Active Duty Military',
	v.isStudent AS 'Student',
	sc.business_name AS 'Business Name',
	sc.address_1 AS 'Business Address',
	sc.address_2 AS 'Business Address 2',
	sc.city AS 'Business City',
	sc.state AS 'Business State',
	sc.zip AS 'Business Zip',
	sc.country AS 'Business Country',
	sc.phone AS 'Business Phone',
	sc.email AS 'Business Email',
	sc.url AS 'Business URL'
FROM tblScam s
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry i ON i.scam_id = s.scam_id
LEFT JOIN tblSCAM_Victim v ON v.victim_id = i.victim_id
LEFT JOIN tblSCAM_Scammer sc ON sc.scammer_id = i.scammer_id
LEFT JOIN tblSCAM_Type t ON t.scam_type_id = s.scam_type_id
LEFT JOIN tblBBB ON tblBBB.bbbid = i.bbbid
LEFT JOIN tblSCAM_Inquiry_Payment p ON p.scam_inquiry_id = i.pk_id
LEFT JOIN tblSCAM_PaymentMethod pm ON pm.payment_method_id = p.payment_method_id
--LEFT JOIN tblSCAM_Victim_ContactMethod c ON c.contact_method_id = v.contact_method_id
LEFT JOIN tblSCAM_Inquiry_Via iv ON iv.scam_inquiry_id = i.pk_id
LEFT JOIN tblSCAM_Via via ON via.scam_via_id = iv.scam_via_id
LEFT JOIN tblSCAM_Status st ON st.status_id = s.status_id
LEFT JOIN tblSCAM_Victim_AgeRange a ON a.agerange_id = v.age
LEFT JOIN tblSCAM_Victim_Gender g ON g.gender = v.gender
WHERE
	s.status_id = 2
