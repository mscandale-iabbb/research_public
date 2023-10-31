    SELECT
            c.ConsumerFirstName, /*[0]*/
            c.ConsumerMiddleName,
            c.ConsumerLastName,
            c.ConsumerStreetAddress,
            c.ConsumerStreetAddress2,
            c.ConsumerCity, /*[5]*/
            ForeignCountries.CountryID,
            c.ConsumerCountry,
            c.ConsumerStateProvince,
            tblStates.StateNameProper,
            c.ConsumerPostalCode, /*[10]*/
            c.ConsumerEveningPhone,
            c.ConsumerPhone,
            c.ConsumerFax,
            c.ConsumerEmail,
            c.BusinessName, /*[15]*/
            c.BusinessStreetAddress,
            c.BusinessStreetAddress2,
            c.BusinessCity,
            c.BusinessStateProvince,
            tblStates2.StateNameProper, /*[20]*/
            c.BusinessPostalCode,
            c.BusinessPhone,
            b.Email,
            c.BusinessWebsite,
            c.BBBID, /*[25]*/
            c.BusinessID,
            c.DateComplaintOpenedByBBB,
            c.BusinessTOBID,
            tblYPPA.yppa_text,
            c.ClassificationID1, /*[30]*/
            tblClassification.ClassificationDescription,
            REPLACE(t.ConsumerComplaint,'"',''''),
            c.BBBID + c.ComplaintID,
            t.DesiredOutcome,
            c.AmountDisputed, /*[35]*/
            c.DateClosed,
            tblResolutionCode.ResolutionCodeDescription,
            c.ComplaintID,
            BBB.UseODRComplaintForm,
            b.Country, /*[40]*/
            tblMexicanStates.FTCStateCode
    from BusinessComplaint c WITH (NOLOCK)
    left outer join Business b WITH (NOLOCK) ON
            c.BBBID = b.BBBID AND
            c.BusinessID = b.BusinessID
    left outer join BBB WITH (NOLOCK) ON
            BBB.BBBID = c.BBBID and BBB.BBBBranchID = 0
    left outer join BusinessComplaintText t WITH (NOLOCK) ON
            c.BBBID = t.BBBID AND
            c.ComplaintID = t.ComplaintID
    left outer join ForeignCountries WITH (NOLOCK) on
            ForeignCountries.Country = c.ConsumerCountry
    left outer join tblStates WITH (NOLOCK) on
            tblStates.StateAbbreviation = c.ConsumerStateProvince
    left outer join tblStates tblStates2 WITH (NOLOCK) on
            tblStates2.StateAbbreviation = b.StateProvince
    left outer join tblMexicanStates WITH (NOLOCK) on
            b.Country = 'MEX' and
            tblMexicanStates.StateAbbreviation = b.StateProvince
    left outer join tblResolutionCode WITH (NOLOCK) ON
            c.CloseCode = tblResolutionCode.ResolutionCodeID
    left outer join tblClassification WITH (NOLOCK) ON
            tblClassification.ClassificationCode = c.ClassificationID1
    left outer join tblYPPA WITH (NOLOCK) ON
            c.BusinessTOBID = tblYPPA.yppa_code
    left outer join BusinessTOBID t2 WITH (NOLOCK) ON t2.BBBID = c.BBBID and t2.BusinessID = c.BusinessID and
        t2.TOBID = '90040-000' and (t2.IsPrimaryTOBID = '0' or t2.IsPrimaryTOBID = '')
    WHERE
      c.DateClosed >= GETDATE() - 1095
