
# scrape Louisiana physical therapists from https://www.laptboard.org/index.cfm/pt-search

delim = "\t"

from urllib.request import urlopen

def SetHomeDirectory():
    import os
    os.chdir("C:\\Users\\Matt Scandale\\OneDrive - Council of Better Business Bureaus, Inc\\Desktop")
SetHomeDirectory()

def FindAll(text, word):
    positions = []
    foundpos = -1
    lastpos = 0
    #found = False
    while True:
        foundpos = text.find(word, lastpos)
        if foundpos == -1:
            break
        positions.append(foundpos)
        lastpos = foundpos + 1
    return positions

def StripPunctuation(string):
    import re
    string = re.sub(r'[^\w\s$]','',string) # \w is alphanumeric (or underscore), \s is whitespace, $ is dollar sign
    string = string.replace('_','')
    string = string.strip()
    return string

def StripSpaces(string):
    string = string.replace(' ','')
    return string

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")

tag = "<h4>CLINIC</h4>"

for offset in range(1, 6525, 8):
#for offset in range(1, 130, 8):
    iURL = "https://www.laptboard.org/index.cfm/pt-search?t=n&q=s&si=" + str(offset)
    print(iURL)

    try:
        resp = urlopen(iURL, timeout=45)
        raw_html = str(resp.read())

        positions = FindAll(raw_html, tag)
        for position in positions:
            start_position = position + len(tag)
            end_position = raw_html.find("<h4>", start_position + 5)
            chunk = raw_html[start_position : end_position]
            chunk = chunk.replace("\\r\\n                      \\r\\n                      \\r\\n                        \\r\\n                      \\r\\n","")
            chunk = chunk.strip()
            fields = chunk.split("<br>")

            street = ""
            street2 = ""
            citystatezip = ""
            phone = ""
            extra = ""
            name = fields[0][28 : ]
            if len(fields) >= 2:
                street = fields[1][28 : ]
            if len(fields) >= 3:
                citystatezip = fields[2][28 : ]
            if len(fields) >= 4:
                phone = fields[3][28 : ]
            if len(fields) >= 5:
                extra = fields[4][28 : ]

            # realign if citystatezip doesn't contain comma
            if citystatezip.find(",") == -1:
                if len(fields) >= 4:
                    street2 = citystatezip
                    citystatezip = phone
                    phone = extra
                    extra = ""
                if len(fields) == 3:
                    phone = citystatezip
                    citystatezip = street
                    street = name
                    name = "-"
                    extra = ""

            city = citystatezip[ : citystatezip.find(",")]
            statezip = citystatezip[citystatezip.find(",") + 1 : ]
            statezip = statezip.strip()
            state = statezip[ : statezip.find(" ")]
            zip = statezip[statezip.find(" ") : ]
            
            phone = StripPunctuation(StripSpaces(phone))

            fh.write(
                #citystatezip + delim +
                name + delim +
                street + delim +
                street2 + delim +
                city + delim +
                state + delim +
                zip + delim +
                phone + delim +
                extra + delim +
                "\n"
            )
            print(name, "-", len(fields), "fields")

        resp.close()

    except Exception as e:
        print("failure", iURL, e)
        pass

fh.close()
print("end")
