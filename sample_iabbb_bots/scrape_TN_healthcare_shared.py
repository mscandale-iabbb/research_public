
# scrape Tennessee healthcare providers

start_at = 32

delim = "\t"

from urllib.request import urlopen
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

iURL = "https://apps.health.tn.gov/facilityListings/"

from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By

driver = webdriver.Firefox()
driver.implicitly_wait(10)
driver.get(iURL)
print("Page title:", driver.title)
time.sleep(1)

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")
fh.write(
    "Series" + "\t" +
    "Name" + "\t" +
    "Address" + "\t" +
    "City" + "\t" +
    "State" + "\t" +
    "Zip" + "\t" +
    "First Name" + "\t" +
    "Last Name" + "\t" +
    "Phone" + "\t" +
    "License #" + "\t" +
    "Status" + "\t" +
    "Expiration" + "\t" +
    "\n"
)

facility_type = driver.find_element(by=By.ID, value="CurrentSearchModel_FacilityType")
facility_type.click()
#options_html = facility_type.get_attribute('innerHTML').strip()
time.sleep(2)

for option_number in range(start_at, 39):

    option = driver.find_element(by=By.XPATH, value="/html/body/div[1]/div/div/form[1]/div/div[1]/select/option[" + str(option_number) + "]")
    option.click()
    series = option.get_attribute('innerHTML')
    print(option_number, "-", series)
    time.sleep(2)
    
    submit_button = driver.find_element(by=By.XPATH, value="/html/body/div[1]/div/div/form[1]/div/div[5]/input")
    submit_button.click()
    time.sleep(9)

    results_table = driver.find_element(by=By.XPATH, value="/html/body/div/div/table")
    results_rows = driver.find_elements(by=By.XPATH, value="/html/body/div/div/table/tbody/tr")

    values = []
    for results_row in results_rows:
        cells = results_row.find_elements(by=By.XPATH, value="td")
        if len(cells) != 4:
            continue
        cell_count = 0
        values = []
        for cell in cells:
            cell_count += 1
            if cell_count == 1 or cell_count == 3:
                continue
            cell_value = cell.get_attribute('innerHTML').strip()
            #fields = cell_value.split("<br>")
            cell_value = StripTags(cell_value)
            fields = cell_value.split("\n")
            for field in fields:
                field = field.strip()
                field = CleanCommonHTML(field)
                values.append(field)

        if values[0].find("                ") == -1:
            name = values[0]
            street = values[1]
            citystatezip = values[2]
            personphone = values[3].replace("Attn: ","")
            license_number = values[4].replace("Facility License Number: ","")
            license_status = values[5].replace("Status: ","")
            license_expires = values[7].replace("Date of Expiration: ","")
        else:
            subvalues = values[0].split("                ")
            name = subvalues[0]
            street = subvalues[1]
            citystatezip = values[1]
            personphone = values[2].replace("Attn: ","")
            license_number = values[3].replace("Facility License Number: ","")
            license_status = values[4].replace("Status: ","")
            license_expires = values[6].replace("Date of Expiration: ","")

        if license_status == "Qualifications" or license_status == "Certified Counties":
            license_status = ""

        city = TrimTrailingComma(citystatezip[ : citystatezip.find(",")])
        state = citystatezip[citystatezip.find(",") + 2 : citystatezip.find(",") + 4]
        zip = citystatezip[citystatezip.rfind(" ") + 1 : ]

        personphone_fields = personphone.split("                 ")
        if len(personphone_fields) == 2:
            person = personphone_fields[0]
            first_name = person[ : person.find(" ")].strip()
            last_name = person[person.find(" ") : ].strip()
            phone = personphone_fields[1]
        else:
            first_name = ""
            last_name = ""
            phone = personphone

        output = \
            series + "\t" + \
            name + "\t" + \
            street + "\t" + \
            city + "\t" + \
            state + "\t" + \
            zip + "\t" + \
            first_name + "\t" + \
            last_name + "\t" + \
            phone + "\t" + \
            license_number + "\t" + \
            license_status + "\t" + \
            license_expires + "\t" + \
            "\n"
        fh.write(output)
        print(output)
        #time.sleep(1)

    return_button = driver.find_element(by=By.XPATH, value="/html/body/div/div/div[1]/div[2]/a")
    return_button.click()
    time.sleep(2)

fh.close()
driver.close()
print("end")
