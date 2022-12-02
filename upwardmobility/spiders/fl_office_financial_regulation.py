from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *

class FlOfficeFinancialRegulationSpider(scrapy.Spider):
    name = 'fl_office_financial_regulation'

    headers = {
        'Accept': '*/*',
        'Connection': 'keep-alive',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Origin': 'https://licenseesearch.fldfs.com',
        'Referer': 'https://licenseesearch.fldfs.com/',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
        'X-Requested-With': 'XMLHttpRequest',
    }
    
    def start_requests(self):
        data = {
            'IndividualFNameFilter': '',
            'IndividualLNameFilter': '',
            'IndividualMNameFilter': '',
            'EmailAddressBeginContainFilter': '1',
            'EmailFilter': '',
            'FirmNameBeginContainFilter': '1',
            'FirmNameFilter': '',
            'ResidentStatusFilter': '',
            'FLLicenseNoFilter': '',
            'NPNNoFilter': '',
            'LicenseStatusFilter': '1',
            'LicenseCategoryFilter': '',
            'LicenseIssueDateFromFilter': '',
            'LicenseIssueDateToFilter': '',
            'OnlyLicWithNoQuApptFilter': 'false',
            'BusinessStateFilter': 'FL',
            'BusinessCityFilter': '',
            'BusinessCountyFilter': '',
            'BusinessZipFilter': '',
            'CEDueDtFromFilter': '',
            'CEDueDtToFilter': '',
            'CEHrsNotMetFilter': 'false',
            'AppointingEntityTYCLFilter': '',
            'AppointingEntityStatusFilter': '',
            'AppointingEntityStatusDateFromFilter': '',
            'AppointingEntityStatusDateToFilter': '',
            'LicenseeSearchInfo.PagingInfo.SortBy': 'Name',
            'LicenseeSearchInfo.PagingInfo.SortDesc': 'False',
            'LicenseeSearchInfo.PagingInfo.CurrentPage': '1',
            'AppointingEntityIdFilter': '',
            'AppointingEntityDisplayName': '',
            'TabLLValue': '0',
            'TabCEValue': '',
            'TabAppValue': '',
            'hdnLApptEntitySearchListUrl': '/Home/GetAppointingEntityListForSearch',
            'hdnLicenseeSearchListUrl': '/Home/GetLicenseeSearchListPartialView',
        }

        url = 'https://licenseesearch.fldfs.com/'
        yield scrapy.FormRequest(url=url, formdata=data, headers=self.headers, dont_filter=True)
    
    def parse(self, response):
        totalpages = response.xpath('//li/a[contains(@href,"PageResult")]/text()').extract()[-1]
        for page in range(1, 3):
            data = {
                'SortBy': 'Name',
                'SortDesc': 'false',
                'CurrentPage': str(page),
            }
            url = 'https://licenseesearch.fldfs.com/Home/GetLicenseeSearchListPartialView'
            yield scrapy.FormRequest(url=url, formdata=data, callback=self.parse_list_page, headers=self.headers, dont_filter=True)
    
    def parse_list_page(self, response):
        links = response.xpath('//div//td/div')
        for link in links:
            url = response.urljoin(link.xpath('./a/@href').extract_first())
            yield scrapy.Request(url=url, callback=self.parse_company)

    def parse_company(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Florida office of financial regulation')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//label[@for="fullName"]/following-sibling::div/text()')
        l.add_xpath('street_address', '//label[contains(text(), "Business Address")]/following-sibling::div/text()[1]')
        city_state = response.xpath('//label[contains(text(), "Business Address")]/following-sibling::div/text()[2]').extract_first('')
        city = city_state.split(',  ')[0]
        l.add_value('city', city)
        state = city_state.split(',  ')[1].split(' ')[0] 
        l.add_value('state', state)
        postal_code = city_state.split(',  ')[1].split(' ')[1]
        l.add_value('postal_code', postal_code)
        l.add_value('country', 'USA')
        l.add_xpath('email', '//label[contains(text(), "Email")]/following-sibling::div/text()')
        l.add_xpath('phone', '//label[@for="phone"]/following-sibling::div/text()')
        l.add_xpath('license_number', '//label[@for="licenseNumber"]/following-sibling::div/text()')
        l.add_xpath('license_type', '//div[contains(text(),"Valid Licenses")]/following-sibling::div/table[contains(@class,"table-striped")]//td[1]/text()')
        l.add_xpath('license_issue_date', '//div[contains(text(),"Valid Licenses")]/following-sibling::div/table[contains(@class,"table-striped")]//td[2]/text()')
        l.add_xpath('license_status', '//div[contains(text(),"Valid Licenses")]/following-sibling::div/table[contains(@class,"table-striped")]//td[3]/text()')
        return l.load_item()

# rm licenseesearch.csv; scrapy crawl licenseesearch