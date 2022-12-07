from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcDeptOfAgricultureSpider(scrapy.Spider):
    name = 'nc_dept_of_agriculture'
    allowed_domains = ['apps.ncagr.gov']
    start_urls = ['https://apps.ncagr.gov/AgRSysPortal/publiclicensesearch/index']
    headers = {
        'Accept': '*/*',
        'Content-Type': 'application/json',
        'Origin': 'https://apps.ncagr.gov',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        'X-Requested-With': 'XMLHttpRequest',
    }    
    json_data = {
        'Owner': '',
        'LicenseType': '',
        'Licensee': '',
        'LicenseNumber': '',
        'Address': '',
        'County': 'All',
        'ExactText': False,
        'Statuses': None,
        'URN': '',
        'Initials': '',
        'BusinessName': '',
        'RecertDateFrom': '',
        'RecertDateTo': '',
        'State': '..',
    }

    def parse(self, response):
        authorization = response.xpath('//input[@id="AuthToken"]/@value').extract_first()
        self.headers['Authorization'] = authorization
        self.json_data['PageNumber'] = 1
        yield scrapy.Request(
            url='https://apps.ncagr.gov/AgRSysAPI/api/publiclicensesearch/publicsearchresults/searchcriteria',
            method='POST',
            body=json.dumps(self.json_data),
            callback=self.get_data,
            headers=self.headers,
            meta={'pageNum': 1}
        )

    def get_data(self, response):
        json_data = json.loads(response.text)
        for p in json_data['Data']:
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Dept of Agriculture')
            l.add_value('email', p['Email'])
            l.add_value('business_name', p['Name'].strip())
            l.add_value('industry_type', p['Owner'])
            l.add_value('license_number', str(p['LicenseNumber']))
            l.add_value('license_issue_date', p['IssueDate'])
            l.add_value('license_expiration_date', p['Expire'])
            l.add_value('license_type', p['LicenseType'])
            l.add_value('license_status', p['Status'])
            if p['MailingAddress']:
                l.add_value('street_address', p['MailingAddress']['Address2'])
                l.add_value('city', p['MailingAddress']['City'])
                l.add_value('state', p['MailingAddress']['State'])
                l.add_value('postal_code', p['MailingAddress']['Zip'])
                l.add_value('country', 'USA')
            yield l.load_item()

        pageNum = response.meta['pageNum']
        totalPages = json_data['TotalPages']
        if pageNum < totalPages:
            self.json_data['PageNumber'] = pageNum + 1
            yield scrapy.Request(
                url='https://apps.ncagr.gov/AgRSysAPI/api/publiclicensesearch/publicsearchresults/searchcriteria',
                method='POST',
                body=json.dumps(self.json_data),
                callback=self.get_data,
                headers=self.headers,
                meta={'pageNum': pageNum + 1}
            )
