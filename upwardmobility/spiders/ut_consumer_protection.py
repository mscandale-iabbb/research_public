from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class UtConsumerProtectionSpider(scrapy.Spider):
    name = 'ut_consumer_protection'
    allowed_domains = ['consumerprotection.utah.gov']
    # custom_settings = {'DOWNLOAD_TIMEOUT': 300, 'DOWNLOAD_DELAY': 10}
    start_urls = ['http://consumerprotection.utah.gov/registered.html']
    headers = {
        'Accept': 'text/html, */*; q=0.01',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Origin': 'http://consumerprotection.utah.gov',
        'Referer': 'http://consumerprotection.utah.gov/registered.html',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'X-Requested-With': 'XMLHttpRequest',
    }

    data = {
        'f': 's',
        't': 'ALL',
    }
    api_url = 'http://consumerprotection.utah.gov/assets/js/registered-ajax.php'
    buf = []

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
        for eid in response.xpath('//table[@id="tblEntities"]//button/@data-eid').extract():
            if eid in self.buf:
                continue
            self.buf.append(eid)
    
            data = {
                'f': 'e',
                'v': eid,
            }
            yield scrapy.FormRequest(
                url='http://consumerprotection.utah.gov/assets/js/registered-ajax.php',
                formdata=data,
                callback=self.parse_profile,
                headers=self.headers,
                meta={'eid': eid}
            )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'UT Consumer Protection (telemarketing, charities, pawn shops)')
        l.add_xpath('business_name', '//div[contains(@class, "card-body")]/h3/text()')
        l.add_xpath('secondary_business_name', '//b[contains(.,"DBA:")]/following-sibling::text()')
        l.add_xpath('industry_type', '//b[contains(.,"TYPE:")]/following-sibling::text()')
        l.add_xpath('license_number', '//b[contains(.,"REGISTRATION")]/following-sibling::text()')
        l.add_xpath('license_status', '//p[./b[contains(.,"STATUS:")]]/span/text()')
        l.add_xpath('license_expiration_date', '//b[contains(.,"EXPIRES:")]/following-sibling::text()')
        addresses = response.xpath('//p[./b[contains(.,"ADDRESS:")]]/following-sibling::p[1]/text()').extract()
        try:
            l.add_value('street_address', ''.join(addresses[:-1]).strip())
            l.add_value('city', addresses[-1].split(',')[0].strip())
            l.add_value('state', addresses[-1].split(',')[1].strip().split(' ')[0])
            l.add_value('postal_code', addresses[-1].split(',')[1].strip().split(' ')[1])
        except:
            return
        l.add_value('country', 'USA')
        return l.load_item()