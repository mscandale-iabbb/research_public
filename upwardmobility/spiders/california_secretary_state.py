from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class CaliforniaSecretaryStateSpider(scrapy.Spider):
    name = 'california_secretary_state'
    allowed_domains = ['bizfileonline.sos.ca.gov']
    start_urls = ['https://bizfileonline.sos.ca.gov/search/business']
    custom_settings={'CONCURRENT_REQUESTS': 1}
    headers = {
        'authority': 'bizfileonline.sos.ca.gov',
        'accept': '*/*',
        'authorization': 'undefined',
        'content-type': 'application/json',
        'origin': 'https://bizfileonline.sos.ca.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for str1 in string.ascii_lowercase:
            for str2 in string.ascii_lowercase:
                for str3 in string.ascii_lowercase:
                    SEARCH_VALUE = f"{str1}{str2}{str3}"
                    json_data = {
                        'SEARCH_VALUE': SEARCH_VALUE,
                        'SEARCH_FILTER_TYPE_ID': '0',
                        'SEARCH_TYPE_ID': '1',
                        'FILING_TYPE_ID': '',
                        'STATUS_ID': '',
                        'FILING_DATE': {
                            'start': None,
                            'end': None,
                        },
                        'CORPORATION_BANKRUPTCY_YN': False,
                        'CORPORATION_LEGAL_PROCEEDINGS_YN': False,
                        'OFFICER_OBJECT': {
                            'FIRST_NAME': '',
                            'MIDDLE_NAME': '',
                            'LAST_NAME': '',
                        },
                        'NUMBER_OF_FEMALE_DIRECTORS': '99',
                        'NUMBER_OF_UNDERREPRESENTED_DIRECTORS': '99',
                        'COMPENSATION_FROM': '',
                        'COMPENSATION_TO': '',
                        'SHARES_YN': False,
                        'OPTIONS_YN': False,
                        'BANKRUPTCY_YN': False,
                        'FRAUD_YN': False,
                        'LOANS_YN': False,
                        'AUDITOR_NAME': '',
                    }
                    yield scrapy.Request(
                        url='https://bizfileonline.sos.ca.gov/api/Records/businesssearch',
                        method='POST',
                        body=json.dumps(json_data),
                        callback=self.get_data,
                        headers=self.headers,
                    )

    def get_data(self, response):
        json_data = json.loads(response.text)
        for r in json_data['rows'].values():
            if r['ID'] in self.buf:
                continue
            self.buf.append(r['ID'])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'California Secretary of State')
            full_name = r['AGENT']
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_value('license_issue_date', r['FILING_DATE'])
            l.add_value('state', r['FORMED_IN'])
            l.add_value('country', 'USA')
            l.add_value('license_status', r['STATUS'])
            l.add_value('license_type', r['ENTITY_TYPE'])
            if r['TITLE']:
                l.add_value('business_name', r['TITLE'][0].split(' (')[0])
            l.add_value('license_number', r['RECORD_NUM'])
            yield l.load_item()
