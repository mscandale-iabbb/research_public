from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class FinraSpider(scrapy.Spider):
    name = 'finra'
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['brokercheck.finra.org']
    start_urls = ['https://brokercheck.finra.org/']
    buf = []
    headers = {
        'authority': 'api.brokercheck.finra.org',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    def parse(self, response):
        for searchText in string.ascii_lowercase:
            u = f"https://api.brokercheck.finra.org/search/individual?query={searchText}&filter=active=true,prev=true,bar=true,broker=true,ia=true,brokeria=true&includePrevious=true&hl=true&nrows=12&start=0&r=25&sort=score+desc&wt=json"
            yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'searchText': searchText, 'start': 0})

    def get_data(self, response):
        json_data = json.loads(response.text)
        if not json_data['hits']:
            return

        data = json_data['hits']['hits']
        for d in data:
            ind_source_id = d['_source']['ind_source_id']
            if ind_source_id in self.buf:
                continue
            self.buf.append(ind_source_id)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Finra')
            l.add_value('first_name', d['_source']['ind_firstname'])
            l.add_value('last_name', d['_source']['ind_lastname'])
            l.add_value('middle_name', d['_source'].get('ind_middlename', None))
            l.add_value('license_status', d['_source']['ind_bc_scope'])
            for b in d['_source']['ind_current_employments']:
                l.add_value('business_name', b.get('firm_name', None))
                l.add_value('city', b.get('branch_city', None))
                l.add_value('state', b.get('branch_state', None))
                l.add_value('postal_code', b.get('branch_zip', None))
                break
            l.add_value('date_business_started', d['_source'].get('ind_industry_cal_date', None))
            yield l.load_item()

        if len(data) == 12:
            searchText = response.meta['searchText']
            start = response.meta['start']
            u = f"https://api.brokercheck.finra.org/search/individual?query={searchText}&filter=active=true,prev=true,bar=true,broker=true,ia=true,brokeria=true&includePrevious=true&hl=true&nrows=12&start={start+12}&r=25&sort=score+desc&wt=json"
            yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'searchText': searchText, 'start': start+12})

