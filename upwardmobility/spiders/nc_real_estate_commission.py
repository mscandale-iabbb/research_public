from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcRealEstateCommissionSpider(scrapy.Spider):
    name = 'nc_real_estate_commission'
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['license.ncrec.gov']
    start_urls = ['https://license.ncrec.gov/ncrec/oecgi3.exe/O4W_LIC_SEARCH_NEW']
    headers = {
        'authority': 'license.ncrec.gov',
        'accept': '*/*',
        'origin': 'https://license.ncrec.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        'x-requested-with': 'XMLHttpRequest',
    }
    buf = []

    def parse(self, response):
        o4wuniqueID = re.search(r'o4wuniqueID="(.*?)",', response.text).group(1)
        for str1 in string.ascii_lowercase:
            for str2 in string.ascii_lowercase:
                name = f"{str1}{str2}"
                data = [
                    ('O4WDispatch', 'O4W_LIC_SEARCH_NEW'),
                    ('O4WControl', 'SUBMITBTN'),
                    ('O4WEvent', 'CLICK'),
                    ('O4WControlBackup', 'SUBMITBTN'),
                    ('LIC_NO', ''),
                    ('Name', name),
                    ('CITY', ''),
                    ('County', ''),
                    ('NO', ''),
                    ('O4WControlBackup', 'SUBMITBTN'),
                    ('O4WUniqueID', o4wuniqueID),
                    ('O4WSubCount', '1'),
                    ('O4WMobile', '0'),
                    ('O4WThisDispatch', 'O4W_LIC_SEARCH_NEW'),
                ]

                yield scrapy.FormRequest(
                    url='https://license.ncrec.gov/ncrec/oecgi3.exe/O4W_LIC_SEARCH_NEW',
                    formdata=data,
                    callback=self.get_data,
                    headers=self.headers,
                )

    def get_data(self, response):
        for href in response.xpath('//table[@id="MAIN"]/tbody/tr/td/a/@href').extract():
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NC Real Estate Commission')
        license_number = response.xpath('//span[contains(., "License:")]/following-sibling::text()').extract_first()
        l.add_value('license_number', license_number)
        license_issue_date = response.xpath('//span[contains(., "Issue Date:")]/following-sibling::text()').extract_first()
        l.add_value('license_issue_date', license_issue_date)
        full_name = response.xpath('//span[contains(., "Name:")]/following-sibling::text()').extract_first()
        if full_name:
            prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
        industry_type = response.xpath('//span[contains(., "Type:")]/following-sibling::text()').extract_first()
        l.add_value('industry_type', industry_type)
        license_status = response.xpath('//span[contains(., "Status:")]/following-sibling::text()').extract_first()
        l.add_value('license_status', license_status)
        business_name = response.xpath('//span[contains(., "Primary Firm:")]/following-sibling::a[1]/following-sibling::text()').extract_first()
        l.add_value('business_name', business_name)
        l.add_xpath('street_address', '//table[@id="INFO"]/tbody/tr[1]/td[1]/text()')
        city_state_zip = response.xpath('//table[@id="INFO"]/tbody/tr[2]/td[1]/text()').extract_first()
        if city_state_zip:
            print(city_state_zip)
            l.add_value('city', city_state_zip.split(',')[0])
            l.add_value('state', city_state_zip.split(',')[1].strip().split(' ')[0])
            l.add_value('postal_code', city_state_zip.split(',')[1].strip().split(' ')[-1])
            l.add_value('country', 'USA')
        return l.load_item()
