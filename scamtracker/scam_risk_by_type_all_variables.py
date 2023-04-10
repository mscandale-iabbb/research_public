
import connect_cdw
cursor = connect_cdw.connect()

import func
func.SetHomeDirectory()

ages = ["Any","0","1","2","3","4","5","6"]
militaries = ["Any","Active Duty","Military Spouse","Veteran",""]
countries = ["Any","USA","CAN"]
genders = ["Any","Male","Female"]

age_labels = {"Any":"Any", "0":"Unknown", "1":"18-24", "2":"25-34", "3":"35-44", "4":"45-54", "5":"55-64", "6":"65+"}
military_labels = {"Any":"Any", "Active Duty":"Active Duty", "Military Spouse":"Military Spouse", "Veteran":"Veteran", "":"Non-Military"}
country_labels = {"Any":"Any", "USA":"USA", "CAN":"Canada"}


query = f"""
    set nocount on
    /*declare @xyear int = '2022'*/
    declare @age_fieldname varchar(30) = 'Age' + '": "'
    declare @age_value varchar(20) = '[[AGE]]'
    declare @gender_fieldname varchar(30) = 'Gender' + '": "'
    declare @gender_value varchar(20) = '[[GENDER]]'
    declare @military_fieldname varchar(30) = 'Military' + '": "'
    declare @military_value varchar(20) = '[[MILITARY]]'
    declare @country_value varchar(20) = '[[COUNTRY]]'
    /* part 1 of 3 - proportion of all scams for each type */
    declare @totalcount as int
    create table #Temp (
        xtype		varchar(50),
        xcount		int
    )
    create table #Final1 (
        xtype		varchar(50),
        xcount		int,
        xproportion	decimal(20,4)
    )
    insert into #Temp
    select
        SUBSTRING(
            SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)),
            1,
            CHARINDEX('"',
                SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)))
                - 1
        ) as ScamType,
        count(*)
    from BusinessComplaint c WITH (NOLOCK)
    left outer join BusinessComplaintText t WITH (NOLOCK) on c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
    where
        c.ComplaintID like 'scam%' and
        /* YEAR(c.DateClosed) = @xyear and */
        (
            @age_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@age_fieldname,t.DesiredOutcome) + LEN(@age_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@age_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@age_fieldname,t.DesiredOutcome) + LEN(@age_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@age_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @age_value
        ) and
        (
            @military_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@military_fieldname,t.DesiredOutcome) + LEN(@military_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@military_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@military_fieldname,t.DesiredOutcome) + LEN(@military_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@military_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @military_value
        ) and
        (
            @gender_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@gender_fieldname,t.DesiredOutcome) + LEN(@gender_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@gender_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@gender_fieldname,t.DesiredOutcome) + LEN(@gender_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@gender_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @gender_value
        ) and
        (
            @country_value = 'Any' or c.ConsumerCountry = @country_value
        )
    group by
        SUBSTRING(
            SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)),
            1,
            CHARINDEX('"',
                SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)))
                - 1
        )
    set @totalcount = (Select sum(xcount) from #Temp)
    insert into #Final1
        select
            xtype,
            xcount,
            (xcount / cast(@totalcount as decimal(10,4)))
        from #Temp
    /* part 2a of 3 - overall median */
    declare @overall_median decimal(38,4)
    declare @overall_count int
    create table #Pre (
        xamount		decimal(38,4)
    )
    create table #Pre2 (
        xnum			int,
        xamount		decimal(38,4)
    )
    insert into #Pre
    select
        case when len(c.ConsumerPostalCode) !=5 then cast(c.AmountDisputed as decimal(38,4)) * 0.77 else c.AmountDisputed end
    from BusinessComplaint c WITH (NOLOCK)
    left outer join BusinessComplaintText t WITH (NOLOCK) on c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
    where
        c.ComplaintID like 'scam%' and
        /*YEAR(c.DateClosed) = @xyear and*/
        c.AmountDisputed != '0.0000'
    set @overall_count = (select count(*) from #Pre)
    insert into #Pre2
    select
        ROW_NUMBER() over (order by #Pre.xamount),
        #Pre.xamount
    from #Pre
    set @overall_median = (
        select AVG(xamount) from #Pre2 where
            #Pre2.xnum >= FLOOR(@overall_count / 2.0) and
            #Pre2.xnum <= CEILING(@overall_count / 2.0)
    )
    /* part 2b of 3 - medians by type */
    create table #Temp2 (
        xtype		varchar(50),
        xamount		decimal(38,4)
    )
    create table #Temp2a (
        xtype		varchar(50),
        xcount		int
    )
    create table #Temp2b (
        xtype		varchar(50),
        xlower		int,
        xupper		int
    )
    create table #Temp2c (
        xrownumber	int,
        xtype		varchar(50),
        xamount		decimal(38,4)
    )
    create table #Final2 (
        xtype		varchar(50),
        xmedian		decimal(38,4)
    )
    insert into #Temp2
    select
        SUBSTRING(
            SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)),
            1,
            CHARINDEX('"',
                SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)))
                - 1
        ) as ScamType,
        case when len(c.ConsumerPostalCode) !=5 then cast(c.AmountDisputed as decimal(38,4)) * 0.77 else c.AmountDisputed end
    from BusinessComplaint c WITH (NOLOCK)
    left outer join BusinessComplaintText t WITH (NOLOCK) on c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
    where
        c.ComplaintID like 'scam%' and
        /* YEAR(c.DateClosed) = @xyear and */
        c.AmountDisputed != '0.0000' and
        (
            @age_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@age_fieldname,t.DesiredOutcome) + LEN(@age_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@age_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@age_fieldname,t.DesiredOutcome) + LEN(@age_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@age_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @age_value
        ) and
        (
            @military_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@military_fieldname,t.DesiredOutcome) + LEN(@military_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@military_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@military_fieldname,t.DesiredOutcome) + LEN(@military_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@military_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @military_value
        ) and
        (
            @gender_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@gender_fieldname,t.DesiredOutcome) + LEN(@gender_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@gender_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@gender_fieldname,t.DesiredOutcome) + LEN(@gender_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@gender_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @gender_value
        ) and
        (
            @country_value = 'Any' or c.ConsumerCountry = @country_value
        )
    insert into #Temp2a
        select xtype, count(*) from #Temp2 group by xtype order by xtype
    insert into #Temp2b
        select
            xtype,
            FLOOR(xcount / 2.0),
            CEILING(xcount / 2.0)
        from #Temp2a
    insert into #Temp2c
    select
            ROW_NUMBER() over (partition by #Temp2.xtype order by #Temp2.xtype, #Temp2.xamount),
            #Temp2.xtype,
            #Temp2.xamount
        from #Temp2
        inner join #Temp2b on #Temp2b.xtype = #Temp2.xtype
    insert into #Final2
        select
                #Temp2c.xtype,
                AVG(#Temp2c.xamount) as Median
            from #Temp2c
            inner join #Temp2b on #Temp2b.xtype = #Temp2c.xtype
            where
                #Temp2c.xrownumber = #Temp2b.xlower or #Temp2c.xrownumber = #Temp2b.xupper
            group by #Temp2c.xtype
    /* part 3 of 3 - proportion of all scams in that type that lost money for each type */
    create table #Temp3 (
        xtype		varchar(50),
        xcount		int
    )
    create table #Final3 (
        xtype		varchar(50),
        xproportion	decimal(20,4)
    )
    insert into #Temp3
    select
        SUBSTRING(
            SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)),
            1,
            CHARINDEX('"',
                SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)))
                - 1
        ) as ScamType,
        count(*)
    from BusinessComplaint c WITH (NOLOCK)
    left outer join BusinessComplaintText t WITH (NOLOCK) on c.BBBID = t.BBBID AND c.ComplaintID = t.ComplaintID
    where
        c.ComplaintID like 'scam%' and
        /* YEAR(c.DateClosed) = @xyear and */
        c.AmountDisputed != '0.0000' and
        (
            @age_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@age_fieldname,t.DesiredOutcome) + LEN(@age_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@age_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@age_fieldname,t.DesiredOutcome) + LEN(@age_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@age_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @age_value
        ) and
        (
            @military_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@military_fieldname,t.DesiredOutcome) + LEN(@military_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@military_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@military_fieldname,t.DesiredOutcome) + LEN(@military_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@military_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @military_value
        ) and
        (
            @gender_value = 'Any' or
            SUBSTRING(
                SUBSTRING(t.DesiredOutcome, CHARINDEX(@gender_fieldname,t.DesiredOutcome) + LEN(@gender_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@gender_fieldname,t.DesiredOutcome)),
                1,
                CHARINDEX('"',
                    SUBSTRING(t.DesiredOutcome, CHARINDEX(@gender_fieldname,t.DesiredOutcome) + LEN(@gender_fieldname), LEN(t.DesiredOutcome) - CHARINDEX(@gender_fieldname,t.DesiredOutcome)))
                    - 1
            ) = @gender_value
        ) and
        (
            @country_value = 'Any' or c.ConsumerCountry = @country_value
        )
    group by
        SUBSTRING(
            SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)),
            1,
            CHARINDEX('"',
                SUBSTRING(t.DesiredOutcome, CHARINDEX('ScamType": "',t.DesiredOutcome) + 12, LEN(t.DesiredOutcome) - CHARINDEX('ScamType": "',t.DesiredOutcome)))
                - 1
        )
    insert into #Final3
        select
            #Temp3.xtype,
            (#Temp3.xcount / cast(#Temp.xcount as decimal(20,4)))
        from #Temp3
        inner join #Temp on #Temp.xtype = #Temp3.xtype
    select
            #Final1.xtype,
            (#Final1.xproportion * (#Final2.xmedian / @overall_median) * #Final3.xproportion) * 1000 as RiskIndex,
            #Final1.xcount,
            #Final1.xproportion as Exposure,
            (#Final2.xmedian / @overall_median) as Median,
            #Final3.xproportion as Susceptibility
        from #Final1
        left outer join #Final2 on #Final2.xtype = #Final1.xtype
        left outer join #Final3 on #Final3.xtype = #Final1.xtype
        order by (#Final1.xproportion * #Final2.xmedian * #Final3.xproportion) desc
    drop table #Pre
    drop table #Pre2
    drop table #Temp
    drop table #Temp2
    drop table #Temp2a
    drop table #Temp2b
    drop table #Temp2c
    drop table #Temp3
    drop table #Final1
    drop table #Final2
    drop table #Final3
"""

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")
fh.write("Consumer Age" + "\t" + "Consumer Military Status" + "\t" + "Consumer Country" + "\t" + "Consumer Gender" + "\t" + "Scam Type 1 (Risk Index, Scam Count, Exposure, Susceptibility, Median)" + "\t" + "Scam Type 2 (Risk Index, Scam Count, Exposure, Susceptibility, Median)" + "\t" + "Scam Type 3 (Risk Index, Scam Count, Exposure, Susceptibility, Median)" + "\t" + "Scam Type 4 (Risk Index, Scam Count, Exposure, Susceptibility, Median)" + "\t" + "Scam Type 5 (Risk Index, Scam Count, Exposure, Susceptibility, Median)" + "\n")
original_query = query
for age in ages:
    for military in militaries:
        for country in countries:
            for gender in genders:
                query = original_query
                query = query.replace("[[AGE]]",age).replace("[[MILITARY]]",military).replace("[[COUNTRY]]",country).replace("[[GENDER]]",gender)
                cursor.execute(query)
                results = []
                results_count = 0
                for entry in cursor:
                    oType = func.CleanString(func.NotNone(entry[0]))
                    oRiskIndex = func.NotNone(entry[1])
                    oCount = func.NotNone(entry[2])
                    oExposure = func.NotNone(entry[3])
                    oMedian = func.NotNone(entry[4])
                    oSusceptibility = func.NotNone(entry[5])
                    if func.IsFloat(oExposure):
                        oExposure = func.FormatPercentage(oExposure)
                    if func.IsFloat(oSusceptibility):
                        oSusceptibility = func.FormatPercentage(oSusceptibility)
                    if func.IsFloat(oMedian):
                        oMedian = round(oMedian,1)
                    oDisplayRiskIndex = "-"
                    if func.IsFloat(oRiskIndex) == True or func.IsNumber(oRiskIndex) == True:
                        oDisplayRiskIndex = str(round(oRiskIndex,0))
                    result = oType + " (" + oDisplayRiskIndex + ", " + str(oCount) + ", " + str(oExposure) + ", " + str(oSusceptibility) + ", " + str(oMedian) + ")"
                    results.append(result)
                    results_count += 1
                    if results_count >= 5:
                        break
                print("Age: " + age_labels[age] + ", Military: " + military_labels[military] + ", Country: " + country_labels[country] + ", Gender: " + gender, results)
                fh.write(age_labels[age] + "\t" + military_labels[military] + "\t" + country_labels[country] + "\t" + gender + "\t")
                for result in results:
                    fh.write(result + "\t")
                fh.write("\n")                

fh.close()
