from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class LicensedbuilderregistrySpider(scrapy.Spider):
    name = 'licensedbuilderregistry'
    allowed_domains = ['licensedbuilderregistry.bchousing.org']
    start_urls = ['https://licensedbuilderregistry.bchousing.org/']
    buf = []
    def parse(self, response):
        headers = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Content-Type': 'application/x-www-form-urlencoded',
            'Origin': 'https://licensedbuilderregistry.bchousing.org',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        }
        __RequestVerificationToken = response.xpath('//input[@name="__RequestVerificationToken"]/@value').extract_first()
        for PICName in string.ascii_lowercase:
            data = {
                '__RequestVerificationToken': __RequestVerificationToken,
                'CommandName': 'SEARCH_CLICKED',
                'CommandArgument': '',
                'ShowSearchMessage': 'False',
                'LicenceNumber: CompanyName': '', 
                'PICName': PICName,
                'SelectedLicenceType': 'Builder',
                'SelectedLocation': 'Any Location',
                'CityName': '',
                'SelectedArea': 'Any',
                'SelectedLicenceStatus': 'In Good Standing*'
            }
            yield scrapy.FormRequest(
                url='https://licensedbuilderregistry.bchousing.org/LicenceRegistry/LicenceSearch',
                formdata=data,
                headers=headers,
                callback=self.get_data,
                dont_filter=True
            )

    def get_data(self, response):
        __RequestVerificationToken = response.xpath('//input[@name="__RequestVerificationToken"]/@value').extract_first()
        cookies = {
            '__RequestVerificationToken': __RequestVerificationToken,
        }
        headers = {
            'Accept': '*/*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin': 'https://licensedbuilderregistry.bchousing.org',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
            'X-Requested-With': 'XMLHttpRequest',
        }

        for iLicenceNumber in response.xpath('//div[contains(@class, "list-group")]/a/@data-cmd-arg').extract():
            if iLicenceNumber in self.buf:
                continue
            self.buf.append(iLicenceNumber)
            data = {
                'iLicenceNumber': iLicenceNumber,
                'sPartialPrefix': 'LicenceDetailsViewModel'
            }
            yield scrapy.FormRequest(
                url='https://licensedbuilderregistry.bchousing.org/Shared/LicenceSelected',
                formdata=data,
                headers=headers,
                cookies=cookies,
                callback=self.parse_profile,
            )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'BC Housing - Licence Registry')
        l.add_xpath('business_name', '//strong[contains(@class, "lims-text-em")]/text()')
        l.add_xpath('license_number', '//label[contains(., "Licence #:")]/following-sibling::div[1]/text()')
        l.add_xpath('license_type', '//label[contains(., "Licence Type:")]/following-sibling::div[1]/text()')
        l.add_xpath('license_status', '//label[contains(., "Status:")]/following-sibling::div[1]/span/text()')
        l.add_xpath('license_expiration_date', '//label[contains(., "Expiry Date:")]/following-sibling::div[1]/span/text()')
        contact_info = response.xpath('//label[contains(., "Contact Information:")]/following-sibling::div[1]/text()').extract()
        if len(contact_info) == 2:
            l.add_value('street_address', contact_info[0])
            l.add_value('city', contact_info[1].split(',')[0])
            state_zip = contact_info[1].split(',')[1].strip()
            postal_code = ' '.join(state_zip.split(' ')[-2:])
            state = state_zip.replace(postal_code, '').strip()
            l.add_value('state', state)
            l.add_value('postal_code', postal_code)
        elif len(contact_info) == 3:
            l.add_value('street_address', contact_info[0])
            l.add_value('city', contact_info[2].split(',')[0])
            state_zip = contact_info[2].split(',')[1].strip()
            postal_code = ' '.join(state_zip.split(' ')[-2:])
            state = state_zip.replace(postal_code, '').strip()
            l.add_value('state', state)
            l.add_value('postal_code', postal_code)

        l.add_xpath('website', '//label[contains(., "Contact Information:")]/following-sibling::div[1]//a/text()')
        phone = response.xpath('//li[contains(., "Public Contact Number")]/text()').extract_first()
        if phone:
            l.add_value('phone', phone.split('Public Contact Number')[0])
        fax = response.xpath('//li[contains(., "Public Fax Number")]/text()').extract_first()
        if fax:
            l.add_value('fax', fax.split('Public Fax Number')[0])
        return l.load_item()