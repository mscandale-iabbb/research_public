from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *

class IRSAllTaxReturnProfessionalsSpider(scrapy.Spider):
    name = 'irs_all_tax_return_professionals'
    allowed_domains = ['irs.gov']
    start_urls = ['https://www.irs.gov/tax-professionals/ptin-information-and-the-freedom-of-information-act']
    headers = ("LAST_NAME","First_NAME","MIDDLE_NAME","SUFFIX","DBA","BUS_ADDR_LINE1","BUS_ADDR_LINE2","BUS_ADDR_LINE3","BUS_ADDR_CITY","BUS_ST_CODE","BUS_ADDR_ZIP","BUS_CNTRY_CDE","WEBSITE","BUS_PHNE_NBR","PROFESSION","AFSP_Indicator")

    def parse(self, response):
        headers = {
            'authority': 'www.irs.gov',
            'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'accept-language': 'en-US,en;q=0.9',
            'referer': 'https://www.irs.gov/tax-professionals/ptin-information-and-the-freedom-of-information-act',
            'sec-ch-ua': '"Google Chrome";v="107", "Chromium";v="107", "Not=A?Brand";v="24"',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Linux"',
            'sec-fetch-dest': 'document',
            'sec-fetch-mode': 'navigate',
            'sec-fetch-site': 'same-origin',
            'sec-fetch-user': '?1',
            'upgrade-insecure-requests': '1',
            'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        }
        for url in response.xpath('//p[./a[@data-entity-substitution="pup_linkit_media"]]/following-sibling::div[1]//table//p/a/@href').extract():
            yield scrapy.Request(url, callback=self.parse_csv, headers=headers)
            # break

    def parse_csv(self, response):
        f = io.StringIO()

        for line_idx, line in enumerate(response.text.splitlines()):
            if line_idx == 0:
                continue
            # Write one line to the in-memory file.
            f.write(line)
            # Seek sends the file handle to the top of the file.
            f.seek(0)
            
            reader = csv.DictReader(f, fieldnames=self.headers)
            row = next(reader)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'IRS All Tax Return Professionals')
            l.add_value('company_url', response.url)
            l.add_value('business_name', row['DBA'])
            l.add_value('postname', row['SUFFIX'])
            l.add_value('first_name', row['First_NAME'])
            l.add_value('last_name', row['LAST_NAME'])
            l.add_value('middle_name', row['MIDDLE_NAME'])
            l.add_value('street_address', row['BUS_ADDR_LINE1'])
            l.add_value('city', row['BUS_ADDR_CITY'])
            l.add_value('state', row['BUS_ST_CODE'])
            l.add_value('postal_code', row['BUS_ADDR_ZIP'])
            l.add_value('country', 'USA')
            l.add_value('phone', row['BUS_PHNE_NBR'])
            l.add_value('website', row['WEBSITE'])
            l.add_value('title', row['PROFESSION'])
            if row['AFSP_Indicator'] == 'Y':
                l.add_value('license_type', 'AFSP')
            else:
                l.add_value('license_type', 'No AFSP')
            yield l.load_item()
            f.seek(0)

            # Clean up the buffer.
            f.flush()
            # break
