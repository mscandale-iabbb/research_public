from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class AlbertaMotorVehicleIndustryCouncilSpider(scrapy.Spider):
    name = 'alberta_motor_vehicle_industry_council'
    allowed_domains = ['amvic.ca.thentiacloud.net']
    start_urls = ['https://amvic.ca.thentiacloud.net/webs/amvic/register/#']
    buf = []

    def parse(self, response):
        for key_name in string.ascii_lowercase:
            u = f"https://amvic.ca.thentiacloud.net/rest/public/registrant/search/?keyword={key_name}&skip=0&take=10"
            yield scrapy.Request(u, callback=self.get_data, meta={'skip': 0, 'key_name': key_name})

    def get_data(self, response):
        json_data = json.loads(response.text)
        for p in json_data['result']:
            if p['id'] in self.buf:
                continue
            self.buf.append(p['id'])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Alberta Motor Vehicle Industry Council')
            l.add_value('first_name', p['firstName'])
            l.add_value('last_name', p['lastName'])
            if p['middleName'] != 'N/A':
                l.add_value('middle_name', p['middleName'])
            l.add_value('license_number', p['licenseNumber'])
            l.add_value('license_expiration_date', p['licenseExpirationDate'])
            l.add_value('license_type', p['licenseCategory'])
            l.add_value('license_status', p['licenseStatus'])
            yield l.load_item()

        if len(json_data['result']) == 10:
            key_name = response.meta['key_name']
            skip = response.meta['skip']
            u = f"https://amvic.ca.thentiacloud.net/rest/public/registrant/search/?keyword={key_name}&skip={skip+10}&take=10"
            yield scrapy.Request(u, callback=self.get_data, meta={'skip': skip+10, 'key_name': key_name})
