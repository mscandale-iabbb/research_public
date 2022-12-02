from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *

class NpinoSpider(scrapy.Spider):
    name = 'npino'
    allowed_domains = ['npino.com']
    start_urls = ['https://npino.com/home-health-care/?page=1']

    def parse(self, response):
        companies = response.xpath('//div[contains(@class, "panel-default")]//div[@class="inlinediv"]/strong/a/@href').extract()
        for href in companies:
            yield scrapy.Request(response.urljoin(href), callback=self.parse_company)
        if len(companies) == 20:
            cur_page = response.url.split('page=')[-1]
            next_link = response.url.replace(f'page={cur_page}', f'page={int(cur_page)+1}')
            yield scrapy.Request(next_link)

    def parse_company(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Home Health')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//td[contains(., "Provider Name")]/following-sibling::td[1]//text()')
        l.add_xpath('street_address', '//u[contains(.,"Provider Business Mailing Address Details:")]/following-sibling::table[1]//td[contains(., "Address")]/following-sibling::td[1]//text()')
        l.add_xpath('city', '//u[contains(.,"Provider Business Mailing Address Details:")]/following-sibling::table[1]//td[contains(., "City")]/following-sibling::td[1]//text()')
        l.add_xpath('state', '//u[contains(.,"Provider Business Mailing Address Details:")]/following-sibling::table[1]//td[contains(., "State")]/following-sibling::td[1]//text()')
        l.add_xpath('postal_code', '//u[contains(.,"Provider Business Mailing Address Details:")]/following-sibling::table[1]//td[contains(., "Zip")]/following-sibling::td[1]//text()')
        l.add_value('country', 'USA')

        l.add_xpath('phone', '//u[contains(.,"Provider Business Mailing Address Details:")]/following-sibling::table[1]//td[contains(., "Phone Number")]/following-sibling::td[1]//text()')
        l.add_xpath('fax', '//u[contains(.,"Provider Business Mailing Address Details:")]/following-sibling::table[1]//td[contains(., "Fax Number")]/following-sibling::td[1]//text()')
        l.add_xpath('secondary_business_name', '//td[contains(., "Other Name")]/following-sibling::td[1]//text()')
        l.add_xpath('title', '//td[contains(., "Authorized Official Title")]/following-sibling::td[1]//text()')
        l.add_xpath('license_number', '//td[contains(., "Licence No.")]/following-sibling::td[1]//text()')
        l.add_xpath('license_type', '//u[contains(.,"Taxonomy Details:")]/following-sibling::table[1]//td[contains(., "Type")]/following-sibling::td[1]//text()')

        full_name = ''.join(response.xpath('//td[contains(., "Authorized Official Name")]/following-sibling::td[1]//text()').extract()).strip()
        prename, postname, first_name, last_name, middle_name = parse_name(full_name)
        l.add_value('prename', prename)
        l.add_value('postname', postname)
        l.add_value('first_name', first_name)
        l.add_value('last_name', last_name)
        l.add_value('middle_name', middle_name)

        return l.load_item()