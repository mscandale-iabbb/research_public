from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class UtSecuritiesDivisionSpider(scrapy.Spider):
    name = 'ut_securities_division'
    allowed_domains = ['db.securities.utah.gov']
    start_urls = ['https://db.securities.utah.gov/']
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    headers = {
        'Accept': 'text/html, */*; q=0.01',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Origin': 'https://db.securities.utah.gov',
        'Referer': 'https://db.securities.utah.gov/',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'X-Requested-With': 'XMLHttpRequest',
    }
    data = {
        'f': 'sn',
    }
    api_url = 'https://db.securities.utah.gov/assets/js/db-ajax.php'
    eids = []

    def parse(self, response):
        for v in string.ascii_lowercase:
            self.data['v'] = v
            yield scrapy.FormRequest(
                url=self.api_url,
                formdata=self.data,
                callback=self.get_data,
                headers=self.headers
            )

    def get_data(self, response):
        for tag in response.xpath('//tr[@class="clk-use"]'):
            eid = tag.xpath('@data-eid').extract_first()
            if eid in self.eids:
                continue
            self.eids.append(eid)
            data = {
                'f': 'le',
                'v': eid,
            }

            yield scrapy.FormRequest(
                url=self.api_url,
                formdata=data,
                callback=self.parse_profile,
                headers=self.headers,
                meta={'status': tag.xpath('./td[4]/text()').extract_first()}
            )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'UT Securities Division')
        l.add_xpath('business_name', '//h1/text()')
        l.add_xpath('license_number', '//h4[contains(., "ENTITY")]/following-sibling::p/text()')
        l.add_xpath('phone', '//h4[contains(., "PHONE")]/following-sibling::p/text()')
        l.add_xpath('title', '//h4[contains(., "TITLE")]/following-sibling::p/text()')
        l.add_value('license_status', response.meta['status'])
        addresses = response.xpath('//h4[contains(., "ADDRESS")]/following-sibling::p/text()').extract()
        if 'File' not in ''.join(addresses):
            if len(addresses) == 2:
                l.add_value('street_address', addresses[0])
                l.add_value('city', addresses[1].split(',')[0])
                l.add_value('state', addresses[1].split(',')[-1].strip().split(' ')[0])
                try:
                    l.add_value('postal_code', addresses[1].split(',')[-1].strip().split(' ')[1])
                except:
                    pass
            elif len(addresses) == 3:
                l.add_value('street_address', f"{addresses[0]} {addresses[1]}")
                l.add_value('city', addresses[2].split(',')[0])
                l.add_value('state', addresses[2].split(',')[-1].strip().split(' ')[0])
                try:
                    l.add_value('postal_code', addresses[2].split(',')[-1].strip().split(' ')[1])
                except:
                    pass

            l.add_value('country', 'USA')
        return l.load_item()

