from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class UsDeptTransportationSpider(scrapy.Spider):
    name = 'us_dept_transportation'
    allowed_domains = ['ai.fmcsa.dot.gov']
    start_urls = ['https://ai.fmcsa.dot.gov/hhg/SearchResults.asp?lan=EN&search=5&ads=a&state=PA']

    def parse(self, response):
        for tag in response.xpath('//td[@class="clsTopRightBoxPadd"]/table//tr[@scope]'):
            href = tag.xpath('.//a/@href').extract_first()
            industry_type = tag.xpath('./td[3]/text()').extract_first()
            number_of_employees = tag.xpath('./td[4]/text()').extract_first()
            yield scrapy.Request(
                url=response.urljoin(href),
                callback=self.parse_company,
                meta={
                    "industry_type": industry_type,
                    "number_of_employees": number_of_employees
                }
            )

    def parse_company(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'US Dept of Transportation (Interstate Movers)')
        l.add_value('company_url', response.url)
        l.add_value('industry_type', response.meta['industry_type'])
        l.add_value('number_of_employees', response.meta['number_of_employees'])
        l.add_xpath('business_name', '//h2[@class="titleheadline"]/text()')
        l.add_xpath('phone', '//td[contains(text(), "Telephone")]/following-sibling::td[2]/text()')
        l.add_xpath('fax', '//td[contains(text(), "Fax")]/following-sibling::td[2]/text()')
        address = response.xpath('//td[text()="Address"]/following-sibling::td[2]/text()').extract()
        l.add_value('street_address', address[0].strip())
        city = address[1].strip().split(',')[0].strip()
        state_zip = address[1].strip().split(',')[-1].strip()
        l.add_value('city', city)
        l.add_value('state', state_zip.split('\xa0')[0])
        l.add_value('postal_code', state_zip.split('\xa0')[1])
        return l.load_item()
        
