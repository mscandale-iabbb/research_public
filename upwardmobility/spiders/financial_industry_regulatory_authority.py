from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class FinancialIndustryRegulatoryAuthoritySpider(scrapy.Spider):
    name = 'financial_industry_regulatory_authority'
    custom_settings={'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['brokercheck.finra.org']
    start_urls = ['http://brokercheck.finra.org/']
    headers = {
        'authority': 'api.brokercheck.finra.org',
        'accept': 'application/json, text/plain, */*',
        'origin': 'https://brokercheck.finra.org',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for query in string.ascii_lowercase:
            u = f'https://api.brokercheck.finra.org/search/firm?query={query}&hl=true&nrows=12&start=0&r=25&sort=score+desc&wt=json'
            yield scrapy.Request(
                u,
                callback=self.parse_profiles,
                headers=self.headers,
                meta={
                    'query': query,
                    'start': 0
                }
            )

    def parse_profiles(self, response):
        json_data = json.loads(response.text)
        try:
            hits = json_data['hits']['hits']
        except:
            return
        for h in hits:
            firm_source_id = h['_source']['firm_source_id']
            if firm_source_id in self.buf:
                continue
            self.buf.append(firm_source_id)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Financial Industry Regulatory Authority')
            l.add_value('business_name', h['_source'].get('firm_name', None))
            l.add_value('license_status', h['_source'].get('firm_ia_scope', None))
            l.add_value('license_number', h['_source'].get('firm_ia_full_sec_number', None))
            address_data = h['_source'].get('firm_ia_address_details', None)
            if address_data:
                add = json.loads(address_data)
                l.add_value('street_address', add['officeAddress'].get('street1', None))
                l.add_value('city', add['officeAddress'].get('city', None))
                l.add_value('state', add['officeAddress'].get('state', None))
                l.add_value('country', add['officeAddress'].get('country', None))
                l.add_value('postal_code', add['officeAddress'].get('postalCode', None))
            yield l.load_item()

        if len(hits) == 12:
            query = response.meta['query']
            start = response.meta['start']
            u = f'https://api.brokercheck.finra.org/search/firm?query={query}&hl=true&nrows=12&start={start+12}&r=25&sort=score+desc&wt=json'
            yield scrapy.Request(
                u,
                callback=self.parse_profiles,
                headers=self.headers,
                meta={
                    'query': query,
                    'start': start + 12
                }
            )