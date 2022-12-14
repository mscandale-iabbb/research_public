from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcRegisteredTaxRefundAnticipationLoanFacilitatorsSpider(scrapy.Spider):
    name = 'nc_registered_tax_refund_anticipation_loan_facilitators'
    allowed_domains = ['nccob.org']
    start_urls = ['https://www.nccob.org/online/RALS/RALSCompanyListing.aspx']

    def parse(self, response):
        for tag in response.xpath('//span[@id="CompanyInfo"]//tr[@class="header"]'):
            business_name = tag.xpath('./td/strong/text()').extract()
            addresses = tag.xpath('./following-sibling::tr[1]/td[2]/text()').extract_first()
            city = tag.xpath('./following-sibling::tr[1]/td[1]/text()').extract_first()
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Department of Public Safety')
            if len(business_name) == 2:
                l.add_value('business_name', business_name[0])
                l.add_value('secondary_business_name', business_name[1].replace('DBA:', '').strip())
            elif len(business_name) == 1:
                l.add_value('business_name', business_name[0])
            if addresses:
                l.add_value('street_address', addresses.split(',')[0])
                state_zip = addresses.split(',')[-1]
                l.add_value('state', state_zip.split(' ')[0])
                l.add_value('postal_code', state_zip.split(' ')[-1])
                l.add_value('city', city)
                l.add_value('country', 'USA')
            yield l.load_item()

