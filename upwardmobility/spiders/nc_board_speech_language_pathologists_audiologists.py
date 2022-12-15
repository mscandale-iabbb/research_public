from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcBoardSpeechLanguagePathologistsAudiologistsSpider(scrapy.Spider):
    name = 'nc_board_speech_language_pathologists_audiologists'
    allowed_domains = ['ncslpa.us.thentiacloud.net']
    start_urls = ['https://ncslpa.us.thentiacloud.net/webs/ncslpa/register/#']
    headers = {
        'authority': 'ncslpa.us.thentiacloud.net',
        'accept': 'application/json, text/plain, */*',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        skip = 0
        for keyword in string.ascii_lowercase:
            u = f"https://ncslpa.us.thentiacloud.net/rest/public/profile/search/?keyword={keyword}&skip={skip}&take=20&lang=en&type=All"
            yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'skip':skip, 'keyword': keyword})

    def get_data(self, response):
        json_data = json.loads(response.text)
        for p in json_data['result']:
            if p['id'] in self.buf:
                continue
            self.buf.append(p['id'])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board for Speech - Language Pathologists and Audiologists')
            l.add_value('city', p['city'])
            l.add_value('street_address', p['address'])
            l.add_value('license_issue_date', p['currentEffectiveDate'])
            l.add_value('license_expiration_date', p['currentExpirationDate'])
            l.add_value('first_name', p['firstName'])
            l.add_value('date_business_started', p['initialRegistrationDate'])
            l.add_value('last_name', p['lastName'])
            l.add_value('middle_name', p['middleName'])
            l.add_value('phone', p['phone'])
            l.add_value('email', p['email'])
            l.add_value('license_status', p['registrationStatus'])
            l.add_value('license_number', p['registrationNumber'])
            l.add_value('license_type', p['registrationCategory'])
            l.add_value('state', p['state'])
            l.add_value('postal_code', p['zip'])
            yield l.load_item()

        if len(json_data['result']) == 20:
            skip = response.meta['skip']
            skip = skip + 20
            keyword = response.meta['keyword']
            u = f"https://ncslpa.us.thentiacloud.net/rest/public/profile/search/?keyword={keyword}&skip={skip}&take=20&lang=en&type=All"
            yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'skip':skip, 'keyword': keyword})

