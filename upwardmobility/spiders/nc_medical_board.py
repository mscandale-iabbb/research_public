from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcMedicalBoardSpider(scrapy.Spider):
    name = 'nc_medical_board'
    allowed_domains = ['portal.ncmedboard.org']
    start_urls = ['https://portal.ncmedboard.org/Verification/search.aspx']
    api_url = 'https://portal.ncmedboard.org/Verification/search.aspx'
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Accept-Language': 'en-US,en;q=0.9',
        'Origin': 'https://portal.ncmedboard.org',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for firstName in string.ascii_lowercase:
            for lastName in string.ascii_lowercase:
                post_data = self.get_post_data(response)
                post_data['ctl00$Content$txtFirst'] = firstName
                post_data['ctl00$Content$txtLast'] = lastName
                post_data['ctl00$Content$ddState'] = 'NC'
                post_data['ctl00$Content$btnSubmit'] = 'Search'
                yield scrapy.FormRequest(
                    url=self.api_url,
                    formdata=post_data,
                    callback=self.get_data,
                    headers=self.headers,
                    dont_filter=True
                )

    def get_data(self, response):
        for tag in response.xpath('//table[contains(@class, "table")]/tbody/tr'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            license_type = tag.xpath('./td[1]/text()').extract_first()
            l.add_value('license_type', license_type)
            full_name = tag.xpath('./td[2]/a/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            addresses = tag.xpath('./td[3]/text()').extract_first()
            l.add_value('street_address', ', '.join(addresses.split(',')[:-1]).strip())
            l.add_value('state', addresses.split(',')[-1].strip())
            l.add_value('country', 'USA')
            license_number = tag.xpath('./td[4]/text()').extract_first()
            l.add_value('license_number', license_number)
            license_status = tag.xpath('./td[5]/span/text()').extract_first()
            l.add_value('license_status', license_status)
            href = tag.xpath('./td[2]/a/@href').extract_first()
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile, meta={'item': l.load_item()})

    def parse_profile(self, response):
        l = CompanyLoader(response.meta.get("item", UpwardMobilityItem()), response=response)
        l.add_value('source', 'NC Medical Board')
        l.add_value('company_url', response.url)
        l.add_xpath('license_issue_date', '//div[./label[contains(., "Issue Date:")]]/following-sibling::div[1]/text()')
        l.add_xpath('license_expiration_date', '//div[./label[contains(., "Expire Date:")]]/following-sibling::div[1]/text()')
        return l.load_item()

    def get_post_data(self, response):
        post_data = {}
        for tag in response.xpath('//input[@type="hidden"]'):
            label = tag.xpath('@name').extract_first()
            value = tag.xpath('@value').extract_first()
            post_data[label] = value if value else ''
        return post_data