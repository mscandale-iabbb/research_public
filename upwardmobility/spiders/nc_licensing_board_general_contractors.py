from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcLicensingBoardGeneralContractorsSpider(scrapy.Spider):
    name = 'nc_licensing_board_general_contractors'
    allowed_domains = ['portal.nclbgc.org']
    start_urls = ['https://portal.nclbgc.org/Public/Search']
    headers = {
        'authority': 'portal.nclbgc.org',
        'accept': '*/*',
        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'origin': 'https://portal.nclbgc.org',
        'referer': 'https://portal.nclbgc.org/Public/Search',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        'x-requested-with': 'XMLHttpRequest',
    }
    buf = []

    def parse(self, response):
        for firstName in string.ascii_lowercase:
            for lastName in string.ascii_lowercase:
                data = {
                    'ClassificationDefinitionIdnt': '',
                    'AccountNumber': '',
                    'QualifierAccountNumber': '',
                    'CompanyName': '',
                    'FirstName': firstName,
                    'LastName': lastName,
                    'PhoneNumber': '',
                    'streetAddress': '',
                    'PostalCode': '',
                    'City': '',
                    'StateCode': '',
                }

                yield scrapy.FormRequest(
                    url='https://portal.nclbgc.org/Public/_Search/',
                    formdata=data,
                    callback=self.get_data,
                    headers=self.headers
                )

    def get_data(self, response):
        for onclick in response.xpath('//table[@id="AccountSearchTable"]/tbody/tr//a/@onclick').extract():
            p_id = onclick.split("ShowAccountDetails(")[1].split(")")[0]
            if p_id in self.buf:
                continue
            self.buf.append(p_id)
            u = f"https://portal.nclbgc.org/Public/_ShowAccountDetails/{p_id}?Source=Search"
            yield scrapy.Request(u, callback=self.parse_profile)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NC Licensing Board for General Contractors')
        business_name = response.xpath('//div[contains(., "Name")]/following-sibling::div[1]/text()').extract_first()
        l.add_value('business_name', business_name)
        secondary_business_name = response.xpath('//div[contains(., "Name")]/following-sibling::div[1]/text()').extract()
        if len(secondary_business_name) == 2 and 'DBA:' in ''.join(secondary_business_name):
            l.add_value('secondary_business_name', secondary_business_name[1].split('DBA:')[1].strip())
        l.add_xpath('phone', '//div[contains(., "Phone")]/following-sibling::div[1]/text()')
        l.add_xpath('email', '//div[contains(., "Email")]/following-sibling::div[1]/text()')        
        l.add_xpath('license_number', '//div[contains(., "License #")]/following-sibling::div[1]/text()')
        l.add_xpath('license_type', '//div[contains(., "Account Type")]/following-sibling::div[1]/text()')
        l.add_xpath('license_issue_date', '//div[contains(., "Effective Date")]/following-sibling::div[1]/text()')
        l.add_xpath('license_expiration_date', '//div[contains(., "Expiration Date")]/following-sibling::div[1]/text()')
        l.add_xpath('license_status', '//div[contains(., "Status")]/following-sibling::div[1]/span/text()')
        l.add_xpath('industry_type', '//*[contains(text(), "Classifications")]/following-sibling::div[1]/text()')
        addresses = response.xpath('//div[contains(., "Address")]/following-sibling::div[1]/text()').extract()
        if len(addresses) == 2:
            l.add_value('street_address', addresses[0].rstrip('\r'))
            l.add_value('city', ', '.join(addresses[1].split(',')[:-1]).strip())
            state_zip = addresses[1].split(',')[-1]
            l.add_value('state', ' '.join(state_zip.split(' ')[:-1]).strip())
            l.add_value('postal_code', state_zip.split(' ')[-1].strip())
            l.add_value('country', 'USA')
        elif len(addresses) == 3:
            str1 = addresses[0].rstrip('\r')
            str2 = addresses[1].rstrip('\r')
            l.add_value('street_address', f"{str1}{str2}")
            l.add_value('city', ', '.join(addresses[2].split(',')[:-1]).strip())
            state_zip = addresses[2].split(',')[-1]
            l.add_value('state', ' '.join(state_zip.split(' ')[:-1]).strip())
            l.add_value('postal_code', state_zip.split(' ')[-1].strip())
            l.add_value('country', 'USA')
        return l.load_item()

