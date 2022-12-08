from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcDivisionChildDevelopmentEarlyEducationSpider(scrapy.Spider):
    name = 'nc_division_child_development_early_education'
    allowed_domains = ['ncchildcare.ncdhhs.gov']
    start_urls = ['https://ncchildcare.ncdhhs.gov/childcaresearch']
    headers = {
        'Accept': '*/*',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Origin': 'https://ncchildcare.ncdhhs.gov',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        'X-MicrosoftAjax': 'Delta=true',
        'X-Requested-With': 'XMLHttpRequest',
    }
    buf = []

    def parse(self, response):
        post_data = self.get_post_data(response)
        post_data['ScriptManager'] = 'dnn$ctr1464$View$updtSearchForm|dnn$ctr1464$View$btnSearch'
        post_data['__EVENTTARGET'] = 'dnn$ctr1464$View$btnSearch'
        post_data['__ASYNCPOST'] = 'true'
        for comb in string.ascii_lowercase:
            post_data['dnn$ctr1464$View$txtFacilityName'] = comb
            yield scrapy.FormRequest(
                url='https://ncchildcare.ncdhhs.gov/childcaresearch',
                formdata=post_data,
                callback=self.get_data,
                headers=self.headers
            )

    def get_data(self, response):
        for tag in response.xpath('//div[@class="table"]/table/tr'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Division of Child Development and Early Education')
            license_number = tag.xpath('./td[1]/text()').extract_first().strip()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            l.add_value('license_number', license_number)
            l.add_value('business_name', tag.xpath('./td[2]/text()').extract_first())
            l.add_value('industry_type', tag.xpath('./td[4]/text()').extract_first())
            l.add_value('license_type', tag.xpath('./td[5]/text()').extract_first())
            addresses = tag.xpath('./td[3]/text()').extract()
            if len(addresses) == 3:
                l.add_value('street_address', addresses[0])
                l.add_value('city', addresses[1].split(',')[0])
                l.add_value('state', addresses[1].split(',')[-1][:2])
                l.add_value('postal_code', addresses[1].split(',')[-1][2:])
                l.add_value('phone', addresses[2].strip())
            yield l.load_item()

    def get_post_data(self, response):
        post_data = {}
        for tag in response.xpath('//input[@type="hidden"]'):
            label = tag.xpath('@name').extract_first()
            value = tag.xpath('@value').extract_first()
            post_data[label] = value if value else ''
        return post_data