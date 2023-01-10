
/* can be run for US, CANADA, BOTH - but typically just run for BOTH */
declare @country varchar(10) = 'BOTH';

/* *** make sure parts 1 and 2 were run beforehand *** */

declare @tob_code varchar(10);
declare @tob_text varchar(100);
declare @inqs int;
declare @cmpls int;
declare @inqslastyear int;
declare @cmplslastyear int;

declare c cursor for
	SELECT yppa_code, yppa_text FROM tblYPPA WITH (NOLOCK) ORDER BY yppa_text;

print
	'Code' + char(9) +
	'Industry Classification' + char(9) +
	'Inquiries Last Year' + char(9) +
	'Complaints Last Year' + char(9) +
	'Inquiries This Year' + char(9) +
	'Complaints This Year' + char(9)
	;
open c;
fetch next from c into @tob_code, @tob_text;
while @@fetch_status = 0 begin
	set @inqs = 0;
	select @inqs = SUM(CountTotal) from tmpInquiries WITH (NOLOCK) where
		tmpInquiries.TOBID = @tob_code AND tmpInquiries.Country != 'Mexico' AND
		(@country = 'BOTH' or tmpInquiries.Country = @country)
		;
	set @cmpls = 0;
	select @cmpls = count(*) from tmpComplaints WITH (NOLOCK) WHERE
		tmpComplaints.BusinessTOBID = @tob_code AND tmpComplaints.Country != 'Mexico' AND
		tmpComplaints.CloseCode <= '300' AND
		(@country = 'BOTH' or tmpComplaints.Country = @country)
		;
	if @inqs is null set @inqs = 0;
	if @cmpls is null set @cmpls = 0;

	set @inqslastyear = 0;
	select @inqslastyear = SUM(CountTotal) from tmpInquiriesLastYear WITH (NOLOCK) where
		tmpInquiriesLastYear.TOBID = @tob_code AND tmpInquiriesLastYear.Country != 'Mexico' AND
		(@country = 'BOTH' or tmpInquiriesLastYear.Country = @country)
		;
	set @cmplslastyear = 0;
	select @cmplslastyear = count(*) from tmpComplaintsLastYear WITH (NOLOCK) WHERE
		tmpComplaintsLastYear.BusinessTOBID = @tob_code AND tmpComplaintsLastYear.Country != 'Mexico' AND
		tmpComplaintsLastYear.CloseCode <= '300' AND
		(@country = 'BOTH' or tmpComplaintsLastYear.Country = @country)
		;
	if @inqslastyear is null set @inqslastyear = 0;
	if @cmplslastyear is null set @cmplslastyear = 0;

	if @inqs > 0 or @cmpls > 0 or @inqslastyear > 0 or @cmplslastyear > 0
	print
		@tob_code + char(9) +
		@tob_text + char(9) +
		cast(@inqslastyear as varchar) + char(9) +
		cast(@cmplslastyear as varchar) + char(9) +
		cast(@inqs as varchar) + char(9) +
		cast(@cmpls as varchar) + char(9)
		;
	fetch next from c into @tob_code, @tob_text;
end
close c;
deallocate c;
