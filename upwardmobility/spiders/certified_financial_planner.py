from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class CertifiedFinancialPlannerSpider(scrapy.Spider):
    name = 'certified_financial_planner'
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['letsmakeaplan.org']
    start_urls = ['https://www.letsmakeaplan.org/']
    headers = {
        'authority': 'www.letsmakeaplan.org',
        'accept': '*/*',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        pg = 1
        for searchText in string.ascii_lowercase:
            u = f"https://www.letsmakeaplan.org/api/feature/lmapprofilesearch/search?limit=10&pg={pg}&last_name={searchText}&randomKey=561&sort=random&distance=25"
            yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'searchText': searchText, 'pg':pg})

    def get_data(self, response):
        json_data = json.loads(response.text)
        for p in json_data['results']:
            if p['id'] in self.buf:
                continue
            self.buf.append(p['id'])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Certified Financial Planner')
            l.add_value('first_name', p['ind_first_name'])
            l.add_value('last_name', p['ind_last_name'])
            l.add_value('middle_name', p['ind_mid_name'])
            l.add_value('prename', p['ind_prf_code'])
            l.add_value('postname', p['ind_sfx_code'])
            l.add_value('website', p['website'])
            l.add_value('business_name', p['cst_org_name_dn'])
            l.add_value('phone', ', '.join(p['phones']) if p['phones'] else None)
            if p['_childDocuments_']:
                l.add_value('street_address', p['_childDocuments_'][0]['adr_line1'])
                l.add_value('city', p['_childDocuments_'][0]['adr_city'])
                l.add_value('postal_code', p['_childDocuments_'][0]['adr_post_code'])
                l.add_value('state', p['_childDocuments_'][0]['adr_state'])
            yield l.load_item()

        if len(json_data['results']) == 10:
            pg = response.meta['pg']
            searchText = response.meta['searchText']
            u = f"https://www.letsmakeaplan.org/api/feature/lmapprofilesearch/search?limit=10&pg={pg+1}&last_name={searchText}&randomKey=561&sort=random&distance=25"
            yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'searchText': searchText, 'pg':pg+1})
