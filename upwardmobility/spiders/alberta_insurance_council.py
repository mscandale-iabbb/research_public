from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class AlbertaInsuranceCouncilSpider(scrapy.Spider):
    name = 'alberta_insurance_council'
    allowed_domains = ['licensing.abcouncil.ab.ca']
    start_urls = ['https://licensing.abcouncil.ab.ca/#!/aglookup']
    buf = []

    def parse(self, response):
        for lastName in string.ascii_lowercase:
            u = f"https://licensing.abcouncil.ab.ca/lookup/license/search/?lastName={lastName}&lookupType=agent&showHistory=false"
            yield scrapy.Request(u, callback=self.get_data)

    def get_data(self, response):
        json_data = json.loads(response.text)
        for p in json_data:
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Alberta Insurance Council')
            license = p['license']
            if license in self.buf:
                continue
            self.buf.append(license)
            l.add_value('business_name', p['agencyname'])
            l.add_value('license_number', license)
            l.add_value('license_expiration_date', p['expirydate'])
            l.add_value('license_issue_date', p['validdate'])
            l.add_value('license_type', p['licensetype'])
            l.add_value('license_status', p['status'])
            l.add_value('street_address', p['street'])
            l.add_value('city', p['city'])
            l.add_value('state', p['province'])
            l.add_value('postal_code', p['postalcode'])
            full_name = p['licenseholdername']
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name.split(")")[-1].strip())
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            yield l.load_item()
