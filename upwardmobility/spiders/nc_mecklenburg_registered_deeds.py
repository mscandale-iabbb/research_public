from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class FederalAviationAdministrationSpider(scrapy.Spider):
    name = 'federal_aviation_administration'
    allowed_domains = ['meckrod.manatron.com']
    start_urls = ['https://www.faa.gov/licenses_certificates/airmen_certification/releasable_airmen_download/']

    def parse(self, response):
        file = open('PILOT_BASIC.csv', 'r')
        reader = csv.reader(file)
        for row_idx, row in enumerate(reader):
            if row_idx == 0:
                continue
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Federal Aviation Administration')
            l.add_value('license_number', row[0])
            first_name = row[1].split(' ')[0]
            l.add_value('first_name', first_name)
            if len(row[1].strip().split(' ')) > 1:
                l.add_value('middle_name', row[1].split(' ')[-1])
            last_name = row[2].split(' ')[0]
            l.add_value('last_name', last_name)
            if len(row[2].strip().split(' ')) == 2:
                l.add_value('postname', row[2].split(' ')[-1])
            l.add_value('street_address', row[3])
            l.add_value('city', row[5])
            l.add_value('state', row[6])
            l.add_value('postal_code', row[7])
            yield l.load_item()

        
    