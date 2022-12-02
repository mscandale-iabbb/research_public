from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NevadaMedicalDentistsSpider(scrapy.Spider):
    name = 'nevada_medical_dentists'
    allowed_domains = ['online.nvdental.org', 'ws.nvdental.org']
    start_urls = ['https://online.nvdental.org/']
    buf = []

    def parse(self, response):
        headers = {
            'Accept': 'application/json, text/plain, */*',
            'Content-Type': 'application/json;charset=UTF-8',
            'Origin': 'https://online.nvdental.org',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        }
        for first_letter in string.ascii_lowercase:
            for last_letter in string.ascii_lowercase:
                json_data = {
                    'sortType': 'LicenseNumber',
                    'sortOrder': 'asc',
                    'currentPage': 1,
                    'totalRecords': 100,
                    'pageSize': 100,
                    'maxSize': 5,
                    'Data': {
                        'LastName': last_letter,
                        'FirstName': first_letter,
                        'LicenseNumber': '',
                    },
                }

                yield scrapy.Request(
                    url='https://ws.nvdental.org/api/Individual/IndividualVerifyLicenseDental',
                    method='POST',
                    body=json.dumps(json_data),
                    callback=self.get_data,
                    headers=headers
                )

    def get_data(self, response):
        json_data = json.loads(response.text)
        headers = {
            'Accept': 'application/json, text/plain, */*',
            'Origin': 'https://online.nvdental.org',
            'Referer': 'https://online.nvdental.org/',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        }        
        for t in json_data['PagerVM']['Records']:
            individualId = t['IndividualId']
            if individualId in self.buf:
                continue
            self.buf.append(individualId)
            u = f"https://ws.nvdental.org/api/Individual/VerifyLicenseSearchBYIndividualId?IndividualId={individualId}"
            yield scrapy.Request(u, callback=self.parse_profile, headers=headers, meta={'IndividualId': individualId})

    def parse_profile(self, response):
        json_data = json.loads(response.text)
        for t in json_data['lstVerifyLicenseSearchResponse']:
            if t['IndividualId'] != response.meta['IndividualId']:
                continue
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Nevada State board of Medical Examiners (Dentists)')
            l.add_value('first_name', t['FirstName'])
            l.add_value('last_name', t['LastName'])
            l.add_value('middle_name', t['MiddleName'])
            l.add_value('postname', t['ProfTitleSuffixTypeCode'])
            l.add_value('license_number', t['LicenseNumber'])
            l.add_value('license_status', t['LicenseStatusTypeName'])
            l.add_value('license_type', t['LicenseTypeName'])
            l.add_value('license_issue_date', t['SpecialityLicenseDate'])
            l.add_value('license_expiration_date', t['ExpirationDate'])
            l.add_value('street_address', t['StreetLine1'])
            l.add_value('state', t['StateCode'])
            l.add_value('postal_code', t['Zip'])
            l.add_value('country', 'USA')
            return l.load_item()

