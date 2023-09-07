SELECT
	s.scam_id AS 'Scam ID',
	v.name_first AS 'Victim First Name',
	v.name_last AS 'Victim Last Name',
	v.state,
	v.zip AS 'Victim Zip',
	v.email AS 'Victim Email',
	v.phone AS 'Victim Phone',
	sc.business_name AS 'Business Name',
	sc.address_1 AS 'Business Address',
	--sc.address_2 AS 'Business Address 2',
	sc.city AS 'Business City',
	sc.state AS 'Business State',
	sc.zip AS 'Business Zip',
	sc.phone AS 'Business Phone',
	s.CreatedOn AS 'Created',
	replace(replace(replace(replace(cast(s.description AS VARCHAR(MAX)),char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Description',
	g.description AS 'Victim Gender',
	a.agerange AS 'Victim Age Range',
	replace(replace(replace(replace(t.scam_type,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Type',
	sc.email AS 'Business Email',
	v.isActiveDuty AS 'Active Duty Military',
	v.isStudent AS 'Student',
	v.isVictim AS 'Is Victim',
	replace(replace(replace(replace(pm.payment_method,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Payment Method',
	replace(replace(replace(replace(via.scam_via,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Contact Method',
	sc.country AS 'Business Country',
	v.allowMedia AS 'Allow Media',
	tblBBB.bureau_code + ' - ' + tblBBB.location AS 'BBB',
	i.dollar_value AS 'Amount Lost'

	/*
	i.scam_type_other AS 'Scam Type Other',
	replace(replace(replace(replace(s.scam_name,char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Name',
	replace(replace(replace(replace(cast(s.keywords AS VARCHAR(MAX)),char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Keywords',
	i.dollar_attempt AS 'Amount Attempted',
	sc.url AS 'Business URL'
	i.ipaddress AS 'IP Address',
	v.city AS 'Victim City',
	v.state AS 'Victim State',
	v.zip_4 AS 'Victim Zip+4',
	v.country AS 'Victim Country',
	v.isIndividual AS 'Individual',
	*/
FROM BlueScam.dbo.tblScam s
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry i ON i.scam_id = s.scam_id
LEFT JOIN BlueScam.dbo.tblSCAM_Victim v ON v.victim_id = i.victim_id
LEFT JOIN BlueScam.dbo.tblSCAM_Scammer sc ON sc.scammer_id = i.scammer_id
LEFT JOIN BlueScam.dbo.tblSCAM_Type t ON t.scam_type_id = s.scam_type_id
LEFT JOIN BlueScam.dbo.tblBBB ON tblBBB.bbbid = i.bbbid
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry_Payment p ON p.scam_inquiry_id = i.pk_id
LEFT JOIN BlueScam.dbo.tblSCAM_PaymentMethod pm ON pm.payment_method_id = p.payment_method_id
--LEFT JOIN tblSCAM_Victim_ContactMethod c ON c.contact_method_id = v.contact_method_id
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry_Via iv ON iv.scam_inquiry_id = i.pk_id
LEFT JOIN BlueScam.dbo.tblSCAM_Via via ON via.scam_via_id = iv.scam_via_id
LEFT JOIN BlueScam.dbo.tblSCAM_Status st ON st.status_id = s.status_id
LEFT JOIN BlueScam.dbo.tblSCAM_Victim_AgeRange a ON a.agerange_id = v.age
LEFT JOIN BlueScam.dbo.tblSCAM_Victim_Gender g ON g.gender = v.gender
WHERE
	s.status_id = 2 and
	t.scam_type IN ('Online Purchase','Counterfeit Product','Phishing','Identity Theft','Other') and
	--YEAR(s.CreatedOn) = '2022' and
	-- v.state = 'WI' and
	(
		sc.business_name like '%pet scam%' or s.description like '%pet scam%' OR s.scam_name like '%pet scam%' OR s.keywords like '%pet scam%' OR
		sc.business_name like '%pupp[yi]%' or s.description like '%pupp[yi]%' OR s.scam_name like '%pupp[yi]%' OR s.keywords like '%pupp[yi]%' OR
		sc.business_name like '%pup[ s]%' or s.description like '%pup[ s]%' OR s.scam_name like '%pup[ s]%' OR s.keywords like '%pup[ s]%' OR
		sc.business_name like '%dog%' or s.description like '%dog%' OR s.scam_name like '%dog%' or s.keywords like '%dog%' OR
		sc.business_name like '%hound%' or s.description like '%hound%' or s.scam_name like '%hound%' or
		sc.business_name like '%canine%' or s.description like '%canine%' or s.scam_name like '%canine%' or
		sc.business_name like '%k9%' or s.description like '%k9%' or
		sc.business_name like '%k-9%' or s.description like '%k-9%' or
		sc.business_name like '%akc%' or s.description like '%akc%' or
		sc.business_name like '%breeder%' or s.description like '%breeder%' OR s.scam_name like '%breeder%' OR s.keywords like '%breeder%' OR

		sc.business_name like '%kitten%' or s.description like '%kitten%' OR s.scam_name like '%kitten%' OR s.keywords like '%kitten%' OR
		sc.business_name like '%kitty%' or s.description like '%kitty%' OR s.scam_name like '%kitty%' OR s.keywords like '%kitty%' OR
		sc.business_name LIKE '% cat[s, ]%' or s.description like '% cat[s, ]%' OR s.scam_name like '% cat[s, ]%' OR s.keywords like '% cat[s, ]%' or
		sc.business_name like '%cattery%' or s.description like '%cattery%' OR s.scam_name like '%cattery%' OR s.keywords like '%cattery%' OR
		sc.business_name like '%maine coon%' or s.description like '%maine coon%' OR s.scam_name like '%maine coon%' OR s.keywords like '%maine coon%' OR
		
		sc.business_name like '%beagle%' or s.description like '%beagle%' or
		sc.business_name like '%poodle%' or s.description like '%poodle%' or
		sc.business_name like '%spaniel%' or s.description like '%spaniel%' or
		sc.business_name like '%retriever%' or s.description like '%retriever%' or
		sc.business_name like '%dachshund%' or s.description like '%dachshund%' or
		sc.business_name like '%boxer%' or s.description like '%boxer%' or
		sc.business_name like '%chihuahua%' or s.description like '%chihuahua%' or
		sc.business_name like '%pomeranian%' or s.description like '%pomeranian%' or
		sc.business_name like '%mastiff%' or s.description like '%mastiff%' or
		sc.business_name like '%bernese%' or s.description like '%bernese%' or
		sc.business_name like '%dalmatian%' or s.description like '%dalmatian%' or
		sc.business_name like '%bichon%' or s.description like '%bichon%' or
		sc.business_name like '%rottweiler%' or s.description like '%rottweiler%' or
		sc.business_name like '%great dane%' or s.description like '%great dane%' or
		sc.business_name like '%doberman%' or s.description like '%doberman%' or
		sc.business_name like '%pinscher%' or s.description like '%pinscher%' or
		sc.business_name like '%husky%' or s.description like '%husk[yi]%' or
		sc.business_name like '%pit bull%' or s.description like '%pit bull%' or
		sc.business_name like '%vizsla%' or s.description like '%vizsla%' or
		sc.business_name like '%maltese%' or s.description like '%maltese%' or
		sc.business_name like '%akita%' or s.description like '%akita%' or
		sc.business_name like '%schnauzer%' or s.description like '%schnauzer%' or
		sc.business_name like '%havanese%' or s.description like '%havanese%' or
		sc.business_name like '%weimar%' or s.description like '%weimar%' or
		sc.business_name like '%basset%' or s.description like '%basset%' or
		sc.business_name like '%ridgeback%' or s.description like '%ridgeback%' or
		sc.business_name like '%papillon%' or s.description like '%papillon%' or
		sc.business_name like '%corgi%' or s.description like '%corgi%' or
		sc.business_name like '%whippet%' or s.description like '%whippet%' or
		sc.business_name like '%malamute%' or s.description like '%malamute%' or
		sc.business_name like '%samoyed%' or s.description like '%samoyed%' or
		sc.business_name like '%shih %' or s.description like '%shih %' or
		sc.business_name like '%shepherd%' or s.description like '%shepherd%' or
		sc.business_name like '%pointer%' or s.description like '%pointer%' or
		sc.business_name like '%collie%' or s.description like '%collie%'
	)
	--i.dollar_value > 0 and
	--v.allowMedia = '1'
