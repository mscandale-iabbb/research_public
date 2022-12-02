from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class InstsearchSpider(scrapy.Spider):
    name = 'instsearch'
    allowed_domains = ['instsearch.pa.gov']
    start_urls = ['https://www.instsearch.pa.gov/']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Accept-Language': 'en-US,en;q=0.9',
        'Origin': 'https://www.instsearch.pa.gov',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    total_pages = None
    def parse(self, response):
        post_data = self.get_post_data(response)
        post_data['__EVENTTARGET'] = 'GridView2'
        post_data['__EVENTARGUMENT'] = 'Page$1'
        yield scrapy.FormRequest(
            url='https://www.instsearch.pa.gov/InstListResults.aspx',
            formdata=post_data,
            callback=self.get_institutions,
            headers=self.headers,
            meta={'pageNum': 1}
        )

    def get_institutions(self, response):
        if not self.total_pages:
            total_text = response.xpath('//span[@id="Label1"]/text()').extract_first().split('results.')[0].split('returned')[-1].strip()
            self.total_pages = int(total_text)/30 + 1

        tags = response.xpath('//table[@id="GridView2"]/tr[./td][not(@align)]')
        for tag in tags:
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA Department of Banking')
            l.add_value('business_name', tag.xpath('./td[1]/text()').extract_first())
            l.add_value('license_type', tag.xpath('./td[2]/text()').extract_first())
            l.add_value('license_number', tag.xpath('./td[3]/text()').extract_first())
            l.add_value('street_address', tag.xpath('./td[6]/text()').extract_first())
            l.add_value('city', tag.xpath('./td[7]/text()').extract_first())
            l.add_value('state', tag.xpath('./td[8]/text()').extract_first())
            l.add_value('postal_code', tag.xpath('./td[9]/text()').extract_first())
            l.add_value('country', 'USA')
            l.add_value('phone', tag.xpath('./td[11]/text()').extract_first())
            l.add_value('industry_type', tag.xpath('./td[12]/text()').extract_first())
            yield l.load_item()

        pageNum = response.meta['pageNum']
        if pageNum < self.total_pages:
        # if len(tags) == 30:
            post_data = self.get_post_data(response)
            post_data['__EVENTTARGET'] = 'GridView2'
            post_data['__EVENTARGUMENT'] = f'Page${pageNum+1}'
            yield scrapy.FormRequest(
                url='https://www.instsearch.pa.gov/InstListResults.aspx',
                formdata=post_data,
                callback=self.get_institutions,
                headers=self.headers,
                meta={'pageNum': pageNum+1}
            )


    def get_post_data(self, response):
        post_data = {}
        for tag in response.xpath('//input[@type="hidden"]'):
            post_data[tag.xpath('@name').extract_first()] = tag.xpath('@value').extract_first() if tag.xpath('@value').extract_first() else ''
        return post_data