from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class UtBarAssociationSpider(scrapy.Spider):
    name = 'ut_bar_association'
    allowed_domains = ['services.utahbar.org']
    start_urls = ['https://services.utahbar.org/Member-Directory']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    api_url = 'https://services.utahbar.org/cvweb/cgi-bin/utilities.dll/CustomList?SORT=m.LASTNAME,%20m.FIRSTNAME&FIRSTFILTER=~&LASTFILTER=~&RANGE={}&SQLNAME=UTMEMDIR_NEW&SHOWSQL_off=N&WHP=Customer_Header.htm&WBP=Customer_List.htm'

    def parse(self, response):
        __RequestVerificationToken = response.xpath('//input[@name="__RequestVerificationToken"]/@value').extract_first()
        page_range = 1
        u = self.api_url.format('1/25')
        yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'page_range': 1})

    def get_data(self, response):
        bars = response.xpath('//table/tbody//a/@href').extract()
        for href in bars:
            bar_id = href.split('=')[-1]
            bar_url = f"https://services.utahbar.org/cvweb/cgi-bin/memberdll.dll/Info?&customercd={bar_id}&wrp=Customer_Profile.htm"
            yield scrapy.Request(bar_url, callback=self.parse_bar, headers=self.headers, meta={'bar_id': bar_id})

        if len(bars) == 25:
            page_range = response.meta['page_range']
            u = self.api_url.format(f'{25*page_range + 1}/25')
            yield scrapy.Request(u, callback=self.get_data, headers=self.headers, meta={'page_range': page_range+1})


    def parse_bar(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'UT Bar Association')
        l.add_value('company_url', f"https://services.utahbar.org/Member-Directory/Profile?customercd={response.meta['bar_id']}")
        l.add_xpath('prename', '//dt[contains(., "Prefix")]/following-sibling::dd[1]/text()')
        l.add_xpath('first_name', '//dt[contains(., "First Name")]/following-sibling::dd[1]/text()')
        l.add_xpath('middle_name', '//dt[contains(., "Middle Name")]/following-sibling::dd[1]/text()')
        l.add_xpath('last_name', '//dt[contains(., "Last Name")]/following-sibling::dd[1]/text()')
        l.add_xpath('license_number', '//dt[contains(., "Bar Number")]/following-sibling::dd[1]/text()')
        l.add_xpath('license_type', '//dt[contains(., "Type")]/following-sibling::dd[1]/text()')
        l.add_xpath('license_status', '//dt[contains(., "Status")]/following-sibling::dd[1]/text()')
        l.add_xpath('license_issue_date', '//dt[contains(., "Date Admitted")]/following-sibling::dd[1]/text()')
        u = f"https://services.utahbar.org/cvweb/cgi-bin/memberdll.dll/Info?&customercd={response.meta['bar_id']}&wmt=none&wrp=CustomerAddressDisp.htm&CustomerAddressList=CustomerAddressListE.htm"
        yield scrapy.Request(u, callback=self.parse_bar_2, headers=self.headers, meta={'item':l.load_item()})

    def parse_bar_2(self, response):
        l = CompanyLoader(response.meta.get('item', UpwardMobilityItem()), response=response)
        l.add_xpath('phone', '//dt[contains(., "Phone")]/following-sibling::dd[1]/text()')
        l.add_xpath('fax', '//dt[contains(., "Fax")]/following-sibling::dd[1]/text()')
        l.add_value('email', ''.join(response.xpath('//dt[contains(., "Email")]/following-sibling::dd[1]//text()').extract()).strip())
        l.add_xpath('business_name', '//dt[contains(., "Organization")]/following-sibling::dd[1]/text()')
        l.add_xpath('street_address', '//dt[contains(., "Mailing Address")]/following-sibling::dd[1]/text()')
        l.add_xpath('city', '//dt[contains(., "City")]/following-sibling::dd[1]/text()')
        l.add_xpath('state', '//dt[contains(., "State")]/following-sibling::dd[1]/text()')
        l.add_xpath('postal_code', '//dt[contains(., "Zip")]/following-sibling::dd[1]/text()')
        l.add_value('country', 'USA')
        return l.load_item()

