from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcBoardOptometrySpider(scrapy.Spider):
    name = 'nc_board_optometry'
    allowed_domains = ['ncoptometry.org']
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    start_urls = ['https://www.ncoptometry.org/verify-a-license']
    headers = {
        'Accept': '*/*',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Origin': 'https://1291006813-atari-embeds.googleusercontent.com',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for query in string.ascii_lowercase:
            data = {
                'query': query,
                'ssn': '',
            }
            yield scrapy.FormRequest(
                url='https://public.ncoptometry.org/api/verify_license',
                formdata=data,
                callback=self.get_data,
                headers=self.headers
            )

    def get_data(self, response):
        for tag in response.xpath('//table/tbody/tr'):
            license_number = tag.xpath('./td[2]/text()').extract_first()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board Of Optometry')
            l.add_value('license_number', license_number)
            l.add_value('license_issue_date', tag.xpath('./td[3]/text()').extract_first())
            l.add_value('license_expiration_date', tag.xpath('./td[4]/text()').extract_first())
            l.add_value('license_status', tag.xpath('./td[5]/text()').extract_first())
            full_name = tag.xpath('./td[1]/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            yield l.load_item()

