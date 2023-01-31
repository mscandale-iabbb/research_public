from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class DepartmentConsumerAfairsSpider(scrapy.Spider):
    name = 'department_consumer_afairs'
    allowed_domains = ['search.dca.ca.gov']
    custom_settings={'CONCURRENT_REQUESTS': 1}
    start_urls = ['https://search.dca.ca.gov/']
    buf = []
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://search.dca.ca.gov',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
    }

    def parse(self, response):
        for fname in string.ascii_lowercase:
            for lname in string.ascii_lowercase:
                data = {
                    'boardCode': '0',
                    'licenseType': '0',
                    'licenseNumber': '',
                    'firstName': fname,
                    'lastName': lname,
                    'registryNumber': '',
                }

                yield scrapy.FormRequest(
                    'https://search.dca.ca.gov/results',
                    formdata=data,
                    callback=self.get_companies,
                    headers=self.headers
                )

    def get_companies(self, response):
        for href in response.xpath('//article//a[contains(@class, "button")]/@href').extract():
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile, headers=self.headers)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Department of Consumer Afairs')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//h1[@id="clntType"]//text()')
        license_number = response.xpath('//h2[@id="licDetail"]/text()').extract_first()
        if license_number:
            l.add_value('license_number', license_number.split(':')[1])
        full_name = response.xpath('//p[@id="name"]/text()').extract_first()
        if full_name:
            prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
        l.add_xpath('license_type', '//p[@id="licType"]/text()')
        l.add_xpath('license_status', '//p[@id="primaryStatus"]/text()')
        l.add_xpath('license_issue_date', '//p[@id="issueDate"]/text()')
        l.add_xpath('license_expiration_date', '//p[@id="expDate"]/text()')
        return l.load_item()

