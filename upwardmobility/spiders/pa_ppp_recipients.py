from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import time

class PaPppRecipientsSpider(scrapy.Spider):
    handle_httpstatus_list = [404, 403]
    name = 'pa_ppp_recipients'
    allowed_domains = ['federalpay.org']
    start_urls = ['https://www.federalpay.org/paycheck-protection-program/pa']

    @staticmethod
    def create_options():
        options = Options()
        options.add_argument('--headless')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--remote-debugging-port=9222')
        options.add_argument('user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36')
        return options

    def parse(self, response):
        detail_links = []
        options = self.create_options()
        for page_num in range(0, 3425):
            u = f"https://www.federalpay.org/paycheck-protection-program/pa/{page_num * 100}"
            print("* parsing ", page_num * 100)
            driver = webdriver.Chrome(chrome_options=options)
            driver.get(u)
            try:
                WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CSS_SELECTOR, 'table#ppp-loans')))
            except:
                driver.quit()
                time.sleep(1)
                break

            tags = driver.find_elements(By.XPATH, '//td[@class="title"]/a')
            for tag in tags:
                detail_links.append(tag.get_attribute('href'))
                # self.makeLog(tag.get_attribute('href'))
            driver.quit()
            time.sleep(1)
            if len(tags) != 100:
                break

        for u_idx, u in enumerate(detail_links):
            if u_idx == 50:
                break
            driver = webdriver.Chrome(chrome_options=options)
            driver.get(u)
            try:
                WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CSS_SELECTOR, 'h1.heading')))
            except:
                driver.quit()
                time.sleep(1)
                break
            print('*parsing:', u)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA PPP Recipients')
            l.add_value('company_url', u)
            l.add_value('business_name', driver.find_element(By.CSS_SELECTOR, 'div.employee-profile-header h2').text.strip())
            l.add_value('secondary_business_name', driver.find_element(By.CSS_SELECTOR, '[title="Business legal structure"]').text.strip())
            l.add_value('industry_type', driver.find_element(By.CSS_SELECTOR, 'div.employee-profile-header h3 a').text.strip())
            address = driver.find_element(By.CSS_SELECTOR, 'div.employee-profile-header address').text
            addresses = address.split('\n')
            l.add_value('street_address', addresses[-2])
            city_state_zip = addresses[-1]
            l.add_value('city', city_state_zip.split(',')[0].strip())
            l.add_value('state', city_state_zip.split(',')[1].strip().split(' ')[0].strip())
            l.add_value('postal_code', city_state_zip.split(',')[1].strip().split(' ')[1].strip())
            l.add_value('country', 'USA')
            naics = driver.find_element(By.CSS_SELECTOR, 'div.employee-profile-header p i').text
            if 'code' in naics:
                l.add_value('NAICS', naics.split('code')[-1].strip())
            yield l.load_item()
            driver.quit()
            time.sleep(1)

    # def makeLog(self, txt):
    #     fout = open("log.txt", "a")
    #     fout.write(txt + "\n")
    #     fout.close()