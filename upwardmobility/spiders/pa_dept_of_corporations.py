from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PaDeptOfCorporationsSpider(scrapy.Spider):
    name = 'pa_dept_of_corporations'
    allowed_domains = ['file.dos.pa.gov']
    start_urls = ['https://file.dos.pa.gov/search/business']
    headers = {
        'Accept': '*/*',
        'content-type': 'application/json',
        'Origin': 'https://file.dos.pa.gov',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'authorization': 'undefined',
    }
    json_data = {
        'SEARCH_FILTER_TYPE_ID': '1',
        'FILING_TYPE_ID': '',
        'STATUS_ID': '',
        'FILING_DATE': {
            'start': None,
            'end': None,
        },
    }
    post_link = 'https://file.dos.pa.gov/api/Records/businesssearch'
    buf = []

    def parse(self, response):
        for searchText1 in string.ascii_lowercase:
            for searchText2 in string.ascii_lowercase:
                SEARCH_VALUE = f"{searchText1}{searchText2}"
                self.json_data['SEARCH_VALUE'] = SEARCH_VALUE
                yield scrapy.Request(
                    url=self.post_link,
                    method='POST',
                    body=json.dumps(self.json_data),
                    callback=self.get_data,
                    headers=self.headers
                )


    def get_data(self, response):
        json_data = json.loads(response.text)['rows']
        for t in json_data:
            d = json_data[t]
            if d['ID'] in self.buf:
                continue
            self.buf.append(d['ID'])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA Dept of Corporations')
            l.add_value('business_name', d['TITLE'][0].split("(")[0])
            l.add_value('license_number', d['TITLE'][0].split("(")[1].split(")")[0])
            l.add_value('license_status', d['STATUS'])
            l.add_value('license_issue_date', d['FILING_DATE'])
            l.add_value('industry_type', d['ENTITY_TYPE'])
            if ', PA' in d['AGENT']: 
                addresses = d['AGENT'].split(',')
                state_zip = addresses[-1]
                city = addresses[-2]
                street_address = ''.join(addresses[:-2])
                l.add_value('street_address', street_address.strip())
                l.add_value('city', city.strip())
                l.add_value('state', state_zip.strip().split(' ')[0].strip())
                l.add_value('postal_code', state_zip.strip().split(' ')[-1].strip())
                l.add_value('country', 'USA')
            else:
                l.add_value('secondary_business_name', d['AGENT'])
            yield l.load_item()
