
select
	BBB.NicknameCity + ',' + State as "BBB City",
	(select sum(SnapshotStats.CountOfInquiries )
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022'
	) as 'Inquiries 2022',
	(select sum(SnapshotStats.CountOfReportableComplaints )
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022'
	) as 'Reportable Complaints 2022',
	(select sum(SnapshotStats.CountOfComplaints )
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022'
	) as 'All Complaints 2022',
	(select sum(SnapshotStats.CountOfNewABs )
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022'
	) as 'New ABs 2022',
	(select sum(SnapshotStats.CountOfDroppedABs )
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022'
	) as 'Dropped ABs 2022',
	(select SnapshotStats.CountOfABs
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022' and
		SnapshotStats.MonthNumber = '12'
	) AS 'ABs As Of 12/2022',
	(select SnapshotStats.CountOfBillableABs
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022' and
		SnapshotStats.MonthNumber = '12'
	) AS 'Billable ABs As Of 12/2022',
	(select SnapshotStats.CountOfBusinesses
		from SnapshotStats WITH (NOLOCK) where
		SnapshotStats.BBBID = BBB.BBBID and
		SnapshotStats.[Year] = '2022' and
		SnapshotStats.MonthNumber = 12
	) AS 'All Businesses As Of 12/2022'
from BBB WITH (NOLOCK)
where
	BBB.BBBBranchID = 0 and IsActive = '1'
order by NicknameCity
