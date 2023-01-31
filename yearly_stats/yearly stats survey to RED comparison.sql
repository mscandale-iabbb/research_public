
declare @xyear smallint
set @xyear = '2022'
declare @datefrom date
declare @dateto date
set @datefrom = '1/1/2022'
set @dateto = '12/31/2022'

select NickNameCity + ', ' + State as BBB,
	Vendor,
	CountOfInquiries as Inquiries,
	CountOfComplaints as Complaints,
	CountOfCustomerReviews as CustomerReviews,
	CountOfABsYearEnd as ABs,
	(	select SUM(CountTotal) from BusinessInquiry WITH (NOLOCK) WHERE
		BusinessInquiry.BBBID = BBB.BBBID and DateOfInquiry >= @datefrom AND
		DateOfInquiry <= @dateto
	) as RED_Inquiries,
	(	select COUNT(*) from BusinessComplaint WITH (NOLOCK) WHERE
		BusinessComplaint.BBBID = BBB.BBBID and DateClosed >= @datefrom and
		DateClosed <= @dateto and
		CloseCode <= '300'
	) as RED_Complaints,
	(	select count(*)
		FROM BusinessCustomerReview cr WITH (NOLOCK) WHERE
		YEAR(DateReceived) = @xyear AND cr.BBBID = BBB.BBBID AND cr.IsPublished = '1'
	) as RED_CustomerReviews,
	(	select count(distinct Business.BusinessID) from Business WITH (NOLOCK)
		inner join BusinessProgramParticipation WITH (NOLOCK) on
		BusinessProgramParticipation.BBBID = Business.BBBID AND
		BusinessProgramParticipation.BusinessID = Business.BusinessID and
		(BBBProgram = 'Membership' or BBBProgram = 'BBB Accredited Business')
		where Business.BBBID = BBB.BBBID and DateFrom <= @dateto AND
		NOT DateFrom IS NULL AND (DateTo > @dateto OR DateTo IS NULL) AND
		Business.IsBillable = '1'
	) as RED_ABs,
	(	select COUNT(*) from BusinessComplaint WITH (NOLOCK) WHERE
		BusinessComplaint.BBBID = BBB.BBBID and DateClosed >= @datefrom and
		DateClosed <= @dateto
	) as RED_TOTAL_Complaints
from YearlyStats WITH (NOLOCK)
inner join BBB WITH (NOLOCK) ON BBB.BBBID = YearlyStats.BBBID and
	BBB.BBBBranchID = '0'
where YearlyStats.BBBID != '' and
	YearlyStats.BBBID != '2000' and
	YearlyStats.BBBID != '8888' and
	IsActive = '1' and
	[Year] = @xyear
order by Vendor, NicknameCity, State
