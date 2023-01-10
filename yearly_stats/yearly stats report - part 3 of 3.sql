
/* make sure parts 1 and 2 were run beforehand */
/* run this 3 times: US, CANADA, BOTH */

declare @country varchar(10) = 'BOTH'


create table #TOBStat (
	tob_code varchar(9),
	tob_text varchar(255),
	inqs bigint,
	cmpls int,
	cmpls_settled int,
	cmpls_not_settled int,
	cmpls_unable_to_pursue int,
	constraint pk_fake_index primary key (tob_code)
)


declare @tob_code varchar(10)
declare @tob_text varchar(100)
declare @inqs int
declare @cmpls int
declare @cmpls_settled int
declare @cmpls_not_settled int
declare @cmpls_unable_to_pursue int

declare c cursor for
	SELECT yppa_code, yppa_text FROM tblYPPA WITH (NOLOCK) ORDER BY yppa_text

open c
fetch next from c into @tob_code, @tob_text
while @@fetch_status = 0 begin
	set @inqs = 0
	select @inqs = SUM(CountAll) from #tmpInquiries WITH (NOLOCK) where
		#tmpInquiries.TOBID = @tob_code AND #tmpInquiries.Country != 'Mexico' AND
		(@country = 'BOTH' or #tmpInquiries.Country = @country)

	set @cmpls = 0
	select @cmpls = count(*) from #tmpComplaints WITH (NOLOCK) WHERE
		#tmpComplaints.BusinessTOBID = @tob_code AND #tmpComplaints.Country != 'Mexico' AND
		#tmpComplaints.CloseCode <= '300' AND
		(@country = 'BOTH' or #tmpComplaints.Country = @country)

	set @cmpls_settled = 0
	select @cmpls_settled = count(*) from #tmpComplaints WITH (NOLOCK) WHERE
		#tmpComplaints.BusinessTOBID = @tob_code AND #tmpComplaints.Country != 'Mexico' AND
		#tmpComplaints.CloseCode IN ('110','150') AND
		(@country = 'BOTH' or #tmpComplaints.Country = @country)

	set @cmpls_not_settled = 0
	select @cmpls_not_settled = count(*) from #tmpComplaints WITH (NOLOCK) WHERE
		#tmpComplaints.BusinessTOBID = @tob_code AND #tmpComplaints.Country != 'Mexico' AND
		#tmpComplaints.CloseCode IN ('120','200') AND
		(@country = 'BOTH' or #tmpComplaints.Country = @country)

	set @cmpls_unable_to_pursue = 0
	select @cmpls_unable_to_pursue = count(*) from #tmpComplaints WITH (NOLOCK) WHERE
		#tmpComplaints.BusinessTOBID = @tob_code AND #tmpComplaints.Country != 'Mexico' AND
		#tmpComplaints.CloseCode = '300' AND
		(@country = 'BOTH' or #tmpComplaints.Country = @country)

	if @inqs is null set @inqs = 0
	if @cmpls is null set @cmpls = 0
	if @cmpls_settled is null set @cmpls_settled = 0
	if @cmpls_not_settled is null set @cmpls_not_settled = 0
	if @cmpls_unable_to_pursue is null set @cmpls_unable_to_pursue = 0
	if @inqs > 0 or @cmpls > 0
	insert into #TOBStat values (
		@tob_code,
		@tob_text,
		@inqs,
		@cmpls,
		@cmpls_settled,
		@cmpls_not_settled,
		@cmpls_unable_to_pursue
	)

	fetch next from c into @tob_code, @tob_text
end
close c
deallocate c

select
	tob_code as 'Code',
	tob_text as 'Industry Classification',
	inqs as 'Inquiries',
	cmpls as 'Complaints',
	cmpls_settled as 'Cmpls Settled',
	cmpls_not_settled as 'Cmpls Not Settled',
	cmpls_unable_to_pursue as 'Cmpls Unable To Pursue'
from #TOBStat
order by tob_code

drop table #TOBStat
