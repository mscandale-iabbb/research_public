from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class UsDotSpider(scrapy.Spider):
    name = 'us_dot'
    allowed_domains = ['safer.fmcsa.dot.gov']
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    start_urls = ['https://safer.fmcsa.dot.gov/CompanySnapshot.aspx']
    headers = {
        'authority': 'safer.fmcsa.dot.gov',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'origin': 'https://safer.fmcsa.dot.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for searchText in string.ascii_lowercase:
            data = {
                'searchtype': 'ANY',
                'query_type': 'queryCarrierSnapshot',
                'query_param': 'NAME',
                'query_string': searchText,
            }
            yield scrapy.FormRequest(
                url='https://safer.fmcsa.dot.gov/query.asp',
                formdata=data,
                callback=self.get_data,
                headers=self.headers,
                dont_filter=True
            )

    def get_data(self, response):
        for href in response.xpath('//a[contains(@href, "query.asp?searchtype")]/@href').extract():
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile, headers=self.headers)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'US DOT')
        l.add_xpath('industry_type', '//th[contains(., "Entity Type:")]/following-sibling::td[1]/text()')
        l.add_xpath('license_status', '//th[contains(., "Operating Status:")]/following-sibling::td[1]/text()')
        l.add_xpath('business_name', '//th[contains(., "Legal Name:")]/following-sibling::td[1]/text()')
        l.add_xpath('secondary_business_name', '//th[contains(., "DBA Name:")]/following-sibling::td[1]/text()')
        l.add_xpath('phone', '//th[contains(., "Phone:")]/following-sibling::td[1]/text()')
        addresses = response.xpath('//th[contains(., "Physical Address:")]/following-sibling::td[1]/text()').extract()
        if len(addresses) == 2:
            l.add_value('street_address', addresses[0])
            l.add_value('city', addresses[1].split(',')[0])
            l.add_value('state', addresses[1].split(',')[-1].strip().split('\xa0')[0])
            l.add_value('postal_code', addresses[1].split(',')[-1].strip().split('\xa0')[1])
            l.add_value('country', 'USA')
        return l.load_item()
