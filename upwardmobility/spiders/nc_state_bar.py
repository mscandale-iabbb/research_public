from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


class NcStateBarSpider(scrapy.Spider):
    name = 'nc_state_bar'
    allowed_domains = ['portal.ncbar.gov']
    start_urls = ['https://portal.ncbar.gov/verification/search.aspx']

    def parse(self, response):
        options = Options()
        options.add_argument('--headless') # No working at headless method
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--remote-debugging-port=9222')
        options.add_argument('user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36')
        profiles = []
        for str1 in string.ascii_lowercase:
            for str2 in string.ascii_lowercase:
                driver = webdriver.Chrome(chrome_options=options)
                driver.get("https://portal.ncbar.gov/Verification/search.aspx")
                try:
                    WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.CSS_SELECTOR, 'input#txtLast')))
                except:
                    driver.quit()
                    time.sleep(1)
                letter = f"{str1}{str2}"
                driver.find_element(By.CSS_SELECTOR, 'input#txtLast').send_keys(letter)
                driver.find_element(By.CSS_SELECTOR, 'input#btnSubmit').click()
                time.sleep(1)
                WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.CSS_SELECTOR, 'table.table')))
                tags = driver.find_elements(By.XPATH, '//table[contains(@class, "table")]/tbody/tr')
                for tag in tags:
                    url = tag.find_element(By.CSS_SELECTOR, 'a').get_attribute('href')
                    if url in profiles:
                        continue
                    profiles.append(url)
                # driver.execute_script("""document.querySelector('div.ButtonGroup a[href*="/Verification/search.aspx"]').click()""")
                # WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.CSS_SELECTOR, 'input#txtLast')))
                # driver.find_element(By.CSS_SELECTOR, 'input#txtLast').clear()
                time.sleep(1)
                for p_u in profiles:
                    driver.get(p_u)
                    try:
                        WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.CSS_SELECTOR, '#pnlVerificationBody')))
                    except:
                        time.sleep(1)
                        continue
                    l = CompanyLoader(item=UpwardMobilityItem(), response=response)
                    tree = fromstring(driver.page_source)
                    l.add_value('source', 'UT Bar Association')
                    l.add_value('company_url', response.url)
                    l.add_value('license_number', ''.join(tree.xpath('//dt[contains(., "Bar #:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('street_address', ''.join(tree.xpath('//dt[contains(., "Address:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('city', ''.join(tree.xpath('//dt[contains(., "City:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('state', ''.join(tree.xpath('//dt[contains(., "State:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('postal_code', ''.join(tree.xpath('//dt[contains(., "Zip Code:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('phone', ''.join(tree.xpath('//dt[contains(., "Phone:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('email', ''.join(tree.xpath('//dt[contains(., "Email:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('license_status', ''.join(tree.xpath('//dt[contains(., "Status:")]/following-sibling::dd[1]/span[contains(@class, "label")]/text()')).strip())
                    l.add_value('license_issue_date', ''.join(tree.xpath('//dt[contains(., "Admitted:")]/following-sibling::dd[1]/text()')).strip())
                    l.add_value('license_expiration_date', ''.join(tree.xpath('//dt[contains(., "Date:")]/following-sibling::dd[1]/text()')).strip())
                    full_name = ''.join(tree.xpath('//dt[contains(., "Name:")]/following-sibling::dd[1]/text()')).strip()
                    if full_name:
                        prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                        l.add_value('prename', prename)
                        l.add_value('postname', postname)
                        l.add_value('first_name', first_name)
                        l.add_value('last_name', last_name)
                        l.add_value('middle_name', middle_name)
                    yield l.load_item()
                driver.quit()
                time.sleep(10)
