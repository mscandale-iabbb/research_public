from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PaStateBarSpider(scrapy.Spider):
    name = 'pa_state_bar'
    allowed_domains = ['pacle.org']
    start_urls = ['https://www.pacle.org/courses/search?operation=2&county=0']

    def parse(self, response):
        for url in response.xpath('//div[@id="coursesearchresults"]//table[contains(@class, "table-striped")]/tbody/tr//a[contains(@class, "capdocumentdownload")]/@href').extract():
            yield scrapy.Request(url, callback=self.parse_profile)

        next_link = response.xpath('//a[contains(., "next page")]/@href').extract_first()
        if next_link:
            yield scrapy.Request(next_link)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'PA State Bar')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//td[contains(., "Provider:")]/following-sibling::td[1]/text()')
        l.add_xpath('industry_type', '//td[contains(., "Classification:")]/following-sibling::td[1]/text()')
        l.add_xpath('phone', '//td[contains(., "Phone:")]/following-sibling::td[1]/text()')
        l.add_xpath('email', '//td[contains(., "Email:")]/following-sibling::td[1]/a/text()')
        l.add_xpath('website', '//td[contains(., "web:")]/following-sibling::td[1]/a/@href')
        address = response.xpath('//td[contains(., "Address:")]/following-sibling::td[1]//text()').extract()
        l.add_value('street_address', address[0].strip())
        l.add_value('city', address[1].split(',')[0].strip())
        l.add_value('state', address[1].split(',')[-1].strip())
        l.add_value('country', 'USA')
        return l.load_item()
