from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PADeptOfHealthSpider(scrapy.Spider):
    name = 'pa_dept_health'
    allowed_domains = ['sais.health.pa.gov']
    start_urls = ['https://sais.health.pa.gov/CommonPOC/content/publiccommonpoc/normalSearch.asp']

    def start_requests(self):
        headers = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        }
        url = 'https://sais.health.pa.gov/CommonPOC/content/publiccommonpoc/CommonPOCSelect.asp?formSubmitted=normalformSearch'
        yield scrapy.Request(url, callback=self.get_contractors, headers=headers)

    def get_contractors(self, response):
        for tag in response.xpath('//div[@align="center"]/center/table/tr'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'PA Dept of Health')
            tag_text = tag.xpath('./td//text()').extract()
            l.add_value('business_name', tag_text[0].strip())
            l.add_value('industry_type', tag_text[2].strip())
            l.add_value('license_status', tag_text[4].strip())
            l.add_value('street_address', tag_text[5].strip())
            city_state_zip = tag_text[6].strip()
            if ', PA ' not in city_state_zip:
                city_state_zip = tag_text[7].strip()
                l.add_value('phone', tag_text[8].strip())
            else:
                l.add_value('phone', tag_text[7].strip())
            l.add_value('city', city_state_zip.split(',')[0])
            l.add_value('state', city_state_zip.split(',')[-1].strip().split(' ')[0].strip())
            l.add_value('postal_code', city_state_zip.split(',')[-1].split(' ')[-1].strip())
            l.add_value('country', 'USA')
            yield l.load_item()

