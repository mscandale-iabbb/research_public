from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PAInsuranceCompaniesSpider(scrapy.Spider):
    name = 'pa_insurance_companies'
    allowed_domains = ['apps02.ins.pa.gov']
    data = {
        'txAgyLicType': 'ALL',
        'txQual': 'ALL',
        'txCity': '',
        'txState': 'PA',
        'btnSubmit': 'Search for Licenses',
    }
    headers = {
        'authority': 'apps02.ins.pa.gov',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'accept-language': 'en-US,en;q=0.9',
        'origin': 'https://apps02.ins.pa.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    txLicenseBuf = []
    def start_requests(self):
        for txName in string.ascii_lowercase:
            self.data['txName'] = txName
            yield scrapy.FormRequest(
                url='https://apps02.ins.pa.gov/producer/alist2.asp',
                formdata=self.data,
                callback=self.get_licenses,
                headers=self.headers,
            )

    def get_licenses(self, response):
        for txLicense in response.xpath('//p[contains(., "Number of Records:")]/following-sibling::table//input[@name="txLicense"]/@value').extract():
            if txLicense in self.txLicenseBuf:
                continue
            self.txLicenseBuf.append(txLicense)
            data = {
                'txLicense': txLicense,
                f'btn{txLicense}': txLicense,
            }
            yield scrapy.FormRequest(
                url='https://apps02.ins.pa.gov/producer/alist3.asp',
                formdata=data,
                callback=self.parse_license,
                headers=self.headers,
                meta={"txLicense": txLicense}
            )

    def parse_license(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'PA Insurance Companies')
        l.add_value('company_url', response.meta['txLicense'])
        l.add_value('license_number' ,response.xpath('//td[contains(., "License Number:")][@bgcolor]/following-sibling::td[1]/text()').extract_first())
        l.add_value('business_name',response.xpath('//td[contains(., "Business Name:")][@bgcolor]/following-sibling::td[1]/text()').extract_first())
        l.add_value('city', response.xpath('//td[contains(., "City:")][@bgcolor]/following-sibling::td[1]/text()').extract_first())
        l.add_value('state', response.xpath('//td[contains(., "State:")][@bgcolor]/following-sibling::td[1]/text()').extract_first())
        l.add_value('country', 'USA')
        l.add_value('license_type', response.xpath('//td[contains(., "License Type:")][@bgcolor]/following-sibling::td[1]/text()').extract_first())
        l.add_value('license_expiration_date', response.xpath('//td[contains(., "Expiration Date of License:")][@bgcolor]/following-sibling::td[1]/text()').extract_first())
        l.add_value('industry_type', response.xpath('//td[contains(., "Lines of Authority:")][@bgcolor]/following-sibling::td[1]/text()').extract_first())
        return l.load_item()
        
        