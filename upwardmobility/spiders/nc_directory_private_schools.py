from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcDirectoryPrivateSchoolsSpider(scrapy.Spider):
    name = 'nc_directory_private_schools'
    allowed_domains = ['ncadmin.nc.gov']
    start_urls = ['https://ncadmin.nc.gov/public/private-school-information/nc-directory-private-schools']
    headers = {
        'authority': 'ncadmin.nc.gov',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'accept-language': 'en-US,en;q=0.9',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }

    def start_requests(self):
        yield scrapy.Request(self.start_urls[0], callback=self.get_csv_url, headers=self.headers)

    def get_csv_url(self, response):
        csv_link = response.xpath('//div[@class="cards"]//a/@href').extract_first()
        yield scrapy.Request(csv_link, callback=self.parse_csv, headers=self.headers)

    def parse_csv(self, response):
        f = io.StringIO()
        for row_idx, row in enumerate(response.text.splitlines()):
            if row_idx == 0:
                continue
            # Write one line to the in-memory file.
            f.write(row)
            # Seek sends the file handle to the top of the file.
            f.seek(0)
            reader = csv.reader(f)
            row = next(reader)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Directory of Private Schools')
            l.add_value('business_name', row[0])
            l.add_value('street_address', row[1])
            l.add_value('city', row[2])
            l.add_value('state', row[3])
            l.add_value('postal_code', row[4])
            l.add_value('phone', row[9])
            l.add_value('email', row[11])
            l.add_value('industry_type', row[13])
            l.add_value('country', 'USA')
            full_name = row[14].strip()
            prename = row[10]
            if prename:
                l.add_value('prename', prename)
                full_name = full_name.replace(prename, '')
            prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)

            yield l.load_item()
            f.seek(0)

            # Clean up the buffer.
            f.flush()
