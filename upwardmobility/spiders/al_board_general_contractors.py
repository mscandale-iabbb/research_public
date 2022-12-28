from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class AlBoardGeneralContractorsSpider(scrapy.Spider):
    name = 'al_board_general_contractors'
    custom_settings={'CONCURRENT_REQUESTS': 1}
    allowed_domains = ['genconbd.alabama.gov']
    start_urls = ['https://genconbd.alabama.gov/DATABASE-SQL/roster.aspx']
    headers = {
        'authority': 'genconbd.alabama.gov',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'origin': 'https://genconbd.alabama.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }

    def parse(self, response):
        for href in response.xpath('//table[@id="ContentPlaceHolder1_GridView1"]//a[contains(@href, "detail.aspx")]/@href').extract():
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile)
        
        post_data = get_post_data(response)

        pageNum = 2
        post_data['__EVENTTARGET'] = 'ctl00$ContentPlaceHolder1$GridView1'
        post_data['__EVENTARGUMENT'] = f'Page${pageNum}'
        yield scrapy.FormRequest(
            url='https://genconbd.alabama.gov/DATABASE-SQL/roster.aspx',
            formdata=post_data,
            callback=self.get_data,
            headers=self.headers,
            meta={'pageNum': pageNum}
        )
        

    def get_data(self, response):
        for href in response.xpath('//table[@id="ContentPlaceHolder1_GridView1"]//a[contains(@href, "detail.aspx")]/@href').extract():
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile)

        pageNum = response.meta['pageNum']
        if pageNum < 626:
            post_data = get_post_data(response)
            pageNum += 1
            post_data['__EVENTTARGET'] = 'ctl00$ContentPlaceHolder1$GridView1'
            post_data['__EVENTARGUMENT'] = f'Page${pageNum}'
            yield scrapy.FormRequest(
                url='https://genconbd.alabama.gov/DATABASE-SQL/roster.aspx',
                formdata=post_data,
                callback=self.get_data,
                headers=self.headers,
                meta={'pageNum': pageNum}
            )


    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'AL Board of General Contractors')
        l.add_xpath('license_number', '//span[@id="ContentPlaceHolder1_FormView1_LicenseNoLabel"]/text()')
        l.add_xpath('business_name', '//span[@id="ContentPlaceHolder1_FormView1_NameLabel"]/text()')
        l.add_xpath('street_address', '//span[@id="ContentPlaceHolder1_FormView1_AddressLabel"]/text()')
        l.add_xpath('city', '//span[@id="ContentPlaceHolder1_FormView1_CityLabel"]/text()')
        l.add_xpath('state', '//span[@id="ContentPlaceHolder1_FormView1_StateLabel"]/text()')
        l.add_xpath('postal_code', '//span[@id="ContentPlaceHolder1_FormView1_ZipLabel"]/text()')
        l.add_xpath('phone', '//span[@id="ContentPlaceHolder1_FormView1_PhonenoLabel"]/text()')
        fax = response.xpath('//span[@id="ContentPlaceHolder1_FormView1_FaxLabel"]/text()').extract_first()
        if fax and "(   )" not in fax:
            l.add_value('fax', fax)
        l.add_xpath('license_type', '//span[@id="ContentPlaceHolder1_FormView1_SpecialtyLabel"]/text()')
        l.add_xpath('license_expiration_date', '//span[@id="ContentPlaceHolder1_FormView1_Expr1Label"]/text()')
        return l.load_item()
