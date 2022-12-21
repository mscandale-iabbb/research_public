from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class ScDepartmentPesticideRegulationSpider(scrapy.Spider):
    name = 'sc_department_pesticide_regulation'
    allowed_domains = ['regfocus.clemson.edu']
    start_urls = ['http://regfocus.clemson.edu/dpr/blicense.htm']
    buf = []

    def parse(self, response):
        for searchText in string.ascii_uppercase:
            u = f"http://regfocus.clemson.edu/cgi-bin/blicence.asp?IBIF_ex=blic&COMPANY={searchText}&NAME=&BNUM=&LNO=&B1=Run+License+Report"
            yield scrapy.Request(u, callback=self.get_data)

    def get_data(self, response):
        for tag_idx, tag in enumerate(response.xpath('//table/tr')):
            if tag_idx == 0:
                continue
            license_number = ''.join(tag.xpath('./td[1]//text()').extract()).strip()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'SC Department of Pesticide Regulation')
            business_name = ''.join(tag.xpath('./td[2]//text()').extract()).strip()
            l.add_value('business_name', business_name)
            l.add_value('license_number', license_number)
            full_name = ''.join(tag.xpath('./td[3]//text()').extract()).strip()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            yield l.load_item()
