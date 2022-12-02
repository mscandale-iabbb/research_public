from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait # available since 2.4.0
from selenium.webdriver.support import expected_conditions as EC # available since 2.26.0
from selenium.webdriver.common.by import By
import time, os
from lxml import html


class PaNurseryDealersSpider(scrapy.Spider):
    handle_httpstatus_list = [500]
    name = 'pa_nursery_dealers'
    allowed_domains = ['www.paplants.pa.gov']
    start_urls = ['https://www.paplants.pa.gov/Licenses/PlantMerchantSearch.aspx']
    download_path = ''

    def parse(self, response):
        self.download_path = os.getcwd()
        options = Options()
        prefs = {'download.default_directory' : self.download_path}
        options.add_experimental_option('prefs', prefs)
        options.add_argument('--headless')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--remote-debugging-port=9222')
        options.add_argument('user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36')

        driver = webdriver.Chrome(chrome_options=options)
        driver.get('https://www.paplants.pa.gov/Licenses/PlantMerchantSearch.aspx')
        
        try:
            WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CSS_SELECTOR, 'div#ctl00_pnlMain')))
        except:
            driver.quit()
            time.sleep(1)
        driver.execute_script("""document.querySelector('input[id="ctl00_cphContentMain_ExportToExcel"]').click()""")
        self.download_wait(self.download_path)

        time.sleep(10)
        driver.quit()
        
        filename='export.xls'
        f = open(filename, 'r')
        file_data = f.read()
        tree = html.fromstring(file_data)
        for tag_idx, tag in enumerate(tree.xpath('//table/tr')):
            if tag_idx == 0:
                continue
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA Nursery (Plant) Dealers')
            l.add_value('business_name', tag.xpath('./td[2]/text()')[0].strip())
            l.add_value('street_address', tag.xpath('./td[6]/text()')[0].strip())
            l.add_value('city', tag.xpath('./td[8]/text()')[0].strip())
            l.add_value('state', tag.xpath('./td[9]/text()')[0].strip())
            l.add_value('postal_code', tag.xpath('./td[10]/text()')[0].strip())
            l.add_value('phone', tag.xpath('./td[11]/text()')[0].strip())
            l.add_value('industry_type', tag.xpath('./td[19]/text()')[0].strip())
            l.add_value('license_number', tag.xpath('./td[1]/text()')[0].strip())
            l.add_value('license_status', tag.xpath('./td[15]/text()')[0].strip())
            l.add_value('license_type', tag.xpath('./td[18]/text()')[0].strip())
            yield l.load_item()


    def download_wait(self, path_to_downloads):
        seconds = 0
        dl_wait = True
        while dl_wait and seconds < 20:
            time.sleep(1)
            dl_wait = False
            for fname in os.listdir(path_to_downloads):
                if fname.endswith('.xls'):
                    dl_wait = True
            seconds += 1
        return seconds