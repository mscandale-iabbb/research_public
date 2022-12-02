from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NvVeterinaryMedicalExaminersSpider(scrapy.Spider):
    name = 'nv_veterinary_medical_examiners'
    allowed_domains = ['nsbvme.us.thentiacloud.net']
    start_urls = ['https://nsbvme.us.thentiacloud.net/rest/public/registrant/search/?keyword=all&skip=0&take=10']

    def parse(self, response):
        json_data = json.loads(response.text)
        for r in json_data['result']:
            u = f"https://nsbvme.us.thentiacloud.net/rest/public/registrant/get/?id={r['id']}"
            yield scrapy.Request(u, callback=self.parse_profile)

        if len(json_data['result']) == 10:
            skip = response.url.split('skip=')[1].split('&take')[0]
            n_u = f"https://nsbvme.us.thentiacloud.net/rest/public/registrant/search/?keyword=all&skip={int(skip)+10}&take=10"
            yield scrapy.Request(n_u)

    def parse_profile(self, response):
        json_data = json.loads(response.text)
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NV State Board of Veterinary Medical Examiners')
        l.add_value('first_name', json_data['firstName'])
        l.add_value('last_name', json_data['lastName'])
        l.add_value('middle_name', json_data['middleName'])
        placesOfPractice = json_data.get('placesOfPractice', [])
        if placesOfPractice:
            l.add_value('street_address', json_data['placesOfPractice'][0]['businessAddress'])
            l.add_value('city', json_data['placesOfPractice'][0]['businessCity'])
            l.add_value('state', json_data['placesOfPractice'][0]['businessState'])
            l.add_value('postal_code', json_data['placesOfPractice'][0]['businessZipCode'])
            l.add_value('country', 'USA')
            l.add_value('business_name', json_data['placesOfPractice'][0]['employerName'])
            l.add_value('email', json_data['placesOfPractice'][0]['email'])
            l.add_value('phone', json_data['placesOfPractice'][0]['phone'])

        registrationRecords = json_data.get('registrationRecords', [])
        if registrationRecords:
            l.add_value('license_number', json_data['registrationRecords'][0]['licenseNumber'])
            l.add_value('license_expiration_date', json_data['registrationRecords'][0]['expiryDate'])
            l.add_value('license_issue_date', json_data['registrationRecords'][0]['initialRegistrationDate'])
            l.add_value('license_status', json_data['registrationRecords'][0]['registrationStatus'])
            l.add_value('license_type', json_data['registrationRecords'][0]['classOfRegistration'])

        return l.load_item()