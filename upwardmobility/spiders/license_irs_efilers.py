from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class LicenseIRSEFilersSpider(scrapy.Spider):
    name = 'license_irs_efilers'
    allowed_domains = ['irs.gov']
    start_urls = ['https://www.irs.gov/efile-index-taxpayer-search?zip=&state=All']

    def parse(self, response):
        totalpages = response.xpath('//h2[@id="solr-results-summary"]/text()').extract_first().split('Matching')[0].replace('Found', '').strip()
        for page_num in range(0, int(int(totalpages)/10)+1):
            page_link = f"https://www.irs.gov/efile-index-taxpayer-search?zip=&state=All&page={page_num}"
            yield scrapy.Request(page_link, callback=self.parse_page)

    def parse_page(self, response):
        for tag in response.xpath('//div[@class="table-responsive"]//table//tr'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'License IRS E-Filers')
            l.add_value('company_url', response.url)
            text = [t.strip() for t in tag.xpath('.//td[contains(@class, "views-align-left")]/text()').extract() if t.strip]
            for idx, t in enumerate(text):
                if idx == 0:
                    for line_idx, line in enumerate(t.splitlines()):
                        if line_idx == 0:
                            l.add_value('business_name', line)
                        elif line_idx == 1:
                            l.add_value('street_address', line)
                        elif line_idx == 2:
                            city = line.split(',')[0].strip()
                            state_zip = line.split(f'{city},')[-1].strip()
                            state = state_zip.split(' ')[0]
                            zipcode = state_zip.split(' ')[-1]
                            l.add_value('city', city)
                            l.add_value('state', state)
                            l.add_value('postal_code', zipcode)
                        elif line_idx == 3:
                            prename, postname, first_name, last_name, middle_name = parse_name(line)
                            l.add_value('prename', prename)
                            l.add_value('postname', postname)
                            l.add_value('first_name', first_name)
                            l.add_value('last_name', last_name)
                            l.add_value('middle_name', middle_name)
                elif idx == 1:
                    l.add_value('industry_type', t.strip())
            phone = tag.xpath('.//a/text()').extract_first()
            if phone:
                l.add_value('phone', phone)
            yield l.load_item()
