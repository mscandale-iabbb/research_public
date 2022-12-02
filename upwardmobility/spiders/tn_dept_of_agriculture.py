from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class AgricultureTnSpider(scrapy.Spider):
    name = 'tn_dept_of_agriculture'
    allowed_domains = ['agriculture.tn.gov']
    start_urls = ['https://agriculture.tn.gov/listcharter.asp']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://agriculture.tn.gov',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    data = {
        'stype': 'SearchAll',
        'ACTION': 'QUERY',
        'SUBMIT': 'Search',
    }
    api_url = 'https://agriculture.tn.gov/listcharter.asp'
    buf = []

    def parse(self, response):
        for query in string.ascii_lowercase:
            self.data['QUERY'] = query
            yield scrapy.FormRequest(
                url=self.api_url,
                formdata=self.data,
                callback=self.get_data,
                headers=self.headers
            )

    def get_data(self, response):
        for tag_idx, tag in enumerate(response.xpath('//div/table/tr')):
            if tag_idx == 0:
                continue
            license_number = ''.join(tag.xpath('./td[1]//text()').extract()).strip()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'TN Dept of Agriculture (Including pest)')
            l.add_value('license_number', license_number)
            l.add_value('business_name', ''.join(tag.xpath('./td[2]//text()').extract()).strip())
            l.add_value('city', ''.join(tag.xpath('./td[3]//text()').extract()).strip())
            l.add_value('state', ''.join(tag.xpath('./td[4]//text()').extract()).strip())
            l.add_value('postal_code', ''.join(tag.xpath('./td[5]//text()').extract()).strip())
            l.add_value('country', 'USA')
            l.add_value('industry_type', ''.join(tag.xpath('./td[6]//text()').extract()).strip())
            yield l.load_item()