from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcStateBoardOfExaminersSpider(scrapy.Spider):
    name = 'nc_state_board_of_examiners'
    allowed_domains = ['public.nclicensing.org']
    start_urls = ['https://public.nclicensing.org/Public/Search']
    headers = {
        'authority': 'public.nclicensing.org',
        'accept': '*/*',
        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'origin': 'https://public.nclicensing.org',
        'referer': 'https://public.nclicensing.org/Public/Search',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        'x-requested-with': 'XMLHttpRequest',
    }
    buf = []

    def parse(self, response):
        for companyName in string.ascii_lowercase:
            data = {
                'ClassificationDefinitionIdnt': '',
                'AccountNumber': '',
                'CompanyName': companyName,
                'FirstName': '',
                'LastName': '',
                'PhoneNumber': '',
                'streetAddress': '',
                'PostalCode': '',
                'City': '',
                'StateCode': '',
            }

            yield scrapy.FormRequest(
                url='https://public.nclicensing.org/Public/_Search/',
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
            u = f"https://public.nclicensing.org/Public/_ShowAccountDetails/{p_id}?Source=Search"
            yield scrapy.Request(u, callback=self.parse_profile)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NC State Board of Examiners of Plumbing, Heating & Fire Sprinkler Contractors')
        l.add_xpath('business_name', '//div[contains(., "Company")]/following-sibling::div[1]/text()')
        full_name = response.xpath('//div[contains(., "Name")]/following-sibling::div[1]/text()').extract_first()
        if full_name:
            prename, postname, first_name, last_name, middle_name = parse_name(full_name)
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
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
            l.add_value('state', state_zip.split(' ')[0].strip())
            l.add_value('postal_code', state_zip.split(' ')[1].strip())
            l.add_value('country', 'USA')
        elif len(addresses) == 3:
            str1 = addresses[0].rstrip('\r')
            str2 = addresses[1].rstrip('\r')
            l.add_value('street_address', f"{str1}{str2}")
            l.add_value('city', ', '.join(addresses[2].split(',')[:-1]).strip())
            state_zip = addresses[2].split(',')[-1]
            l.add_value('state', state_zip.split(' ')[0].strip())
            l.add_value('postal_code', state_zip.split(' ')[1].strip())
            l.add_value('country', 'USA')
        return l.load_item()
