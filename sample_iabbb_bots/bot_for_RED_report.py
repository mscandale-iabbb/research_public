
import time
from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By

def SetHomeDirectory():
    import os
    os.chdir("C:\\Users\\Matt Scandale\\OneDrive - Council of Better Business Bureaus, Inc\\Desktop")
SetHomeDirectory()


# load browser

iURL = "https://bbb-services.bbb.org/intranet/red_scheduled_tasks.php"
driver = webdriver.Firefox()
driver.implicitly_wait(15)
driver.get(iURL)
print("Page title:", driver.title)
time.sleep(1)


# log into intranet

username = "//*[@id=\"iUsername\"]"
password = "//*[@id=\"iPassword\"]"
submit = "//*[@id=\"Button1\"]"
test = driver.find_elements_by_xpath(username)
test[0].send_keys('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx@iabbb.org')
test = driver.find_elements_by_xpath(password)
test[0].send_keys('yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy')
test = driver.find_elements_by_xpath(submit)
test[0].click()
time.sleep(2)


# click Testing checkbox to send emails to IABBB instead of BBBs

testing = "/html/body/div[1]/div/div[3]/div/div[3]/div/span/p[2]/input[1]"
checkbox = driver.find_elements_by_xpath(testing)
checkbox[0].click()
time.sleep(2)


# click all the (400 or so) "Run" buttons, when clickable, pausing and scrolling as needed

row_number = 0
while True:
    row_number += 1

    buttons = driver.find_elements_by_xpath("/html/body/div[1]/div/div[3]/div/div[3]/div/span/table/tbody/tr[" + str(row_number) + "]/td[3]/input")
    while buttons[0].is_enabled() == False:
        print("waiting 4 seconds")
        time.sleep(4)
    buttons[0].click()
    print("clicked " + str(row_number))
    if row_number >= 1:
        driver.execute_script("window.scrollBy(0, 28);")
    time.sleep(2)


driver.close()
print("end")
