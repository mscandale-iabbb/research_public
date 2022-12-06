from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcSecretaryOfStateSpider(scrapy.Spider):
    name = 'nc_secretary_of_state'
    allowed_domains = ['sosnc.gov']
    start_urls = ['https://www.sosnc.gov/search/index/corp']
    headers = {
        'authority': 'www.sosnc.gov',
        'accept': '*/*',
        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'origin': 'https://www.sosnc.gov',
        'referer': 'https://www.sosnc.gov/search/index/corp',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        'x-requested-with': 'XMLHttpRequest',
    }
    buf = []

    def parse(self, response):
        __RequestVerificationToken = response.xpath('//input[@name="__RequestVerificationToken"]/@value').extract_first()
        for SearchCriteria in string.ascii_lowercase:
            data = {
                '__RequestVerificationToken': __RequestVerificationToken,
                'CorpSearchType': 'CORPORATION',
                'EntityType': 'ORGANIZATION',
                'Words': 'STARTING',
                'SearchCriteria': SearchCriteria,
                'IndividualsSurname': '',
                'FirstPersonalName': '',
                'AdditionalNamesInitials': '',
            }

            yield scrapy.FormRequest(
                url='https://www.sosnc.gov/online_services/search/Business_Registration_Results',
                formdata=data,
                callback=self.get_data,
                headers=self.headers,
            )

    def get_data(self, response):
        for href in response.xpath('//table[@class="double"]/tbody/tr//a[@class="java_link"]/@data-action').extract():
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NC Secretary of State')
        business_name = response.xpath('//span[contains(., "Legal Name")]/following-sibling::span/text()').extract_first()
        l.add_value('business_name', business_name)
        secondary_business_name = response.xpath('//span[contains(., "Prev Legal Name")]/following-sibling::span/text()').extract_first()
        if secondary_business_name:
            l.add_value('secondary_business_name', secondary_business_name)
        l.add_xpath('license_number', '//span[contains(., "SosId:")]/following-sibling::span[1]/text()')
        l.add_xpath('license_status', '//span[contains(., "Status:")]/following-sibling::span[1]/text()')
        l.add_xpath('license_issue_date', '//span[contains(., "Date Formed:")]/following-sibling::span[1]/text()')
        l.add_xpath('license_type', '//span[contains(., "Citizenship:")]/following-sibling::span[1]/text()')
        full_name = response.xpath('//span[contains(., "Registered Agent:")]/following-sibling::span[1]/a/text()').extract_first()
        if full_name:
            prename, postname, first_name, last_name, middle_name = parse_name(full_name)
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
        addresses = response.xpath('//span[contains(., "Mailing") and not(contains(., "Reg"))]/following-sibling::span[1]/text()').extract()
        addresses = [s.replace('\r\n', '').strip() for s in addresses if s.replace('\r\n', '').strip()]
        state_zip = response.xpath('//span[contains(., "Mailing") and not(contains(., "Reg"))]/following-sibling::span[1]/span/text()').extract()
        if addresses:
            l.add_value('street_address', addresses[0])
            l.add_value('city', addresses[1].rstrip(','))
            l.add_value('state', state_zip[0].strip())
            l.add_value('postal_code', state_zip[1].strip())
            l.add_value('country', 'USA')
        return l.load_item()
