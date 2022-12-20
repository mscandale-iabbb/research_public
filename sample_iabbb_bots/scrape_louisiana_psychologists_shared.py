
# scrape Louisiana psychologists

delim = "\t"

from urllib.request import urlopen
import time

def SetHomeDirectory():
    import os
    os.chdir("C:\\Users\\Matt Scandale\\OneDrive - Council of Better Business Bureaus, Inc\\Desktop")
SetHomeDirectory()


iURL = "https://www.lsbepportal.com/LicenseVerification.aspx"

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
    "FirstName" + "\t" +
    "MiddleName" + "\t" +
    "LastName" + "\t" +
    "Sex" + "\t" +
    "Address1" + "\t" +
    "Address2" + "\t" +
    "City" + "\t" +
    "State" + "\t" +
    "Zip" + "\t" +
    "Parish" + "\t" +
    "Country" + "\t" +
    "PhoneNumber" + "\t" +
    "Other Records Available" +
    "\n"
)

page = 1
true_page = 1
page_series = 1
while(True):
    if page == 1:
        # initial search form
        driver.execute_script("document.getElementById('ctl00_ContentPlaceHolder1_btnSearch').click()")
        print("waiting for rows to be retrieved")
        time.sleep(20) # important to wait
    elif page >= 2:
        # subsequent page links
        link = driver.find_element(by=By.XPATH, value="/html/body/form/table[2]/tbody/tr/td[1]/div/div[2]/div[1]/table/tbody/tr[7]/td/table/tbody/tr/td[" + str(page) + "]/a")
        link.click()
        time.sleep(3)

    for row_id in range (2, 7):
        driver.execute_script("document.getElementById('ctl00_ContentPlaceHolder1_vwResultsGrid_ctl0" + str(row_id) + "_lnkViewbutton').click()")
        #print("waiting for results table to be retrieved")
        time.sleep(5)

        details_table = driver.find_element(by=By.ID, value="ctl00_ContentPlaceHolder1_vwResultsDetails")
        html = details_table.get_attribute('innerHTML')

        detail_rows = details_table.find_elements(by=By.XPATH, value="tbody/tr")

        FirstName = ""
        MiddleName = ""
        LastName = ""
        Sex = ""
        Address1 = ""
        Address2 = ""
        City = ""
        State = ""
        Zip = ""
        Parish = ""
        Country = ""
        PhoneNumber = ""
        Other_Records_Available = ""
        last_value = ""
        for detail_row in detail_rows:
            cells = detail_row.find_elements(by=By.XPATH, value="td")
            cell_count = 0
            for cell in cells:
                cell_count += 1
                cell_value = cell.get_attribute('innerHTML')
                cell_value = cell_value.replace("&nbsp;"," ").replace("&amp;","&")
                if cell_count == 1:
                    if cell_value == "FirstName":
                        last_value = "FirstName"
                    if cell_value == "MiddleName":
                        last_value = "MiddleName"
                    if cell_value == "LastName":
                        last_value = "LastName"
                    if cell_value == "Sex":
                        last_value = "Sex"
                    if cell_value == "Address1":
                        last_value = "Address1"
                    if cell_value == "Address2":
                        last_value = "Address2"
                    if cell_value == "City":
                        last_value = "City"
                    if cell_value == "State":
                        last_value = "State"
                    if cell_value == "Zip":
                        last_value = "Zip"
                    if cell_value == "Parish":
                        last_value = "Parish"
                    if cell_value == "Country":
                        last_value = "Country"
                    if cell_value == "PhoneNumber":
                        last_value = "PhoneNumber"
                    if cell_value == "Other Records Available":
                        last_value = "Other Records Available"
                elif cell_count == 2:
                    if last_value == "FirstName":
                        FirstName = cell_value
                    if last_value == "MiddleName":
                        MiddleName = cell_value
                    if last_value == "LastName":
                        LastName = cell_value
                    if last_value == "Sex":
                        Sex = cell_value
                    if last_value == "Address1":
                        Address1 = cell_value
                    if last_value == "Address2":
                        Address2 = cell_value
                    if last_value == "City":
                        City = cell_value
                    if last_value == "State":
                        State = cell_value
                    if last_value == "Zip":
                        Zip = cell_value
                    if last_value == "Parish":
                        Parish = cell_value
                    if last_value == "Country":
                        Country = cell_value
                    if last_value == "PhoneNumber":
                        PhoneNumber = cell_value
                    if last_value == "Other Records Available":
                        Other_Records_Available = cell_value
        fh.write(
            str(page_series) + "\t" +
            str(page) + "\t" +
            str(true_page) + "\t" +
            FirstName + "\t" +
            MiddleName + "\t" +
            LastName + "\t" +
            Sex + "\t" +
            Address1 + "\t" +
            Address2 + "\t" +
            City + "\t" +
            State + "\t" +
            Zip + "\t" +
            Parish + "\t" +
            Country + "\t" +
            PhoneNumber + "\t" +
            Other_Records_Available +
            "\n"
        )
        print(
            page_series,
            page,
            true_page,
            FirstName,
            MiddleName,
            LastName,
            Sex,
            Address1,
            Address2,
            City,
            State,
            Zip,
            Parish,
            Country,
            PhoneNumber,
            Other_Records_Available
        )

    page += 1
    true_page += 1
    if page_series == 1 and page == 12:
        page = 3
        page_series += 1
    elif page_series >= 2 and page == 13:
        page = 3
        page_series += 1

fh.close()
driver.close()
print("end")
