from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class UtDfiSpider(scrapy.Spider):
    name = 'ut_dfi'
    allowed_domains = ['utah.gov']
    start_urls = ['https://www.utah.gov/dfi/XML/Financial-Institutions.xml']

    def parse(self, response):
        for tag in response.xpath('//Financial-Institutions'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'UT DFI Department of Financial Institutions')
            l.add_value('business_name', tag.xpath('./Name/text()').extract_first())
            l.add_value('industry_type', tag.xpath('./Type/text()').extract_first())
            l.add_value('street_address', tag.xpath('./Address/text()').extract_first())
            l.add_value('city', tag.xpath('./City/text()').extract_first())
            l.add_value('state', tag.xpath('./State/text()').extract_first())
            l.add_value('postal_code', tag.xpath('./Zip/text()').extract_first())
            l.add_value('country', 'USA')
            l.add_value('phone', tag.xpath('./Phone/text()').extract_first())
            l.add_value('title', tag.xpath('./Title/text()').extract_first())
            full_name = tag.xpath('./Contact/text()').extract_first()
            prename, postname, first_name, last_name, middle_name = parse_name(full_name)
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
            yield l.load_item()
            
