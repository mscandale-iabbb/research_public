
/* PREP - clean up BusinessComplaint.BusinessTOBID field */

update BusinessComplaint
	SET BusinessComplaint.BusinessTOBID = Business.TOBID
FROM BusinessComplaint
inner join Business WITH (NOLOCK) ON
	BusinessComplaint.BBBID = Business.BBBID AND
	BusinessComplaint.BusinessID = Business.BusinessID
where
	BusinessComplaint.BusinessTOBID IS NULL or
	LEN(BusinessComplaint.BusinessTOBID) < 5 or
	BusinessComplaint.CDWLastUpdate < Business.CDWLastUpdate
