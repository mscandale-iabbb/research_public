SELECT
	s.scam_id,
	s.status_id,
	-- s.scam_type_id,
	t.scam_type,
	i.scam_type_other,
	s.scam_name,
	s.description,
	s.keywords,
	s.CreatedOn,
	i.dollar_value,
	i.dollar_attempt,
	i.ipaddress,
	i.latitude,
	i.longitude,
	v.name_first,
	v.name_last,
	v.phone,
	v.email,
	v.age,
	v.gender,
	v.city,
	v.state,
	v.zip,
	v.country,
	v.zip_4,
	v.allowMedia,
	v.isVictim,
	v.isIndividual,
	v.isActiveDuty,
	v.isStudent,
	sc.business_name,
	sc.address_1,
	sc.address_2,
	sc.city,
	sc.state,
	sc.zip,
	sc.country,
	sc.phone,
	sc.email,
	sc.url
FROM tblScam s
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry i ON i.scam_id = s.scam_id
LEFT JOIN tblSCAM_Victim v ON v.victim_id = i.victim_id
LEFT JOIN tblSCAM_Scammer sc ON sc.scammer_id = i.scammer_id
LEFT JOIN tblSCAM_Type t ON t.scam_type_id = s.scam_type_id
WHERE
	s.status_id = 2
