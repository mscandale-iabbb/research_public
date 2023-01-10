
declare @FromDate smalldatetime = '1/1/2022'
declare @ToDate smalldatetime = '1/1/2023'



/* **************************************** */
/* STEP 1 - create entries for BusinessComplaint */
/* **************************************** */

create table #tmpComplaints
(	BusinessTOBID varchar(12),
	CloseCode smallint,
	Country varchar(8) )

insert into #tmpComplaints
select
	BusinessComplaint.BusinessTOBID,
	BusinessComplaint.CloseCode,
	BBB.Country
from
	BusinessComplaint WITH (NOLOCK)
	left outer join BBB WITH (NOLOCK)
		on BBB.BBBIDFull = BusinessComplaint.BBBID
where
	BusinessComplaint.DateClosed >= @FromDate AND
	BusinessComplaint.DateClosed < @ToDate AND
	BusinessComplaint.ComplaintID not like 'scam%' AND
	BusinessComplaint.BBBID != '8888'

/* create index for table */
create index idxTOB on #tmpComplaints
	(BusinessTOBID, CloseCode, Country)

/* **************************************** */
/* STEP 2 - create entries for BusinessInquiry */
/* **************************************** */

create table #tmpInquiries
(	TOBID varchar(12),
	Country varchar(8),
	CountAll int )

insert into #tmpInquiries
select
	Business.TOBID,
	BBB.Country,
	BusinessInquiry.CountTotal
from
	BusinessInquiry WITH (NOLOCK)
	left outer join BBB WITH (NOLOCK)
		on BBB.BBBIDFull = BusinessInquiry.BBBID
	inner join Business WITH (NOLOCK)
		on Business.BBBID = BusinessInquiry.BBBID AND
		Business.BusinessID = BusinessInquiry.BusinessID
where
	BusinessInquiry.DateOfInquiry >= @FromDate AND
	BusinessInquiry.DateOfInquiry < @ToDate AND
	BusinessInquiry.BBBID != '8888'

/* create index */
create index idxTOB on #tmpInquiries
	(TOBID, Country)
