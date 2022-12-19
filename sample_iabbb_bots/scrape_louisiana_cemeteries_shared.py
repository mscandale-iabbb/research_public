
# scrape Louisiana cemteries from www.lcb.state.la.us/all.php

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

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")

tag = "<tr><td width=\"400\" valign=\"top\"><font face=\"Verdana,Geneva,Arial,Helvetica,sans-serif,sans-serif\" size=\"2\" color=\"#000000\"><br><b><u>"

for offset in range(0, 2041, 30):
    iURL = "http://www.lcb.state.la.us/all.php?city=&offset=" + str(offset)
    print(iURL)

    try:
        resp = urlopen(iURL, timeout=45)
        raw_html = str(resp.read())

        positions = FindAll(raw_html, tag)
        for position in positions:
            start_position = position + len(tag)
            end_position = raw_html.find("</td>", start_position)
            chunk = raw_html[start_position : end_position]
            chunk = chunk.replace("</u>","").replace("</font>","").replace("</b>","").replace("&nbsp;"," ").replace("\\'","'")
            fields = chunk.split("<br>")

            city = fields[2][ : fields[2].find(",")]
            zip = fields[2][fields[2].find(" LA ") + 4 : ]

            fh.write(
                fields[0] + delim +
                fields[1] + delim +
                city + delim +
                "LA" + delim +
                zip + delim +
                fields[3] + delim +
                fields[4] + delim +
                fields[5] + delim +
                "\n"
            )
            print(fields[0])

        resp.close()

    except Exception as e:
        print("failure", iURL, e)
        pass

fh.close()
print("end")
