
# scrape Ohio car dealers - runs on Windows or Linux

Descending = False
start_at = 330

iURL = "https://bmvonline.dps.ohio.gov/bmvonline/search/dealer?handler=DealerSearch"

from urllib.request import urlopen
import time
import sys

def SetHomeDirectory():
    import os
    os.chdir("C:\\Users\\Matt Scandale\\OneDrive - Council of Better Business Bureaus, Inc\\Desktop")
SetHomeDirectory()

def CleanString(string):
    string = string.replace("\x00", " ")
    string = string.replace("\n", " ")
    string = string.replace("\r", " ")
    string = string.replace("\t", " ")
    string = string.replace("’", "`")
    string = string.replace("&rsquo;","`")
    while string.find("  ") >= 0:
        string = string.replace("  ", " ")
        string = string.strip()
    return string

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

from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.firefox.options import Options
from selenium.webdriver.common.action_chains import ActionChains

options = Options()
options.headless = False
if sys.version.find("Red Hat") != -1:
    options.headless = True

if sys.version.find("Red Hat") == -1:
    driver = webdriver.Firefox(options=options, executable_path="geckodriver")
else:
    driver = webdriver.Firefox(options=options, executable_path="/home/ec2-user/mattjs/geckodriver")
driver.set_window_size(1600, 1050)
driver.implicitly_wait(10)
driver.get(iURL)
print("Page title:", driver.title)
time.sleep(1)

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")
fh.write(
    "Page" + "\t" +
    "Name" + "\t" +
    "Address" + "\t" +
    "City" + "\t" +
    "State" + "\t" +
    "Zip" + "\t" +
    "Phone" + "\t" +
    "Email" + "\t" +
    "License Status" + "\t" +
    "Expiration" + "\t" +
    "Phone 2" + "\t" +
    "Fax" + "\t" +
    "DBA" + "\t" +
    "\n"
)

page = 1
increment_amount = 1
if Descending == True:
    increment_amount = -1
while(True):

    print(page)
    if page == 1:
        driver.execute_script("document.getElementById('DealerData_Parameters_BusinessName').value = 'a'")
        time.sleep(1)
        button = driver.find_element(by=By.ID, value="btnSearch")
        button.click()
        time.sleep(3)
        if Descending == True:
            # last page
            link = driver.find_element(by=By.NAME, value="Last")
            link.click()
            time.sleep(3)
            # second last page (because last doesn't have 10 rows)
            link = driver.find_element(by=By.NAME, value="Previous")
            link.click()
            time.sleep(3)
            current_page_link = driver.find_element(by=By.XPATH, value="/html/body/article/section/div/div[2]/div/form/div[2]/div/div[2]/table/tfoot/tr/td/div/ul/li[7]/a")
            current_page_value = current_page_link.get_attribute('innerHTML').strip()
            print(current_page_value)
            page = int(current_page_value)
        if start_at > 0:
            page = 5
            link = driver.find_element(by=By.XPATH, value="/html/body/article/section/div/div[2]/div/form/div[2]/div/div[2]/table/tfoot/tr/td/div/ul/li[5]/a")
            link.click()
            time.sleep(2)
            while(True):
                page += 4
                print("forwarding to page " + str(page) + "...")
                link = driver.find_element(by=By.XPATH, value="/html/body/article/section/div/div[2]/div/form/div[2]/div/div[2]/table/tfoot/tr/td/div/ul/li[11]/a")
                link.click()
                time.sleep(2)
                if page >= start_at:
                    break
    elif page >= 2:
        link = driver.find_element(by=By.NAME, value=str(page))
        link.click()
        time.sleep(4)

    for results_row_number in range(0, 10):
        link = driver.find_element(by=By.ID, value="details_" + str(results_row_number))
        link.click()
        time.sleep(4)

        details_table = driver.find_element(by=By.XPATH, value="/html/body/article/section/div/div[1]/div[2]/div/table")
        details_rows = details_table.find_elements(by=By.XPATH, value="tbody/tr")
        values = []
        for details_row in details_rows:
            cells = details_row.find_elements(by=By.XPATH, value="td")
            cell_count = 0
            for cell in cells:
                cell_count += 1
                if cell_count == 1:
                    continue
                cell_value = cell.get_attribute('innerHTML').strip()
                cell_value = cell_value.replace("&amp;","&")
                values.append(cell_value)
        name = CleanString(values[0])
        address = CleanString(values[1])
        phone = CleanString(StripTags(values[2]))
        phone2 = CleanString(StripTags(values[3]))
        fax = CleanString(StripTags(values[4]))
        email = CleanString(StripTags(values[5]))
        website = CleanString(values[6])
        status = CleanString(values[7])
        expiration = CleanString(values[8])
        dba = CleanString(StripTags(values[9]))

        address_fields = address.split("<br>")
        street = StripTags(address_fields[0])
        citystatezip = StripTags(address_fields[1])
        city = citystatezip[ : citystatezip.find(",")]
        city = TrimTrailingComma(city)
        state = citystatezip[citystatezip.find(",") + 2 : citystatezip.find(",") + 4]
        zip = citystatezip[citystatezip.rfind(" ") + 1 : ]
        fh.write(
            str(page) + "\t" +
            name + "\t" +
            street + "\t" +
            city + "\t" +
            state + "\t" +
            zip + "\t" +
            phone + "\t" +
            email + "\t" +
            status + "\t" +
            expiration + "\t" +
            phone2 + "\t" +
            fax + "\t" +
            dba + "\t" +
            "\n"
        )
        print(page, name, street, city, state, zip, phone, email, status, expiration, phone2, fax, dba)
        link = driver.find_element(by=By.ID, value="btnClose")
        link.click()
        time.sleep(4)
 
    page += increment_amount

fh.close()
driver.close()
print("end")
