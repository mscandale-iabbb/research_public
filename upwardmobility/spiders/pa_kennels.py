from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PAKennelsSpider(scrapy.Spider):
    name = 'pa_kennels'
    allowed_domains = ['pda.pa.gov']
    start_urls = ['http://www.pda.pa.gov/PADogLawPublicKennelInspectionSearch/KennelInspections/Index/SearchForm']
    custom_settings = {'DOWNLOAD_TIMEOUT': 600}
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'http://www.pda.pa.gov',
        'Referer': 'http://www.pda.pa.gov/PADogLawPublicKennelInspectionSearch/KennelInspections/Index/SearchForm',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }

    data = {
        'County': '',
        'KennelType': '',
        'LicenseNumber': '',
        'KennelName': '',
        'KennelPersonLastName': '',
        'KennelPersonFirstName': '',
        'City': '',
        'ZipCode': '',
    }
    def parse(self, response):
        yield scrapy.Request(
            url='http://www.pda.pa.gov/PADogLawPublicKennelInspectionSearch/KennelInspections/Index/SearchForm',
            method='POST',
            body=json.dumps(self.data),
            callback=self.get_companies,
            headers=self.headers
        )

    def get_companies(self, response):
        for href in response.xpath('//table[@class="table"]/tr//a/@href').extract():
            yield scrapy.Request(response.urljoin(href), callback=self.parse_company, headers=self.headers)

    def parse_company(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'PA Kennels')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//div[./div[@class="form-group"][contains(., "KENNEL")]]/following-sibling::div[1]/div/div[1]/text()')
        l.add_xpath('street_address', '//div[./div[@class="form-group"][contains(., "KENNEL")]]/following-sibling::div[1]/div/div[2]/text()')
        city_state_zip = response.xpath('//div[./div[@class="form-group"][contains(., "KENNEL")]]/following-sibling::div[1]/div/div[4]/text()').extract_first()
        l.add_value('city', city_state_zip.split(',')[0].strip())
        l.add_value('state', city_state_zip.split(',')[-1].strip().split('\n')[0].strip())
        l.add_value('postal_code', city_state_zip.split(',')[-1].strip().split('\n')[-1].strip())
        l.add_value('country', 'USA')
        l.add_xpath('license_number', '//div[./div[@class="form-group"][contains(., "LICENSE NUMBER")]]/following-sibling::div[1]/div/div[1]/text()')
        l.add_xpath('license_status', '//div[./div[@class="form-group"][contains(., "LICENSE NUMBER")]]/following-sibling::div[1]/div/div[2]/text()')
        l.add_xpath('license_issue_date', '//div[./div[@class="form-group"][contains(., "LICENSE NUMBER")]]/following-sibling::div[1]/div/div[3]/text()')
        return l.load_item()

