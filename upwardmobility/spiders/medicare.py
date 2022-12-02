from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class MedicareSpider(scrapy.Spider):
    name = 'medicare'
    allowed_domains = ['medicare.gov']
    start_urls = ['https://www.geonames.org/postal-codes/US/PA/pennsylvania.html']
    headers = {
        'authority': 'www.medicare.gov',
        'accept': 'application/json, text/plain, */*',
        'X-Requested-With': 'XMLHttpRequest',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    profiles = []

    def parse(self, response):
        headers = {
            'authority': 'www.medicare.gov',
            'accept': 'application/json, text/plain, */*',
            'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        }
        for tag in response.xpath('//table[@class="restable"]/tr'):
            zipcode = tag.xpath('./td[3]/text()').extract_first()
            if zipcode:
                u = f'https://www.medicare.gov/medical-equipment-suppliers/results?location={zipcode}'
                yield scrapy.Request(u, callback=self.get_session, headers=headers, meta={'zipcode': zipcode})

    def get_session(self, response):
        zipcode = response.meta['zipcode']
        u = f'https://www.medicare.gov/api/procedure-price-lookup/dme/api/v1/suppliers/search/location?radius=10&cba-filter=true&&zipcode={zipcode}'
        yield scrapy.Request(u, callback=self.get_profiles, headers=self.headers, meta={'zipcode': zipcode})

    def get_profiles(self, response):
        json_data = json.loads(response.text)
        for p in json_data['supplierList']:
            if p['supplier']['supplierID'] in self.profiles:
                continue
            self.profiles.append(p['supplier']['supplierID'])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Medical Equipment Suppliers (Medicare Approvded)')
            l.add_value('business_name', p['supplier']['businessName'])
            l.add_value('street_address', p['supplier']['address1'])
            l.add_value('city', p['supplier']['city'])
            l.add_value('state', p['supplier']['state'])
            l.add_value('postal_code', p['supplier']['zip'])
            l.add_value('country', 'USA')
            l.add_value('phone', p['supplier']['phone'])
            l.add_value('secondary_business_name', p['supplier']['practiceName'])
            l.add_value('license_issue_date', p['supplier']['participationBeginDate'])
            yield l.load_item()