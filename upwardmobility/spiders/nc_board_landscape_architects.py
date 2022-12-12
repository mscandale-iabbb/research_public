from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


class NcBoardLandscapeArchitectsSpider(scrapy.Spider):
    name = 'nc_board_landscape_architects'
    allowed_domains = ['ncbola.org', 'c1dcd177.caspio.com']
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    start_urls = ['https://www.ncbola.org/resources/licensee-lookup?-session=LASession:D836AD4C04f581DE40nGw2D74BA4']
    headers = {
        'authority': 'c1dcd177.caspio.com',
        'accept': '*/*',
        'content-type': 'multipart/form-data; boundary=----WebKitFormBoundary4qWmdc6AlZYg12yc',
        'origin': 'https://www.ncbola.org',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
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
            WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CSS_SELECTOR, 'table.cbResultSetTable')))
        except:
            driver.quit()
            time.sleep(1)
        driver.execute_script("document.querySelector('table.cbResultSetTable tbody tr:first-child a.cbResultSetActionsLinks:first-child').click()")
        WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, '[data-cb-name="cbTable"]')))
        time.sleep(5)
        while(1):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board of Landscape Architects')
            full_name = driver.find_element(By.CSS_SELECTOR, '[data-cb-name="cbTable"] h3').text
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            info_text = driver.find_element(By.CSS_SELECTOR, '[data-cb-name="cbTable"] h4').text
            texts = []
            if info_text:
                texts = info_text.split('\n')
            for text in texts:
                if '@' in text and '.com' in text:
                    l.add_value('email', text)
            if len(texts) == 3:
                l.add_value('city', ', '.join(texts[1].split(',')[:-1]).strip())
                l.add_value('state', texts[1].split(',')[-1])
                l.add_value('business_name', texts[0])
            elif len(texts) == 2:
                l.add_value('city', ', '.join(texts[0].split(',')[:-1]).strip())
                l.add_value('state', texts[0].split(',')[-1])
            l.add_value('country', 'USA')

            license_number = driver.find_element(By.XPATH, '//div[contains(@data-cb-cell-name, "EditRecordLicense_Number")]/following-sibling::div[1]').text
            l.add_value('license_number', license_number)
            license_status = driver.find_element(By.XPATH, '//div[contains(@data-cb-cell-name, "EditRecordLicense_Status")]/following-sibling::div[1]').text
            l.add_value('license_status', license_status)
            license_issue_date = driver.find_element(By.XPATH, '//div[contains(@data-cb-cell-name, "EditRecordOriginal_Issue_Date")]/following-sibling::div[1]').text
            l.add_value('license_issue_date', license_issue_date)
            license_expiration_date = driver.find_element(By.XPATH, '//div[contains(@data-cb-cell-name, "EditRecordExpiration_Date")]/following-sibling::div[1]').text
            l.add_value('license_expiration_date', license_expiration_date)
            yield l.load_item()
            try:
                driver.execute_script("""document.querySelector('[data-cb-name="ResponsiveNavBar"] [data-cb-name="JumpToNext"]').click()""")
            except:
                break
            time.sleep(2)
        driver.quit()

        
