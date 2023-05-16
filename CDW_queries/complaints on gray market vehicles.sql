/* complaints and negative reviews on gray market vehicles */
/* CDW database */


select
	'Complaint',
	REPLACE(b.BusinessName,'&#39;','''') as 'Business',
	b.ReportURL,
	yppa_text as 'Type of Business',
	c.ConsumerLastName + ', ' + c.ConsumerFirstName as 'Consumer',
	'BBB ' + BBB.NicknameCity + ', ' + BBB.State as 'BBB City',
	cast(DateClosed as date) as 'Date',
	replace(replace(replace(replace(ConsumerComplaint,char(13),''),char(10),''),char(9),''),'&#39;','''') AS 'Narrative',
	replace(replace(replace(replace(DesiredOutcome,char(13),''),char(10),''),char(9),''),'&#39;','''') AS 'Desired Outcome'
from BusinessComplaint c WITH (NOLOCK)
inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID AND b.BusinessID = c.BusinessID
left outer join BusinessComplaintText t WITH (NOLOCK) on c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
left outer join BBB WITH (NOLOCK) ON BBB.BBBID = c.BBBID AND BBB.BBBBranchID = '0'
left outer join tblYPPA WITH (NOLOCK) ON BusinessTOBID = tblYPPA.yppa_code
where
	c.DateClosed >= '5/1/2020' AND
	BBB.Country = 'US' and
	LEN(c.ConsumerPostalCode) NOT IN (6,7) and
	yppa_text IN ('Auto Brokers','Auto Distributor','Auto Manufacturers','Car Dealers','New Car Dealers','Online Car Dealers','Used Car Dealers') and
	(
		ConsumerComplaint LIKE '% canad[ai]%' or
		ConsumerComplaint like '%grey market%' or
		ConsumerComplaint like '%gray market%' or
		ConsumerComplaint like '%grey_ market%' or
		ConsumerComplaint like '%gray_ market%'
	)
UNION
SELECT
	'Customer Review',
	REPLACE(b.BusinessName,'&#39;','''') as 'Business',
	b.ReportURL,
	yppa_text as 'Type of Business',
	cr.ConsumerLastName + ', ' + cr.ConsumerFirstName as 'Consumer',
	'BBB ' + BBB.NicknameCity + ', ' + BBB.State as 'BBB City',
	cast(DateReceived as date) as 'Date',
	replace(replace(replace(replace(t.CustomerReviewText,char(13),''),char(10),''),char(9),''),'&#39;','''') AS 'Narrative',
	''
from BusinessCustomerReview cr WITH (NOLOCK)
inner join BusinessCustomerReviewText t on t.BBBID = cr.BBBID and t.CustomerReviewID = cr.CustomerReviewID
left outer join BBB WITH (NOLOCK) ON BBB.BBBID = cr.BBBID AND BBB.BBBBranchID = '0'
inner join Business b on b.BBBID = cr.BBBID and b.BusinessID = cr.BusinessID
inner join tblYPPA WITH (NOLOCK) ON b.TOBID = yppa_code
WHERE
	cr.DateReceived >= '5/1/2020' AND
	Stars IN (1,2) and
	IsPublished = '1' and
	BBB.Country = 'US' and
	LEN(cr.ConsumerPostalCode) NOT IN (6,7) and
	yppa_text IN ('Auto Brokers','Auto Distributor','Auto Manufacturers','Car Dealers','New Car Dealers','Online Car Dealers','Used Car Dealers') and
	(
		CustomerReviewText LIKE '% canad[ai]%' or
		CustomerReviewText like '%grey market%' or
		CustomerReviewText like '%gray market%' or
		CustomerReviewText like '%grey_ market%' or
		CustomerReviewText like '%gray_ market%'
	)
