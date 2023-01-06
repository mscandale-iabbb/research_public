
declare @levels int
set @levels = 6
declare @FromDate smalldatetime = '1/1/2020'
declare @ToDate smalldatetime = '12/31/2020'

select
	-- substring(cast(y.naics_code as varchar(6)),1,@levels),
	n.naics_description,
	SUM(CountTotal)
from Business b WITH (NOLOCK)
inner join BusinessInquiry i WITH (NOLOCK) ON
	i.BBBID = b.BBBID and i.BusinessID = b.BusinessID and
	i.DateOfInquiry >= @FromDate and i.DateOfInquiry <= @ToDate
inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as int)
where
	b.TOBID != '99999-000' and
	LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels
group by substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
order by SUM(CountTotal) desc
