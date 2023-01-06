
declare @levels INT = 6     /* 2 through 6 */
declare @FromDate smalldatetime = '1/1/2022'
declare @ToDate smalldatetime = '1/1/2023'

SELECT
	substring(cast(y.naics_code as varchar(6)),1,@levels),
	n.naics_description,
	count(*) as Complaints
from BusinessComplaint c WITH (NOLOCK)
inner join Business b WITH (NOLOCK) on b.BBBID = c.BBBID and b.BusinessID = c.BusinessID
inner join tblYPPA y WITH (NOLOCK) ON b.TOBID = y.yppa_code
inner join tblNAICS n WITH (NOLOCK) ON n.naics_code = cast(substring(cast(y.naics_code as varchar(6)),1,@levels) as INT)
WHERE
	c.DateClosed >= @FromDate and c.DateClosed < @ToDate and
	LEN(rtrim(cast(y.naics_code as varchar(6)))) >= @levels and
	c.CloseCode <= '300' and c.CloseCode is not null and c.CloseCode > '0' and
	c.ComplaintID not like 'scam%' and
	b.TOBID != '99999-000' and
	len(b.TOBID) = 9
	-- ('0272' = '' or c.BBBID = '0272')
GROUP BY substring(cast(y.naics_code as varchar(6)),1,@levels), n.naics_description
ORDER BY Complaints DESC
