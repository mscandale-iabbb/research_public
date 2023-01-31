
declare @xyear smallint
set @xyear = '2022'
declare @datefrom date
declare @dateto date
set @datefrom = '1/1/2022'
set @dateto = '12/31/2022'

/* Part 1 of 2: Ones who have submitted so far: */
select NickNameCity + ', ' + State as BBB,
	[Year],
	Vendor,
	SalesCategory,
	Region,
	/*BBB.[2007EstabsInArea] as Firms2007,*/
	CountOfInquiries as Inquiries,
	CountOfComplaints as Complaints,
	CountOfCustomerReviews as CustRevs,
	CountOfScams as Scams,
	CountOfABsYearEnd as ABs,
	CountOfAdReview as AdReview,
	CountOfInvestigations as Investigations,
	CountOfCharityReports as CharityReports,
	CountOfBBBOnLine as BBBOL,
	CountOfBBBOnLineSeals as BBBOLClicks,
	CountOfPageViews as PageViews,
	CountOfABsRevoked as ABsRevoked,
	CountOfABsDenied as ABsDenied,
	CountOfGeneralInquiries as GeneralInqs,
	CountOfInquiriesReferred as InqsReferred,
	CountOfMediationsFormal as MedsFormal,
	CountOfMediationsInformal as MedsInformal,
	CountOfArbitrationsNonAutoline as ArbsNonAuto,
	CountOfArbitrationsOfferedNonAutoline as ArbsOffered,
	CountOfComplaintCounselings as CmplCounsel
	/*
	(	select SUM(CountTotal) from BusinessInquiry WITH (NOLOCK) WHERE
		BusinessInquiry.BBBID = BBB.BBBID and DateOfInquiry >= @datefrom AND
		DateOfInquiry <= @dateto
	) as RED_Inquiries,
	(	select COUNT(*) from BusinessComplaint WITH (NOLOCK) WHERE
		BusinessComplaint.BBBID = BBB.BBBID and DateClosed >= @datefrom and
		DateClosed <= @dateto and
		CloseCode IN ('110','111','112','120','121','122','200','300')
	) as RED_Complaints,
	(	select count(distinct Business.BusinessID) from Business WITH (NOLOCK)
		inner join BusinessProgramParticipation WITH (NOLOCK) on
		BusinessProgramParticipation.BBBID = Business.BBBID AND
		BusinessProgramParticipation.BusinessID = Business.BusinessID and
		(BBBProgram = 'Membership' or BBBProgram = 'BBB Accredited Business')
		where Business.BBBID = BBB.BBBID and DateFrom <= @dateto AND
		NOT DateFrom IS NULL AND (DateTo > @dateto OR DateTo IS NULL) AND
		Business.IsBillable = '1'
	) as RED_ABs
	*/
from YearlyStats WITH (NOLOCK)
inner join BBB WITH (NOLOCK) ON BBB.BBBID = YearlyStats.BBBID and
	BBB.BBBBranchID = '0'
where YearlyStats.BBBID != '' and
	YearlyStats.BBBID != '2000' and
	IsActive = '1' and
	[Year] = @xyear
order by NicknameCity, State

/* Part 2 of 2: Ones who haven't submitted yet: */
/*
select NickNameCity + ', ' + State as BBB,
	BBBPerson.Email, CountOfInquiries
from BBB WITH (NOLOCK)
left outer join BBBPerson WITH (NOLOCK) ON
	BBBPerson.BBBID = BBB.BBBID AND BBBPerson.CEO = 1
left outer join YearlyStats WITH (NOLOCK) ON YearlyStats.BBBID = BBB.BBBID and
	YearlyStats.[Year] = @xyear
where BBB.BBBBranchID = '0' and
	IsActive = '1' and CountOfInquiries IS NULL and
	CountOfComplaints is NULL
order by CountofInquiries, NicknameCity, State
*/
