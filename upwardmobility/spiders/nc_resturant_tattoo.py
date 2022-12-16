from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys


class NcResturantTattooSpider(scrapy.Spider):
    name = 'nc_resturant_tattoo'
    allowed_domains = ['public.cdpehs.com']
    start_urls = ['https://public.cdpehs.com/NCENVPBL/ESTABLISHMENT/ShowESTABLISHMENTTablePage.aspx?ESTTST_CTY=60']

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
            WebDriverWait(driver, 30).until(EC.visibility_of_element_located((By.CSS_SELECTOR, 'input[name*="_PageSize"]')))
        except:
            driver.quit()
            time.sleep(1)
        input_tag = driver.find_element(By.CSS_SELECTOR, 'input[name*="_PageSize"]')
        input_tag.send_keys('10000')
        input_tag.send_keys(Keys.ENTER)
        time.sleep(20)
        tree = get_etree(driver.page_source, response.url)
        for tag in tree.xpath('//table[@class="dBody"]/tbody//td[@class="tre"]/table/tbody/tr[not(@class)]'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Resturant & Tattoo')
            l.add_value('business_name', tag.xpath('./td[2]/text()')[0])
            l.add_value('street_address', tag.xpath('./td[3]/text()')[0])
            l.add_value('city', tag.xpath('./td[4]/text()')[0])
            l.add_value('state', tag.xpath('./td[5]/text()')[0])
            l.add_value('postal_code', tag.xpath('./td[6]/text()')[0])
            l.add_value('industry_type', tag.xpath('./td[7]/text()')[0])
            yield l.load_item()

        time.sleep(1)
        driver.quit()