from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcBoardMassageBodyworkTherapySpider(scrapy.Spider):
    name = 'nc_board_massage_bodywork_therapy'
    allowed_domains = ['theconjuredsolution.com']
    start_urls = ['http://www.theconjuredsolution.com/aspsearch/ncmassx.asp?Page=1']

    def parse(self, response):
        for tag in response.xpath('//table[@id="masterDataTable"]/tr[@class]'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board of Massage & Bodywork Therapy')
            l.add_value('last_name', tag.xpath('.//span[contains(@id, "Last_Name")]/text()').extract_first())
            l.add_value('first_name', tag.xpath('.//span[contains(@id, "First_Name")]/text()').extract_first())
            l.add_value('city', tag.xpath('.//span[contains(@id, "City")]/text()').extract_first())
            l.add_value('state', tag.xpath('.//span[contains(@id, "State")]/text()').extract_first())
            l.add_value('license_number', tag.xpath('.//span[contains(@id, "License_value")]/text()').extract_first())
            l.add_value('license_expiration_date', tag.xpath('.//span[contains(@id, "Expiration_Date")]/text()').extract_first())
            yield l.load_item()

        next_href = response.xpath('//a[contains(., "Next")]/@href').extract_first()
        if next_href:
            yield scrapy.Request(response.urljoin(next_href), callback=self.parse)
