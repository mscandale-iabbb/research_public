from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PaDeptStateSpider(scrapy.Spider):
    name = 'pa_dept_state'
    allowed_domains = ['pals.pa.gov']
    start_urls = ['https://www.pals.pa.gov/#/page/search']

    def parse(self, response):
        headers = {
            'Accept': 'application/json, text/plain, */*',
            'Content-Type': 'application/json;charset=UTF-8',
            'Origin': 'https://www.pals.pa.gov',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        }

        json_data = {
            'OptPersonFacility': 'Person',
            'State': '',
            'Country': 'ALL',
            'County': None,
            'IsFacility': 0,
            'PersonId': None,
            'PageNo': 1,
        }
        yield scrapy.Request(
            url='https://www.pals.pa.gov/api/Search/SearchForPersonOrFacilty',
            method='POST',
            body=json.dumps(json_data),
            callback=self.get_data,
            headers=headers
        )

    def get_data(self, response):
        for f in json.loads(response.text):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA Dept of State')
            l.add_value('business_name', 'BoardName')
            l.add_value('city', f['City'])
            l.add_value('postal_code', f['zipcode'])
            l.add_value('state', 'PA')
            l.add_value('country', 'USA')
            l.add_value('first_name', f['FirstName'])
            l.add_value('last_name', f['LastName'])
            l.add_value('middle_name', f['MiddleName'])
            l.add_value('postname', f['NameSuffix'])
            l.add_value('city', f['City'])
            l.add_value('license_number', f['LicenseNumber'])
            l.add_value('license_type', f['LicenceType'])
            l.add_value('license_status', f['Status'])
            l.add_value('industry_type', f['ProfessionType'])
            l.add_value('email', f['Emailid1'])
            l.add_value('phone', f['PhoneNo1'])
            yield l.load_item()
