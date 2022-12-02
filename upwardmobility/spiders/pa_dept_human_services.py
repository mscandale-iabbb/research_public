from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PaDeptHumanServicesSpider(scrapy.Spider):
    name = 'pa_dept_human_services'
    allowed_domains = ['humanservices.state.pa.us']
    start_urls = ['https://www.humanservices.state.pa.us/HUMAN_SERVICE_PROVIDER_DIRECTORY/']
    headers = {
        'Accept': '*/*',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Origin': 'https://www.humanservices.state.pa.us',
        'Referer': 'https://www.humanservices.state.pa.us/HUMAN_SERVICE_PROVIDER_DIRECTORY/',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'X-Requested-With': 'XMLHttpRequest',
    }

    def parse(self, response):
        __RequestVerificationToken = response.xpath('//input[@name="__RequestVerificationToken"]/@value').extract_first()
        data = {
            '__RequestVerificationToken': __RequestVerificationToken,
            'ReturnSearchScreen': '',
            'ServiceCode': '',
            'Region': '',
            'FacilityName': '',
            'ProgramOffice': '',
            'City': '',
            'County': '',
            'ZipCode': '',
            'LicenseStatusType': '',
            'X-Requested-With': 'XMLHttpRequest',
        }
        yield scrapy.FormRequest(
            url='https://www.humanservices.state.pa.us/HUMAN_SERVICE_PROVIDER_DIRECTORY/Home/HumanServicesProviderDirectorySearchResult',
            formdata=data,
            callback=self.get_companies,
            headers=self.headers
        )

    def get_companies(self, response):
        for tag in response.xpath('//table[@class="table"]/tbody/tr'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA Dept of Human Services')
            l.add_value('company_url', response.url)
            l.add_value('industry_type', tag.xpath('./td[0]/text()').extract_first())
            # l.add_value('secondary_business_name', tag.xpath('./td[2]/text()').extract_first())
            text = tag.xpath('./td[3]/text()').extract()
            l.add_value('business_name', text[0].strip())
            l.add_value('secondary_business_name', text[1].strip())
            l.add_value('street_address', text[2].strip())
            l.add_value('city', text[3].split(',')[0].strip())
            l.add_value('state', text[3].split(',')[-1].strip().split('\xa0')[0].strip())
            l.add_value('postal_code', text[3].split(',')[-1].strip().split('\xa0')[1].strip())
            l.add_value('country', 'USA')
            if 'Phone:' in text[4]:
                l.add_value('phone', text[4].split(':')[-1].strip())
            l.add_value('number_of_employees', tag.xpath('./td[4]/text()').extract_first())
            l.add_value('license_type', tag.xpath('./td[7]/text()').extract_first())
            license_status_number = tag.xpath('./td[6]/text()').extract()
            l.add_value('license_status', license_status_number[0].strip())
            l.add_value('license_number', license_status_number[1].replace('[', '').replace(']', '').strip())
            yield l.load_item()

