from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class YablySpider(scrapy.Spider):
    name = 'yably'
    allowed_domains = ['yably.ca']

    def start_requests(self):
        for letter in string.ascii_uppercase:
            u = f'https://yably.ca/categories/{letter}'
            yield scrapy.Request(u, callback=self.get_categories)

    def get_categories(self, response):
        for href in response.xpath('//ul[@class="az-category-listing"]//a/@href').extract():
            yield scrapy.Request(response.urljoin(href), callback=self.parse_category)

    def parse_category(self, response):
        for href in response.xpath('//div[@class="views-element-container"]//span/a/@href').extract():
            yield scrapy.Request(response.urljoin(href), callback=self.get_companies)

    def get_companies(self, response):
        for href in response.xpath('//a[@class="company-profile-link"]/@href').extract():
            yield scrapy.Request(response.urljoin(href), callback=self.parse_company)

        next_href = response.xpath('//a[@rel="next"]/@href').extract_first()
        if next_href:
            yield scrapy.Request(response.urljoin(next_href), callback=self.get_companies)

    def parse_company(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Yably')
        l.add_xpath('business_name', '//div[@class="company-info visible-xs"]/div[contains(@class, "company-name")]/span/text()')
        l.add_xpath('industry_type', '//div[@class="company-info visible-xs"]/div[contains(@class, "company-category")]/span/text()')
        contact_info = response.xpath('//div[@class="company-address"]/p/text()').extract()
        if len(contact_info) == 3:
            l.add_value('street_address', contact_info[0])
            l.add_value('state', contact_info[2])
            city_zip = contact_info[1].strip()
            postal_code = ' '.join(city_zip.split(' ')[:2])
            city = city_zip.replace(postal_code, '')
            l.add_value('postal_code', postal_code)
            l.add_value('city', city)

        l.add_xpath('phone', '//i[contains(@class, "fa-phone")]/following-sibling::text()')
        l.add_xpath('website', '//i[contains(@class, "fa-globe")]/following-sibling::a/@href')
        return l.load_item()
