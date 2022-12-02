from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NvStateContractorsBoardSpider(scrapy.Spider):
    name = 'nv_state_contractors_board'
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['app.nvcontractorsboard.com']
    start_urls = ['https://app.nvcontractorsboard.com/Clients/NVSCB/Public/ContractorLicenseSearch/ContractorLicenseSearch.aspx']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://app.nvcontractorsboard.com',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    api_url = 'https://app.nvcontractorsboard.com/Clients/NVSCB/Public/ContractorLicenseSearch/ContractorLicenseSearch.aspx'
    buf = []

    def parse(self, response):
        data = self.get_post_data(response)
        data['__EVENTTARGET'] = 'ctl00$ContentPlaceHolder1$DdlSearchBy'
        data['ctl00$ContentPlaceHolder1$DdlSearchBy'] = '2'
        data['ctl00$ContentPlaceHolder1$inputLicenseNumber'] = ''
        yield scrapy.FormRequest(
            url=self.api_url,
            formdata=data,
            callback=self.to_name_search,
            headers=self.headers
        )

    def to_name_search(self, response):
        data = self.get_post_data(response)
        data['ctl00$ContentPlaceHolder1$DdlSearchBy'] = '2'
        data['ctl00$ContentPlaceHolder1$inputBusID'] = ''
        data['ctl00$ContentPlaceHolder1$btnSearch'] = 'Submit'
        for com in string.ascii_lowercase:
            data['ctl00$ContentPlaceHolder1$inputCompany'] = com
            yield scrapy.FormRequest(
                url=self.api_url,
                formdata=data,
                callback=self.get_data,
                headers=self.headers,
                dont_filter=True
            )

    def get_data(self, response):
        data = self.get_post_data(response)
        for tag_idx, tag in enumerate(response.xpath('//table[@id="ContentPlaceHolder1_dtgResults"]//tr')):
            if tag_idx == 0:
                continue
            license_number = tag.xpath('./td[4]/text()').extract_first()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            event_target = tag.xpath('.//a/@href').extract_first()
            event_target = event_target.split("doPostBack('")[-1].split("',")[0]
            data['__EVENTTARGET'] = event_target
            yield scrapy.FormRequest(
                url='https://app.nvcontractorsboard.com/Clients/NVSCB/Public/ContractorLicenseSearch/LicenseSearchResults.aspx',
                formdata=data,
                callback=self.parse_profile,
                headers=self.headers,
                dont_filter=True
            )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NV State Contractors Board')
        l.add_xpath('business_name', '//span[@id="ContentPlaceHolder1_lblLegalName1"]/text()')
        l.add_xpath('license_number', '//span[@id="ContentPlaceHolder1_lblLicNum"]/text()')
        l.add_xpath('street_address', '//span[@id="ContentPlaceHolder1_lblMailStrt1"]/text()')
        l.add_xpath('city', '//span[@id="ContentPlaceHolder1_lblMailCityOne"]/text()')
        l.add_xpath('state', '//span[@id="ContentPlaceHolder1_lblMailStateOne"]/text()')
        l.add_xpath('postal_code', '//span[@id="ContentPlaceHolder1_lblMailZipOne"]/text()')
        l.add_value('country', 'USA')
        l.add_xpath('license_status', '//span[@id="ContentPlaceHolder1_lblStatusOne"]/text()')
        l.add_xpath('license_type', '//span[@id="ContentPlaceHolder1_lblClassifications"]/text()')
        l.add_xpath('license_issue_date', '//span[@id="ContentPlaceHolder1_lblStatusDateOne"]/text()')
        l.add_xpath('license_expiration_date', '//span[@id="ContentPlaceHolder1_lblExpireDate"]/text()')
        l.add_xpath('industry_type', '//span[@id="ContentPlaceHolder1_lblBusTypeOne"]/text()')
        l.add_xpath('secondary_business_name', '//span[@id="ContentPlaceHolder1_lblDBAName1"]/text()')
        l.add_xpath('phone', '//span[@id="ContentPlaceHolder1_lblPhoneOne"]/text()')
        l.add_xpath('title', '//span[@id="ContentPlaceHolder1_dtgPrincipalBroker_lblPrincipalRelation_0"]/text()')
        full_name = response.xpath('//span[@id="ContentPlaceHolder1_dtgPrincipalBroker_lblPrincipalName_0"]/text()').extract_first()
        if full_name:
            prename, postname, first_name, last_name, middle_name = parse_name(full_name)
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
        return l.load_item()

    def get_post_data(self, response):
        post_data = {}
        for tag in response.xpath('//input[@type="hidden"]'):
            post_data[tag.xpath('@name').extract_first()] = tag.xpath('@value').extract_first() if tag.xpath('@value').extract_first() else ''
        return post_data