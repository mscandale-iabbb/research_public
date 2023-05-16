/* this query used for study in blog https://iabbb.sharepoint.com/sites/ResearchHub/SitePages/Scammers-Using-Messaging-More.aspx */

DECLARE @type VARCHAR(30) = 'website transaction'  /* value can be 'website transaction' or 'interpersonal transaction' */
SELECT
	replace(replace(replace(replace(cast(s.description AS VARCHAR(MAX)),char(13),' '),char(10),' '),char(9),' '),'&#39;','`') AS 'Scam Description',
	t.scam_type,
	via.scam_via,
	pm.payment_method,
	i.dollar_value
FROM BlueScam.dbo.tblScam s
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry i ON i.scam_id = s.scam_id
LEFT JOIN BlueScam.dbo.tblSCAM_Victim v ON v.victim_id = i.victim_id
LEFT JOIN BlueScam.dbo.tblSCAM_Scammer sc ON sc.scammer_id = i.scammer_id
LEFT JOIN BlueScam.dbo.tblSCAM_Type t ON t.scam_type_id = s.scam_type_id
LEFT JOIN BlueScam.dbo.tblBBB ON tblBBB.bbbid = i.bbbid
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry_Payment p ON p.scam_inquiry_id = i.pk_id
LEFT JOIN BlueScam.dbo.tblSCAM_PaymentMethod pm ON pm.payment_method_id = p.payment_method_id
LEFT JOIN BlueScam.dbo.tblSCAM_Victim_ContactMethod c ON c.contact_method_id = v.contact_method_id
LEFT JOIN BlueScam.dbo.tblSCAM_Inquiry_Via iv ON iv.scam_inquiry_id = i.pk_id
LEFT JOIN BlueScam.dbo.tblSCAM_Via via ON via.scam_via_id = iv.scam_via_id
WHERE
	s.status_id = 2 and
	YEAR(s.CreatedOn) >= '2020' and
	--i.dollar_value > 0 and
	(
		(
			@type = 'website transaction' and
			(
				(
					via.scam_via = 'Website' or
					t.scam_type = 'Counterfeit Product' or
					s.description LIKE '%website%' or
					s.description LIKE '% site%' or
					s.description LIKE '% store %' or
					s.description LIKE '% merchant%' or
					s.description LIKE '% order%' OR
					s.description LIKE '%purchased%' OR
					s.description LIKE '%bought%' OR
					s.description LIKE '% ad %' OR
					s.description LIKE '%advertis%'
				) and
				NOT t.scam_type IN ('Employment','Fake Invoice/Supplier Bill','Tech Support','Utility','Family/Friend Emergency','Government Grant','Home Improvement','CryptoCurrency','Credit Repair/Debt Relief','Rental','Sweepstakes/Lottery/Prizes','Business Email Compromise','Fake Check/Money Order','Romance','Investment') AND
				NOT via.scam_via LIKE '%craigslist%' and
				NOT via.scam_via IN ('Text Message','In Person','Internet Messaging (e.g., WhatsApp)','Phone') and
				(NOT pm.payment_method like 'wire transfer%' or pm.payment_method is NULL) and
				(NOT pm.payment_method like 'money order%' or pm.payment_method is NULL) and
				NOT s.description LIKE '% zelle[ .,!]%' and
				NOT s.description LIKE '% cash app%' and
				NOT s.description LIKE '%cashapp%' and
				NOT s.description LIKE '%venmo%' and
				NOT s.description LIKE '% chat %' and
				NOT s.description LIKE '% chatting%' and
				NOT s.description LIKE '% text me%' and
				NOT s.description LIKE '% reached out to me%'
			)
		)
		OR
		(
			@type = 'interpersonal transaction' and
			via.scam_via != 'Website' and
			t.scam_type != 'Counterfeit Product' and
			NOT s.description LIKE '% order%' and
			NOT s.description LIKE '%website%' and
			NOT s.description LIKE '% site%' and
			NOT s.description LIKE '% store %' and
			NOT s.description LIKE '% merchant%' and
			NOT s.description LIKE '%purchased%' and
			NOT s.description LIKE '%bought%' and
			NOT s.description LIKE '% ad %' and
			NOT s.description LIKE '%advertis%' and
			(
				t.scam_type IN ('Employment','Fake Invoice/Supplier Bill','Tech Support','Utility','Family/Friend Emergency','Government Grant','Home Improvement','CryptoCurrency','Credit Repair/Debt Relief','Rental','Sweepstakes/Lottery/Prizes','Business Email Compromise','Fake Check/Money Order','Romance','Investment') or
				via.scam_via LIKE '%craigslist%' or
				via.scam_via IN ('Text Message','In Person','Internet Messaging (e.g., WhatsApp)','Phone') or
				pm.payment_method like 'wire transfer%' or
				pm.payment_method like 'money order%' or
				s.description LIKE '% zelle[ .,!]%' or
				s.description LIKE '% cash app%' or
				s.description LIKE '%cashapp%' or
				s.description LIKE '%venmo%' or
				s.description LIKE '% chat %' or
				s.description LIKE '% chatting%' or
				s.description LIKE '% text me%' or
				s.description LIKE '% contacted me%' or
				s.description LIKE '% reached out to me%'
			)
		)
	)
ORDER BY NEWID()
