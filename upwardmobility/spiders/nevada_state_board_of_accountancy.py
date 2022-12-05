from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NevadaStateBoardOfAccountancySpider(scrapy.Spider):
    name = 'nevada_state_board_of_accountancy'
    allowed_domains = ['nvaccountancy.com']
    start_urls = ['https://www.nvaccountancy.com/search.fx']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://www.nvaccountancy.com',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for cpaname in string.ascii_lowercase:
            data = {
                'cpaid': '',
                'cpaname': cpaname,
                'search.x': '11',
                'search.y': '13',
            }
            yield scrapy.FormRequest(
                url='https://www.nvaccountancy.com/search.fx',
                formdata=data,
                callback=self.get_data,
                headers=self.headers
            )

    def get_data(self, response):
        for href in response.xpath('//td/table[@style]//a[contains(@href, "show=")]/@href').extract():
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_company, headers=self.headers)

    def parse_company(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Nevada State Board of Accountancy')
        license_number = response.xpath('//span[contains(., "License #")]/text()').extract_first()
        if license_number:
            l.add_value('license_number', license_number.split(':')[-1].strip())
        l.add_xpath('license_status', '//span[contains(., "Status:")]/following-sibling::span/text()')
        license_issue_date = response.xpath('//span[contains(., "Date:")]/text()').extract_first()
        if license_issue_date:
            l.add_value('license_issue_date', license_issue_date.split(':')[-1].strip())
        license_expiration_date = response.xpath('//span[contains(., "Valid Through")]/text()').extract_first()
        if license_expiration_date:
            l.add_value('license_expiration_date', license_expiration_date.split('Through')[-1].strip())
        info = response.xpath('//span[@class="headertext"]/text()').extract()
        if len(info) == 3:
            l.add_value('business_name', info[0])
            l.add_value('street_address', info[1])
            l.add_value('city', ' '.join(info[2].split(',')[:-1]).strip())
            l.add_value('state', ' '.join(info[2].split(',')[-1].strip().split(' ')[:-1]).strip())
            l.add_value('postal_code', info[2].split(',')[-1].strip().split(' ')[-1])
            l.add_value('country', 'USA')
        elif len(info) == 2:
            l.add_value('business_name', info[0])
            l.add_value('state', info[1].split(',')[-1].strip())
            l.add_value('city', ' '.join(info[1].split(',')[:-1]).strip())
            l.add_value('country', 'USA')
        elif len(info) == 1:
            l.add_value('business_name', info[0])

        return l.load_item()
