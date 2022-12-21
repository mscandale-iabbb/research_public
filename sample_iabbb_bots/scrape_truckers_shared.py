
# scrape truckers

start_at = "dp"

delim = "\t"

#from urllib.request import urlopen
import time

def SetHomeDirectory():
    import os
    os.chdir("C:\\Users\\Matt Scandale\\OneDrive - Council of Better Business Bureaus, Inc\\Desktop")
SetHomeDirectory()

def StripTags(string):
    xlen = len(string)
    in_tag = False
    newstring = ""
    for i in range(0, xlen):
        if string[i:i+1] == "<":
            in_tag = True
            continue
        if string[i:i+1] == ">":
            in_tag = False
            continue
        if not in_tag:
            newstring = newstring + string[i:i+1]
    return newstring

def CleanCommonHTML(iNarrative):
    iNarrative = iNarrative.replace("<br>"," ").replace("<br/>"," ").replace("<br />"," ")
    iNarrative = iNarrative.replace("<p>"," ").replace("</p>"," ")
    iNarrative = iNarrative.replace("&nbsp;"," ")
    iNarrative = iNarrative.replace("&amp;","&")
    iNarrative = iNarrative.replace("&apos;","'")
    iNarrative = iNarrative.replace("&#39;","'")
    iNarrative = iNarrative.replace("&rsquo;","'")
    iNarrative = iNarrative.replace("&quot;","\"")
    return iNarrative

def TrimTrailingComma(string):
    if string[len(string) - 2: len(string)] == ", ":
        string = string[0: len(string) - 2]
    if string[len(string) - 1: len(string)] == ",":
        string = string[0: len(string) - 1]
    return string


original_iURL = "https://safer.fmcsa.dot.gov/keywordx.asp?searchstring=*[[string]]*"

from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By

driver = webdriver.Firefox()
driver.implicitly_wait(60)

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")
fh.write(
    "Series" + "\t" +
    "Name" + "\t" +
    "DBA" + "\t" +
    "Street" + "\t" +
    "City" + "\t" +
    "State" + "\t" +
    "Zip" + "\t" +
    "Phone" + "\t" +
    "Status" + "\t" +
    "\n"
)

letters = ["a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"]
search_strings = []
for letter in letters:
    for letter2 in letters:
        search_strings.append(letter + letter2)
search_strings.sort()
print(search_strings)
for search_string in search_strings:
    if search_string <= start_at:
        print("skipping " + search_string)
        continue

    # load page so can count search result links
    iURL = original_iURL.replace("[[string]]", search_string)
    driver.get(iURL)
    time.sleep(2)
    links = driver.find_elements(by=By.XPATH, value="/html/body/table[3]/tbody/tr/th/b/a")

    # count search result links
    number_links = 0
    for link in links:
        number_links += 1

    print(search_string, number_links)

    already_seen = 1
    while(True):

        driver.get(iURL)
        time.sleep(1)

        links = driver.find_elements(by=By.XPATH, value="/html/body/table[3]/tbody/tr/th/b/a")

        # if not loaded, retry
        retried = 0
        timedout = False
        while(len(links) == 0):
            print("trying again...")
            driver.refresh()
            links = driver.find_elements(by=By.XPATH, value="/html/body/table[3]/tbody/tr/th/b/a")
            retried += 1
            if retried > 10:
                timedout = True
                break
        if timedout == True:
            continue

        count = 0
        for link in links:
            count += 1
            if count < already_seen:
                continue            
            link.click()
            time.sleep(1)
            break

        already_seen += 1

        status = ""
        status_cell = driver.find_elements(by=By.XPATH, value="/html/body/p/table/tbody/tr[2]/td/table/tbody/tr[2]/td/center[1]/table/tbody/tr[3]/td[1]")
        if len(status_cell) >= 1:
            status = status_cell[0].get_attribute('innerHTML').strip()
            status = CleanCommonHTML(StripTags(status))

        name = ""
        name_cell = driver.find_elements(by=By.XPATH, value="/html/body/p/table/tbody/tr[2]/td/table/tbody/tr[2]/td/center[1]/table/tbody/tr[4]/td")
        if len(name_cell) >= 1:
            name = name_cell[0].get_attribute('innerHTML').strip()
            name = CleanCommonHTML(StripTags(name))

        dba = ""
        dba_cell = driver.find_elements(by=By.XPATH, value="/html/body/p/table/tbody/tr[2]/td/table/tbody/tr[2]/td/center[1]/table/tbody/tr[5]/td")
        if len(dba_cell) >= 1:
            dba = dba_cell[0].get_attribute('innerHTML').strip()
            dba = CleanCommonHTML(StripTags(dba))

        address = ""
        street = ""
        citystatezip = ""
        address_cell = driver.find_elements(by=By.XPATH, value="/html/body/p/table/tbody/tr[2]/td/table/tbody/tr[2]/td/center[1]/table/tbody/tr[6]/td")
        if len(address_cell) >= 1:
            address = address_cell[0].get_attribute('innerHTML').strip()
            if address.find("<br>") == -1:
                street = CleanCommonHTML(StripTags(address)).strip()
                citystatezip = ""
            else:
                fields = address.split("<br>")
                street = CleanCommonHTML(StripTags(fields[0])).strip()
                citystatezip = CleanCommonHTML(StripTags(fields[1])).strip()

        city = TrimTrailingComma(citystatezip[ : citystatezip.find(",")])
        state = citystatezip[citystatezip.find(",") + 2 : citystatezip.find(",") + 4]
        zip = citystatezip[citystatezip.rfind(" ") + 1 : ]

        phone = ""
        phone_cell = driver.find_elements(by=By.XPATH, value="/html/body/p/table/tbody/tr[2]/td/table/tbody/tr[2]/td/center[1]/table/tbody/tr[7]/td")
        if len(phone_cell) >= 1:
            phone = phone_cell[0].get_attribute('innerHTML')
            phone = CleanCommonHTML(StripTags(phone)).strip()

        output = \
            search_string + "-" + str(count) + "\t" + \
            name + "\t" + \
            dba + "\t" + \
            street + "\t" + \
            city + "\t" + \
            state + "\t" + \
            zip + "\t" + \
            phone + "\t" + \
            status + "\t" + \
            "\n"
        fh.write(output)
        print(output)

        if already_seen > number_links:
            break

fh.close()
driver.close()
print("end")
