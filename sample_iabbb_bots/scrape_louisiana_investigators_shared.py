
# scrape Louisiana investigators

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
    
def TrimTrailingComma(string):
    if string[len(string) - 2: len(string)] == ", ":
        string = string[0: len(string) - 2]
    if string[len(string) - 1: len(string)] == ",":
        string = string[0: len(string) - 1]
    return string


iURL = "https://lsbpie.com/search.aspx"

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
    "Page" + "\t" +
    "True Page" + "\t" +
    "Name" + "\t" +
    "Address" + "\t" +
    "City" + "\t" +
    "State" + "\t" +
    "Zip" + "\t" +
    "Phone" + "\t" +
    "License" + "\t" +
    "Expiration" + "\t" +
    "\n"
)

page = 1
true_page = 1
page_series = 1
while(True):

    print(page, true_page)
    if page == 1:
        agency_tab = driver.find_element(by=By.XPATH, value="/html/body/form/div[3]/div/table/tbody/tr/td[2]/table/tbody/tr[1]/td/div/div[2]/div/div[2]/ul/li[2]/a")
        agency_tab.click()
        time.sleep(1)
    elif page >= 2:
        link = driver.find_element(by=By.XPATH, value="/html/body/form/div[3]/div/table/tbody/tr/td[2]/table/tbody/tr[1]/td/div/div[2]/div/div[2]/div[2]/div/div/table/tbody/tr[2]/td/div/table/tbody/tr[27]/td/table/tbody/tr/td["+ str(page) + "]/a")
        link.click()
        time.sleep(1)

    results_table = driver.find_element(by=By.ID, value="ctl00_ContentPlaceHolder1_ucAgencies_gridAgency")
    results_rows = results_table.find_elements(by=By.XPATH, value="tbody/tr")

    values = []
    for results_row in results_rows:
        cells = results_row.find_elements(by=By.XPATH, value="td")
        if len(cells) != 6:
            continue
        cell_count = 0
        values = []
        for cell in cells:
            cell_count += 1
            cell_value = cell.get_attribute('innerHTML').strip()
            cell_value = StripTags(cell_value)
            cell_value = cell_value.replace("&amp;","&")
            values.append(cell_value)
        print(values)
        name = values[0]
        license = values[1]
        expiration = values[2]
        citystate = values[3]
        address = values[4]
        phone = values[5]
        street = address[ : address.find(citystate) - 1]
        street = TrimTrailingComma(street)
        city = citystate[ : citystate.find(",")]
        state = citystate[len(citystate) - 2 : ]
        zip = address[len(address) - 5 : ]
        fh.write(
            str(page_series) + "\t" +
            str(page) + "\t" +
            str(true_page) + "\t" +
            name + "\t" +
            street + "\t" +
            city + "\t" +
            state + "\t" +
            zip + "\t" +
            phone + "\t" +
            license + "\t" +
            expiration + "\t" +
            "\n"
        )

    page += 1
    true_page += 1
    if page_series == 1 and page == 12:
        page = 4        
        page_series += 1
    elif page_series >= 2 and page == 14:
        page = 4
        page_series += 1

fh.close()
driver.close()
print("end")
