from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class AlBoardChiropracticExaminersSpider(scrapy.Spider):
    name = 'al_board_chiropractic_examiners'
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['alabamainteractive.org']
    start_urls = ['https://www.alabamainteractive.org/asbce/verification/licenseSearch.action']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://www.alabamainteractive.org',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for str1 in string.ascii_lowercase:
            for str2 in string.ascii_lowercase:
                data = {
                    'licenseeName': f'{str1}{str2}',
                    'licenseNumber': '',
                }
                yield scrapy.FormRequest(
                    url = 'https://www.alabamainteractive.org/asbce/verification/licenseSearch.action;wsuid=0A4387AADA3A6BAC8E5441B4A07FC393',
                    formdata=data,
                    callback=self.get_data,
                    headers=self.headers,
                    dont_filter=True
                )

    def get_data(self, response):
        for tag in response.xpath('//form[@id="licenseSelection"]/div[@class="row"]'):
            texts = tag.xpath('./div[contains(@class, "large-7")]//text()').extract()
            full_name = texts[1].split('\xa0')[0]
            license_number = texts[1].split('\xa0')[1].replace("(", "").replace(")", "")
            if license_number in self.buf:
                continue
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'AL Board of Chiropractic Examiners')
            self.buf.append(license_number)
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_value('license_number', license_number)
            l.add_value('business_name', texts[3].strip())
            l.add_value('street_address', texts[4].strip())
            city_state_zip = texts[5]
            city_state_zip = replace_nbsp(city_state_zip)
            if '-' in city_state_zip:
                print(city_state_zip)
                l.add_value('city', city_state_zip.split(',')[0])
                l.add_value('state', city_state_zip.split(',')[1].strip().split(' ')[0])
                l.add_value('postal_code', city_state_zip.split(',')[1].strip().split(' ')[1])
                l.add_value('country', 'USA')
            yield l.load_item()