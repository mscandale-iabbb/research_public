from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NevadaMedicalDoctorsSpider(scrapy.Spider):
    name = 'nevada_medical_doctors'
    allowed_domains = ['nsbme.us.thentiacloud.net']
    start_urls = ['https://nsbme.us.thentiacloud.net/rest/public/registrant/search/?keyword=all&skip=0&take=20']

    def parse(self, response):
        json_data = json.loads(response.text)
        for r in json_data['result']:
            u = f"https://nsbme.us.thentiacloud.net/rest/public/registrant/get/?id={r['id']}"
            yield scrapy.Request(u, callback=self.parse_profile)

        if len(json_data['result']) == 20:
            skip = response.url.split('skip=')[1].split('&take')[0]
            n_u = f"https://nsbme.us.thentiacloud.net/rest/public/registrant/search/?keyword=all&skip={int(skip)+20}&take=20"
            yield scrapy.Request(n_u)

    def parse_profile(self, response):
        json_data = json.loads(response.text)
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Nevada State board of Medical Examiners (Doctors)')
        l.add_value('postname', json_data['nameSuffix'])
        l.add_value('first_name', json_data['firstName'])
        l.add_value('last_name', json_data['lastName'])
        l.add_value('middle_name', json_data['middleName'])
        l.add_value('phone', json_data['phone'])
        l.add_value('license_type', json_data['licenseCategory'])
        l.add_value('license_number', json_data['licenseNumber'])
        l.add_value('license_status', json_data['licenseStatus'])
        l.add_value('license_issue_date', json_data['initialLicenseDate'])
        l.add_value('license_expiration_date', json_data['licenseExpirationDate'])
        l.add_value('industry_type', json_data['specialty'])
        l.add_value('street_address', json_data['address'])
        l.add_value('city', json_data['city'])
        l.add_value('state', json_data['state'])
        l.add_value('postal_code', json_data['zipcode'])
        return l.load_item()