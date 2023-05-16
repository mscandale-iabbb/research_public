declare @FromDate smalldatetime = '1/1/2020'
declare @ToDate smalldatetime = '12/31/2023'

SELECT
	NatureOfReview,
	COUNT(*) as AdReview
from BusinessAdReview a WITH (NOLOCK)
inner join Business b WITH (NOLOCK) ON b.BBBID = a.BBBID AND b.BusinessID = a.BusinessID
inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
where
	a.DateClosed >= @FromDate and a.DateClosed <= @ToDate and
	b.TOBID != '99999-000' and
	NatureOfReview LIKE '[0123456789]%' and
	LEN(NatureOfReview) > 1 and
	LEN(NatureOfReview) < 80 and
	NatureOfReview NOT LIKE 'http%' and
	NatureOfReview NOT LIKE '%www.%' and
	NatureOfReview NOT LIKE 'noted a %' and
	NatureOfReview NOT LIKE '"%' and
	NatureOfReview NOT LIKE '%logo%' and
	NatureOfReview NOT LIKE '%bbb name%'
	--NatureOfReview LIKE '%logo%'
group by NatureOfReview
order by NatureOfReview -- count(*) desc
