
# scrape British Columbia acupuncturists

number_of_rows = 2090 # set this before running


delim = "\t"

from urllib.request import urlopen
import time

def SetHomeDirectory():
    import os
    os.chdir("C:\\Users\\Matt Scandale\\OneDrive - Council of Better Business Bureaus, Inc\\Desktop")
SetHomeDirectory()


iURL = "https://portal.ctcma.bc.ca/public"

from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By

driver = webdriver.Firefox()
driver.implicitly_wait(10)
driver.get(iURL)
print("Page title:", driver.title)

id_province_field = "ctl01_TemplateBody_WebPartManager1_gwpciPublicRegistry_ciPublicRegistry_ResultsGrid_Sheet0_Input5_TextBox1"
id_submit_button = "ctl01_TemplateBody_WebPartManager1_gwpciPublicRegistry_ciPublicRegistry_ResultsGrid_Sheet0_SubmitButton"
id_page_size = "ctl01_TemplateBody_WebPartManager1_gwpciPublicRegistry_ciPublicRegistry_ResultsGrid_Grid1_ctl00_ctl02_ctl00_ChangePageSizeTextBox"
id_change_page_size = "ctl01_TemplateBody_WebPartManager1_gwpciPublicRegistry_ciPublicRegistry_ResultsGrid_Grid1_ctl00_ctl02_ctl00_ChangePageSizeLinkButton"
id_results_grid = "ctl01_TemplateBody_WebPartManager1_gwpciPublicRegistry_ciPublicRegistry_ResultsGrid_Grid1_ctl00"

out_filename = "out.txt"
print("writing to " + out_filename)
fh = open(out_filename, "w", encoding="utf8")

time.sleep(1)
driver.execute_script("document.getElementById('" + id_province_field + "').value = 'BC'")
province = driver.find_element(by=By.ID, value=id_province_field)
print("Province: " + province.get_attribute('value'))

time.sleep(1)
driver.execute_script("document.getElementById('" + id_submit_button + "').click()")

time.sleep(1)
driver.execute_script("document.getElementById('" + id_page_size + "').value = '" + str(number_of_rows) + "'")

time.sleep(1)
driver.execute_script("document.getElementById('" + id_change_page_size + "').click()")

# THIS IS ABSOLUTELY CRITICAL TO GET ALL ROWS!
print("waiting for all rows to load")
time.sleep(15)

results_grid = driver.find_element(by=By.ID, value=id_results_grid)
rows = results_grid.find_elements(by=By.CLASS_NAME, value="rgRow")
print("Rows:", len(rows))

links = []
for row in rows:
    cells = row.find_elements(by=By.XPATH, value="td")
    cell = cells[0]
    anchors = cell.find_elements(by=By.XPATH, value="a")
    anchor = anchors[0]
    links.append(anchor)

print("Links:", len(links))
row_number = 0
for link in links:
    row_number += 1

    time.sleep(3)
    driver.switch_to.default_content()
    link.click()

    #iframe = driver.find_element_by_xpath("/html/body/form/div[1]/table/tbody/tr[" + str(xcount) + "]/td[2]/iframe")
    iframes = driver.find_elements(by=By.TAG_NAME, value="iframe")
    print("Frames:", len(iframes))
    framecount = 0
    for iframe in iframes:
        framecount += 1
        if framecount == 1:
            print("switching context to frame " + str(framecount))
            driver.switch_to.frame(iframe)
            time.sleep(2)

    #panel = driver.find_element(by=By.XPATH, value='//*[@id="ctl00_ContentPanel"]')
    #html = panel.get_attribute('innerHTML')

    person = driver.find_element(by=By.XPATH, value='//*[@id="ctl00_TemplateBody_WebPartManager1_gwpciNewContactMiniProfileCommon_ciNewContactMiniProfileCommon_contactName_fullName"]')
    person_value = person.get_attribute('innerHTML')

    address = driver.find_element(by=By.XPATH, value='//*[@id="ctl00_TemplateBody_WebPartManager1_gwpciAddress_ciAddress__address"]')
    address_value = address.get_attribute('innerHTML')
    
    phone = driver.find_element(by=By.XPATH, value='//*[@id="ctl00_TemplateBody_WebPartManager1_gwpciAddress_ciAddress__phoneNumber"]')
    phone_value = phone.get_attribute('innerHTML')

    email = driver.find_element(by=By.XPATH, value='//*[@id="ctl00_TemplateBody_WebPartManager1_gwpciAddress_ciAddress__email"]')
    email_value = email.get_attribute('innerHTML')

    company_value = "-"
    try:
        company = driver.find_element(by=By.XPATH, value='//*[@id="ctl00_TemplateBody_WebPartManager1_gwpciNewContactMiniProfileCommon_ciNewContactMiniProfileCommon_contactName_institute"]')
        company_value = company.get_attribute('innerHTML')
    except:
        pass

    print(person_value, address_value, phone_value, email_value, company_value)

    fh.write(
        person_value + "\t" +
        address_value + "\t" +
        phone_value + "\t" +
        email_value + "\t" +
        company_value + "\t" +
        "\n"
    )

    #close_buttons = driver.find_elements_by_xpath("/html/body/form/div[1]/table/tbody/tr[1]/td[2]/table/tbody/tr/td[3]/ul/li[3]/a")
    #close_buttons = driver.find_elements_by_class_name("rwCloseButton")
    #for close_button in close_buttons:
    #    close_button.click()

fh.close()

driver.close()

print("end")
