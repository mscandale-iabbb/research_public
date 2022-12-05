from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NevadaTransportationAuthoritySpider(scrapy.Spider):
    name = 'nevada_transportation_authority'
    allowed_domains = ['tsa1.nv.gov']
    start_urls = ['http://tsa1.nv.gov/ActiveCertificatesTable.asp?nNo=5']

    def parse(self, response):
        for tag in response.xpath('//table/tbody/tr'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Nevada Transportation Authority')
            l.add_value('license_number', ''.join(tag.xpath('./td[1]//text()').extract()).strip())
            l.add_value('license_status', ''.join(tag.xpath('./td[2]//text()').extract()).strip())
            l.add_value('business_name', ''.join(tag.xpath('./td[3]//text()').extract()).strip())
            l.add_value('secondary_business_name', ''.join(tag.xpath('./td[4]//text()').extract()).strip())
            l.add_value('phone', ''.join(tag.xpath('./td[5]//text()').extract()).strip())
            l.add_value('fax', ''.join(tag.xpath('./td[6]/font[@face="Arial"]/text()').extract()).strip())
            yield l.load_item()
