from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PaAsbestosAbatementSpider(scrapy.Spider):
    name = 'pa_asbestos_abatement'
    allowed_domains = ['dli.pa.gov']
    start_urls = ['https://www.dli.pa.gov/Individuals/Labor-Management-Relations/bois/Documents/ASBCONTR.HTM']

    def parse(self, response):
        for tag in response.xpath('//table/tbody/tr'):
            check_text = tag.xpath('./td[1]/text()').extract_first()
            if 'Revised Date:' in check_text:
                continue
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA Asbestos Abatement')
            l.add_value('company_url', response.url)
            l.add_value('license_number', tag.xpath('./td[1]/text()').extract_first())
            l.add_value('business_name', tag.xpath('./td[2]/text()').extract_first())
            l.add_value('phone', tag.xpath('./td[3]/text()').extract_first())
            l.add_value('street_address', tag.xpath('./td[4]/text()').extract_first())
            l.add_value('city', tag.xpath('./td[5]/text()').extract_first())
            l.add_value('state', tag.xpath('./td[6]/text()').extract_first())
            l.add_value('postal_code', tag.xpath('./td[7]/text()').extract_first())
            l.add_value('country', 'USA')
            l.add_value('license_expiration_date', tag.xpath('./td[8]/text()').extract_first())
            yield l.load_item()