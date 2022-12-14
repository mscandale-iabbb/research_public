from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcBoardRefrigerationContractorsSpider(scrapy.Spider):
    name = 'nc_board_refrigeration_contractors'
    allowed_domains = ['refrigerationboard.org']
    start_urls = ['http://www.refrigerationboard.org/dbsearch.html']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'http://www.refrigerationboard.org',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        for sblname in string.ascii_lowercase:
            data = {
                'sbkeyword': '',
                'sblnum': '',
                'sblname': sblname,
                'sbconame': '',
                'sbcity': '',
            }
            yield scrapy.FormRequest(
                url='http://www.refrigerationboard.org/cgi-bin/dbsearch.pl',
                formdata=data,
                callback=self.get_data,
                headers=self.headers
            )

    def get_data(self, response):
        for tag in response.xpath('//table'):
            license_number = tag.xpath('.//td[contains(., "License Number:")]/following-sibling::td[1]/text()').extract_first()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board Of Refrigeration Contractors')
            l.add_value('license_number', license_number)
            full_name = tag.xpath('//td[contains(., "Name:")]/following-sibling::td[1]/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            business_name = tag.xpath('.//td[contains(., "Company:")]/following-sibling::td[1]/text()').extract_first()
            l.add_value('business_name', business_name)
            l.add_value('street_address', tag.xpath('.//td[contains(., "Address:")]/following-sibling::td[1]/text()').extract_first())
            city_state_zip = tag.xpath('.//td[contains(., "City, St, Zip:")]/following-sibling::td[1]/text()').extract_first()
            l.add_value('city', city_state_zip.split(',')[0].strip())
            l.add_value('state', city_state_zip.split(',')[-1].strip().split(' ')[0])
            l.add_value('postal_code', city_state_zip.split(',')[-1].strip().split(' ')[1])
            l.add_value('country', 'USA')
            yield l.load_item()



