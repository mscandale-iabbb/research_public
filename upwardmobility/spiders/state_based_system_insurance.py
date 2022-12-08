from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class StateBasedSystemInsuranceSpider(scrapy.Spider):
    name = 'state_based_system_insurance'
    allowed_domains = ['sbs.naic.org', 'services.naic.org']
    start_urls = ['https://services.naic.org/api/lookup']
    buf = []

    def parse(self, response):
        for v in json.loads(response.text):
            for last_name in string.ascii_lowercase:
                u = f"https://services.naic.org/api/licenseLookup/search?jurisdiction={v['code']}&searchType=Licensee&entityType=IND&lastName={last_name}"
                yield scrapy.Request(u, callback=self.parse_profile)

    def parse_profile(self, response):
        json_data = json.loads(response.text)
        for p in json_data:
            licenseNumber = p['licenseNumber']
            if licenseNumber in self.buf:
                continue
            self.buf.append(licenseNumber)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'State Based System for Insurance ')
            l.add_value('license_number', str(licenseNumber))
            l.add_value('license_issue_date', p['licenseEffectiveDate'])
            l.add_value('license_expiration_date', p['licenseExpirationDate'])
            l.add_value('license_type', p['licenseType'])
            l.add_value('phone', p['businessPhone'])
            full_name = p['name']
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            addresses = p['businessAddress']
            l.add_value('city', ', '.join(addresses.split(',')[:-1]))
            l.add_value('state', addresses.split(',')[-1].strip().split(' ')[0])
            l.add_value('postal_code', addresses.split(',')[-1].strip().split(' ')[1])
            l.add_value('country', 'USA')
            yield l.load_item()

