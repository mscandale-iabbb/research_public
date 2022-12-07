from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcLandscapeContractorsLicensingBoardSpider(scrapy.Spider):
    name = 'nc_landscape_contractors_licensing_board'
    allowed_domains = ['public-nclclb.arlsys.com']
    start_urls = ['https://public-nclclb.arlsys.com/Public/Search']
    headers = {
        'authority': 'public-nclclb.arlsys.com',
        'accept': '*/*',
        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'origin': 'https://public-nclclb.arlsys.com',
        'referer': 'https://public-nclclb.arlsys.com/Public/Search',
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
                    url='https://public-nclclb.arlsys.com/Public/_Search/',
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
            u = f"https://public-nclclb.arlsys.com/Public/_ShowAccountDetails/{p_id}?Source=Search"
            yield scrapy.Request(u, callback=self.parse_profile, meta={'p_id': p_id})

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NC Landscape Contractors’ Licensing Board')
        business_name = response.xpath('//div[contains(., "Name")]/following-sibling::div[1]/text()').extract_first()
        if business_name and 'inc.' in business_name.lower() or ', llc' in business_name.lower():
            l.add_value('business_name', business_name.strip())
            secondary_business_name = response.xpath('//div[contains(., "Name")]/following-sibling::div[1]/text()').extract()
            if len(secondary_business_name) == 2 and 'DBA:' in ''.join(secondary_business_name):
                l.add_value('secondary_business_name', secondary_business_name[1].split('DBA:')[1].strip())
        elif business_name:
            prename, postname, first_name, last_name, middle_name = parse_name(business_name)
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
            secondary_business_name = response.xpath('//div[contains(., "Name")]/following-sibling::div[1]/text()').extract()
            if len(secondary_business_name) == 2 and 'DBA:' in ''.join(secondary_business_name):
                l.add_value('business_name', secondary_business_name[1].split('DBA:')[1].strip())
        l.add_xpath('phone', '//div[contains(., "Phone")]/following-sibling::div[1]/text()')
        l.add_xpath('email', '//div[contains(., "Email")]/following-sibling::div[1]/text()')        
        l.add_xpath('license_number', '//div[contains(., "License #")]/following-sibling::div[1]/text()')
        l.add_xpath('license_type', '//div[contains(., "Account Type")]/following-sibling::div[1]/text()')
        l.add_xpath('license_expiration_date', '//div[contains(., "Expiration Date")]/following-sibling::div[1]/text()')
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