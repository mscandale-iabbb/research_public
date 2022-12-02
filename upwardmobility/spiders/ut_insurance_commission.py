from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


class UtInsuranceCommissionSpider(scrapy.Spider):
    name = 'ut_insurance_commission'
    allowed_domains = ['secure.utah.gov']
    start_urls = ['https://secure.utah.gov/agent-search/search.html#']
    profiles = []

    def parse(self, response):
        options = Options()
        # options.add_argument('--headless') # No working at headless method
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--remote-debugging-port=9222')
        options.add_argument('user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36')

        driver = webdriver.Chrome(chrome_options=options)
        driver.get(response.url)
        
        try:
            WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CSS_SELECTOR, 'a#searchNameTab')))
        except:
            driver.quit()
            time.sleep(1)
        driver.find_element(By.CSS_SELECTOR, 'a#searchNameTab').click()
        time.sleep(1)
        for letter in string.ascii_lowercase:
            driver.find_element(By.CSS_SELECTOR, 'input#firstName').send_keys(letter)
            driver.find_element(By.CSS_SELECTOR, 'form#searchName button[type="submit"]').click()
            time.sleep(1)
            WebDriverWait(driver, 50).until(EC.invisibility_of_element_located((By.CSS_SELECTOR, 'div.loading')))
            tags = driver.find_elements(By.XPATH, '//div[@id="results"]/table/tbody/tr')
            for tag in tags:
                url = tag.find_element(By.CSS_SELECTOR, 'a').get_attribute('href')
                if url in self.profiles:
                    continue
                self.profiles.append(url)
            driver.find_element(By.CSS_SELECTOR, 'input#firstName').clear()
            time.sleep(1)
            
        driver.quit()

        for u in self.profiles:
            yield scrapy.Request(u, callback=self.parse_profile)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'UT Insurance Commission')
        full_name = ''.join(response.xpath('//dd[@class="nameDef"]/text()').extract()).strip()
        prename, postname, first_name, last_name, middle_name = parse_name(full_name)
        l.add_value('prename', prename)
        l.add_value('postname', postname)
        l.add_value('first_name', first_name)
        l.add_value('last_name', last_name)
        l.add_value('middle_name', middle_name)
        addresses = response.xpath('//dt[contains(., "Address")]/following-sibling::dd[1]/text()').extract_first()
        address_city = replace_nbsp(' '.join(addresses.split(',')[:-1]).strip())
        state_zip = addresses.split(',')[-1].strip()
        l.add_value('street_address', ' '.join(address_city.split(' ')[:-1]).strip())
        l.add_value('city', address_city.split(' ')[-1].strip())
        l.add_value('state', state_zip.split(' ')[0].strip())
        l.add_value('postal_code', state_zip.split(' ')[1].strip())
        l.add_value('country', 'USA')
        l.add_xpath('phone', '//dt[contains(., "Phone")]/following-sibling::dd[1]//text()')
        l.add_xpath('website', '//dt[contains(., "Internet")]/following-sibling::dd[1]//text()')
        l.add_xpath('email', '//dt[contains(., "Email")]/following-sibling::dd[1]//text()')
        license_tag = response.xpath('//table[.//th[contains(., "Expires")]]//tbody/tr[1]')
        l.add_value('license_number', license_tag.xpath('./td[4]/text()').extract_first())
        l.add_value('license_expiration_date', license_tag.xpath('./td[3]/text()').extract_first())
        l.add_value('license_status', license_tag.xpath('./td[2]/text()').extract_first())
        l.add_value('license_type', license_tag.xpath('./td[1]/text()').extract_first())
        return l.load_item()


