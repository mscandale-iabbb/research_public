from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NpiregistrySpider(scrapy.Spider):
    name = 'npiregistry'
    allowed_domains = ['npiregistry.cms.hhs.gov']
    start_urls = ['https://npiregistry.cms.hhs.gov/search']
    headers = {
        'authority': 'npiregistry.cms.hhs.gov',
        'accept': 'application/json, text/plain, */*',
        'content-type': 'application/json',
        'origin': 'https://npiregistry.cms.hhs.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }

    json_data = {
        'city': None,
        'state': None,
        'country': None,
        'lastName': None,
        'organizationName': None,
        'postalCode': None,
        'enumerationType': None,
        'taxonomyDescription': None,
        'addressType': None,
        'exactMatch': False,
    }
    api_url = 'https://npiregistry.cms.hhs.gov/RegistryBack/search'
    buf = []

    def parse(self, response):
        for str1 in string.ascii_lowercase:
            for str2 in string.ascii_lowercase:
                firstName = f"{str1}{str2}"
                self.json_data['skip'] = 0
                self.json_data['firstName'] = firstName
                yield scrapy.Request(
                    url=self.api_url,
                    method='POST',
                    body=json.dumps(self.json_data),
                    callback=self.get_data,
                    headers=self.headers,
                    meta={'skip': 0, 'firstName': firstName}
                )

    def get_data(self, response):
        json_data = json.loads(response.text)
        for p in json_data:
            if p["number"] in self.buf:
                continue
            self.buf.append(p["number"])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Healthcare Providers (All States)')
            l.add_value('company_url', f"https://npiregistry.cms.hhs.gov/provider-view/{p['number']}")
            l.add_value('prename', p['basic'].get('namePrefix', ''))
            l.add_value('postname', p['basic'].get('nameSuffix', ''))
            l.add_value('first_name', p['basic'].get('firstName', ''))
            l.add_value('last_name', p['basic'].get('lastName', ''))
            l.add_value('middle_name', p['basic'].get('middleName', ''))
            l.add_value('license_number', p['primaryTaxonomy'].get('license', ''))
            l.add_value('license_type', p['primaryTaxonomy'].get('desc', ''))
            l.add_value('street_address', p['primaryAddress'].get('addressLine1', ''))
            l.add_value('city', p['primaryAddress'].get('city', ''))
            l.add_value('state', p['primaryAddress'].get('state', ''))
            l.add_value('postal_code', p['primaryAddress'].get('postalCode', ''))
            l.add_value('country', 'USA')
            l.add_value('phone', p['primaryAddress'].get('teleNumber', ''))
            l.add_value('fax', p['primaryAddress'].get('faxNumber', ''))
            yield l.load_item()

        if len(json_data) == 101:
            skip = response.meta['skip']
            firstName = response.meta['firstName']
            self.json_data['skip'] = skip + 100
            self.json_data['firstName'] = firstName
            yield scrapy.Request(
                url=self.api_url,
                method='POST',
                body=json.dumps(self.json_data),
                callback=self.get_data,
                headers=self.headers,
                meta={'skip': skip + 100, 'firstName': firstName}
            )

