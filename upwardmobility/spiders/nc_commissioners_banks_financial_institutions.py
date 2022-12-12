from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


class NcCommissionersBanksFinancialInstitutionsSpider(scrapy.Spider):
    name = 'nc_commissioners_banks_financial_institutions'
    allowed_domains = ['nccob.org']
    start_urls = ['https://www.nccob.org/Online/NMLS/LicenseSearch.aspx']

    def parse(self, response):
        options = Options()
        options.add_argument('--headless') # No working at headless method
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--remote-debugging-port=9222')
        options.add_argument('user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36')

        driver = webdriver.Chrome(chrome_options=options)
        driver.get(response.url)
        try:
            WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CSS_SELECTOR, 'input#btnAllOfficers')))
        except:
            driver.quit()
            time.sleep(1)
        driver.find_element(By.CSS_SELECTOR, 'input#btnAllOfficers').click()
        time.sleep(5)
        while(1):
            time.sleep(5)
            tree = get_etree(driver.page_source, response.url)
            for tag in tree.xpath('//table[@id="dgOfficer"]/tbody/tr[not(@class="header")]'):
                l = CompanyLoader(item=UpwardMobilityItem(), response=response)
                l.add_value('source', 'NC Commissioners Of Banks Financial Institutions')
                full_name = tag.xpath('./td[1]//text()')
                if full_name:
                    prename, postname, first_name, last_name, middle_name = parse_name(full_name[0].strip())
                    l.add_value('prename', prename)
                    l.add_value('postname', postname)
                    l.add_value('first_name', first_name)
                    l.add_value('last_name', last_name)
                    l.add_value('middle_name', middle_name)
                
                business_name = tag.xpath('./td[2]/text()')
                if business_name:
                    l.add_value('business_name', business_name[0])
                license_number = tag.xpath('./td[3]/text()')
                if license_number:
                    l.add_value('license_number', license_number[0])
                license_status = tag.xpath('./td[5]/text()')
                if license_status:
                    l.add_value('license_status', license_status[0])
                license_expiration_date = tag.xpath('./td[6]/text()')
                if license_expiration_date:
                    l.add_value('license_expiration_date', license_expiration_date[0])
                yield l.load_item()
            next_tag = tree.xpath('//tr[@class="header"]/td/span/following-sibling::a[1]')
            if next_tag:
                driver.find_element(By.XPATH, '//tr[@class="header"]/td/span/following-sibling::a[1]').click()
            else:
                break
        driver.quit()
