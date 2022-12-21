
# scrape Louisiana drug distributors from http://www.lsbwdd.org/wholesaler-license-lookup/

delim = "\t"

from urllib.request import urlopen

def SetHomeDirectory():
    import os
    os.chdir("C:\\Users\\Matt Scandale\\OneDrive - Council of Better Business Bureaus, Inc\\Desktop")
SetHomeDirectory()

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")

#for offset in range(2800, 3900, 1):
for offset in range(1, 13746, 1):
    iURL = "http://www.lsbwdd.org/forms/license_detail.php?id=" + str(offset)

    try:
        resp = urlopen(iURL, timeout=45)
        text = str(resp.read())
        fields = text.split("<br />")
        results = []
        for field in fields:
            field = field.strip()
            if field.find("<span ") != -1:
                field = field.replace("<span class=\"bold\">","").replace("<span class=\"bold underline\">","").replace("\\n","").replace("<span class=\"text\">","").replace("&nbsp;"," ").replace("</span>","|")
                field = field.replace(":|",":").replace(": |",":").replace(":  |",":")
                field = field.strip()
                if field[len(field) - 1 : ] == "|":
                    field = field[ : len(field) - 1]
                if field == "Business Location:" or field == "Distribution Location:":
                    continue
                if field.find("|") == -1:
                    results.append(field)
                else:
                    subfields = field.split("|")
                    for subfield in subfields:
                        subfield = subfield.strip()
                        results.append(subfield)

        name = ""
        street = ""
        city = ""
        state = ""
        zip = ""
        phone = ""
        fax = ""
        for result in results:
            if result.find("Company: ") != -1:
                name = result
            if result.find("DBA: ") != -1 or result.find("dba: ") != -1:
                dba = result[4: ].strip()
                if dba[ : 4] == "dba " or dba[ : 4] == "DBA ":
                    dba = dba[4: ]
                if dba[ : 6] == "d/b/a " or dba[ : 6] == "D/B/A ":
                    dba = dba[6: ]
                name += " / " + dba
            if result.find("Address: ") != -1:
                street = result
            if result.find("City: ") != -1:
                city = result
            if result.find("State: ") != -1:
                state = result
            if result.find("Zip: ") != -1:
                zip = result
            if result.find("Company: ") != -1:
                name = result
            if result.find("Telephone #: ") != -1:
                phone = result
            if result.find("Fax #: ") != -1:
                fax = result

        name = name[ name.find(":") + 1 : ].strip()
        street = street[ street.find("Address:") + 9 : ].strip()
        city = city[ city.find("City:") + 5 : ].strip()
        state = state[ state.find("State:") + 6 : ].strip()
        zip = zip[ zip.find("Zip:") + 4 : ].strip()
        phone = phone[ phone.find("Telephone #:") + 12 : ].strip()
        fax = fax[ fax.find("Fax #:") + 7 : ].strip()

        if name[len(name) - 1 : ] == ":":
            name = ""
        if street[len(street) - 1 : ] == ":":
            street = ""
        if city[len(city) - 1 : ] == ":":
            city = ""
        if state[len(state) - 1 : ] == ":":
            state = ""
        if zip[len(zip) - 1 : ] == ":":
            zip = ""
        if phone[len(phone) - 1 : ] == ":":
            phone = ""
        if fax[len(fax) - 1 : ] == ":":
            fax = ""

        if name[ : 4] == "ZZZ-" or name[ : 4] == "zzz-":
            #name = name[4: ] # + " (ZZZ)"
            #name = name.strip()
            name = ""
            street = ""
            city = ""
            state = ""
            zip = ""
            phone = ""
            fax = ""

        phone = phone.replace("(","").replace(")","").replace("-","").replace(" ","")
        phone = phone[0 : 10]
        if len(phone) < 10:
            phone = ""

        fax = fax.replace("(","").replace(")","").replace("-","").replace(" ","")
        fax = fax[0 : 10]
        if len(fax) < 10:
            fax = ""

        if zip[len(zip) - 1 : ] == "-":
            zip = zip[ : len(zip) - 1]

        if name > "":
            fh.write(
                name + delim +
                street + delim +
                city + delim +
                state + delim +
                zip + delim +
                phone + delim +
                #fax + delim +
                "\n"
            )
            print(name)

        resp.close()

    except Exception as e:
        print("failure", iURL, e)
        pass

fh.close()
print("end")
