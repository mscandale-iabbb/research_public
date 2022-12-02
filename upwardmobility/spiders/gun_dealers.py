from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class GunDealersSpider(scrapy.Spider):
    name = 'gun_dealers'
    allowed_domains = ['atf.gov']
    start_urls = ['https://www.atf.gov/firearms/listing-federal-firearms-licensees']

    def parse(self, response):
        for state in response.xpath('//select[@name="field_state_value"]/option/@value').extract():
            search_url = f"https://www.atf.gov/firearms/listing-federal-firearms-licensees/state?field_ffl_date_value%5Bvalue%5D%5Byear%5D=&ffl_date_month%5Bvalue%5D%5Bmonth%5D=&field_state_value={state}"
            yield scrapy.Request(search_url, callback=self.get_dealers)

    def get_dealers(self, response):
        for href in response.xpath('//td[@class="views-field views-field-field-ffl-txt-listings"]//a/@href').extract():
            yield scrapy.Request(response.urljoin(href), callback=self.parse_xlsx)

    def parse_xlsx(self, response):
        for line_idx, line in enumerate(response.text.splitlines()):
            if line_idx == 0:
                continue
            values = line.split('\t')
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Gun Dealers (All States)')
            l.add_value('license_type', values[3])
            l.add_value('license_number', values[5])
            l.add_value('secondary_business_name', values[7])
            l.add_value('business_name', values[6])
            l.add_value('street_address', values[8])
            l.add_value('city', values[9])
            l.add_value('state', values[10])
            l.add_value('postal_code', values[11])
            l.add_value('country', 'USA')
            l.add_value('phone', values[16])
            yield l.load_item()
