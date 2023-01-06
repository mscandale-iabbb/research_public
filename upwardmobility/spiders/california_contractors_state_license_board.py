from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class CaliforniaContractorsStateLicenseBoardSpider(scrapy.Spider):
    name = 'california_contractors_state_license_board'
    # custom_settings = {'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['cslb.ca.gov']
    start_urls = ['https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/CheckLicense.aspx']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://www.cslb.ca.gov',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        post_data = get_post_data(response)
        post_data['ctl00$MainContent$LicNo'] = ''
        post_data['ctl00$MainContent$LName'] = ''
        post_data['ctl00$MainContent$FName'] = ''
        post_data['ctl00$MainContent$Contractor_Business_Name_Button'] = ''
        post_data['ctl00$MainContent$HIS_LicNo'] = ''
        post_data['ctl00$MainContent$HIS_LName'] = ''
        post_data['ctl00$MainContent$HIS_FName'] = ''
        for lname in string.ascii_lowercase:
            post_data['ctl00$MainContent$NextName'] = lname
            yield scrapy.FormRequest(
                url='https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/CheckLicense.aspx',
                formdata=post_data,
                headers=self.headers,
                callback=self.get_data,
                meta={'lname': lname},
                dont_filter=True
            )

    def get_data(self, response):
        profiles = response.xpath('//table[@id="MainContent_dlMain"]/tr')
        for tag in profiles:
            p_href = tag.xpath('.//a[contains(@id, "MainContent_dlMain_hlLicense_")]/@href').extract_first()
            if p_href in self.buf:
               continue
            self.buf.append(p_href)
            license_number = tag.xpath('.//a[contains(@id, "MainContent_dlMain_hlLicense_")]/text()').extract_first()
            license_status = tag.xpath('.//span[contains(@id, "MainContent_dlMain_lblLicenseStatus_")]/text()').extract_first()
            yield scrapy.Request(
                response.urljoin(p_href),
                callback=self.parse_profile,
                meta={
                    'license_number': license_number,
                    'license_status': license_status
                }
            )

        if len(profiles) > 0:
            post_data = get_post_data(response)
            lname = response.meta['lname']
            post_data['ctl00$MainContent$NextLicenses'] = 'Next 50 Business Names >>'
            yield scrapy.FormRequest(
                url=f'https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/NameSearch.aspx?NextName={lname}&NextLicNum=',
                formdata=post_data,
                headers=self.headers,
                callback=self.get_data,
                meta={'lname': lname},
                dont_filter=True
            )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'California Contractors State License Board')
        info_txt = response.xpath('//td[@id="MainContent_BusInfo"]/text()').extract()
        if len(info_txt) == 4:
            l.add_value('business_name', info_txt[0])
            l.add_value('street_address', info_txt[1])
            l.add_value('city', info_txt[2].split(',')[0])
            l.add_value('state', info_txt[2].split(',')[1].strip().split(' ')[0])
            l.add_value('postal_code', info_txt[2].split(',')[1].strip().split(' ')[1])
            try:
                l.add_value('phone', info_txt[3].split(':')[1])
            except:
                pass
        elif len(info_txt) == 5:
            l.add_value('business_name', info_txt[0])
            l.add_value('street_address', info_txt[1])
            l.add_value('city', info_txt[3].split(',')[0])
            l.add_value('state', info_txt[3].split(',')[1].strip().split(' ')[0])
            l.add_value('postal_code', info_txt[3].split(',')[1].strip().split(' ')[1])
            try:
                l.add_value('phone', info_txt[4].split(':')[1])
            except:
                pass
        elif len(info_txt) == 3:
            l.add_value('business_name', info_txt[0])
            l.add_value('street_address', info_txt[1])
            l.add_value('city', info_txt[2].split(',')[0])
            l.add_value('state', info_txt[2].split(',')[1].strip().split(' ')[0])
            l.add_value('postal_code', info_txt[2].split(',')[1].strip().split(' ')[1])

        l.add_value('license_number', response.meta['license_number'])
        l.add_value('license_status', response.meta['license_status'])
        l.add_xpath('license_type', '//td[@id="MainContent_Entity"]/text()')
        l.add_xpath('license_issue_date', '//td[@id="MainContent_IssDt"]/text()')
        l.add_xpath('license_expiration_date', '//td[@id="MainContent_ExpDt"]/text()')
        return l.load_item()