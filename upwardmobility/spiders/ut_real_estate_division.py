from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class UtRealEstateDivisionSpider(scrapy.Spider):
    name = 'ut_real_estate_division'
    allowed_domains = ['secure.utah.gov']
    start_urls = ['https://secure.utah.gov/rer/relv/search.html']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://secure.utah.gov',
        'Referer': 'https://secure.utah.gov/rer/relv/search.html',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
    }
    csv_headers = []

    def parse(self, response):
        data_key = response.xpath('//input[@value="Download Real Estate Agents and Brokers Summary"]/@name').extract_first()
        _csrf = response.xpath('//input[@name="_csrf"]/@value').extract_first()
        data = {
            data_key: 'Download Real Estate Agents and Brokers Summary',
            '_csrf': _csrf,
        }
        yield scrapy.FormRequest(
            url='https://secure.utah.gov/rer/relv/search.html',
            formdata=data,
            callback=self.get_data,
            headers=self.headers
        )

    def get_data(self, response):
        f = io.StringIO()
        for row_idx, row in enumerate(response.text.splitlines()):
            if row_idx == 0:
                self.csv_headers = [r.replace('"','') for r in row.split(',')]
                continue
            # Write one line to the in-memory file.
            f.write(row)
            # Seek sends the file handle to the top of the file.
            f.seek(0)
            reader = csv.DictReader(f, fieldnames=self.csv_headers)
            row = next(reader)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'UT Real Estate Division')
            l.add_value('business_name', row['Association'])
            l.add_value('first_name', row['First Name'])
            l.add_value('last_name', row['Last Name'])
            l.add_value('middle_name', row['Middle Name'])
            l.add_value('license_number', row['License #'])
            l.add_value('license_status', row['Status'])
            l.add_value('license_type', row['License Type'])
            l.add_value('license_issue_date', row['Issue Date'].replace('=','').replace('"',''))
            l.add_value('license_expiration_date', row['Expiration Date'].replace('=','').replace('"',''))
            yield l.load_item()
            f.seek(0)

            # Clean up the buffer.
            f.flush()

