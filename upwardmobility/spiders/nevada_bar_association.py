from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NevadaBarAssociationSpider(scrapy.Spider):
    name = 'nevada_bar_association'
    allowed_domains = ['nvbar.org']
    start_urls = ['https://nvbar.org/']
    buf = []

    def parse(self, response):
        for comb in string.ascii_lowercase:
            u = f"https://nvbar.org/for-the-public/find-a-lawyer/?usearch={comb}"
            yield scrapy.Request(u, callback=self.parse_profile)

    def parse_profile(self, response):
        for tag in response.xpath('//article[contains(@class, "user_chunk")]'):
            license_number = tag.xpath('.//strong[contains(.,"Bar # :")]/following-sibling::text()').extract_first()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'Nevada Bar Association')
            full_name = tag.xpath('./div[1]/h3/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_value('license_number', license_number)
            l.add_value('license_issue_date', tag.xpath('.//strong[contains(.,"since:")]/following-sibling::text()').extract_first())
            l.add_value('license_status', tag.xpath('.//strong[contains(.,"Status:")]/following-sibling::text()').extract_first())
            text = tag.xpath('./div[2]//text()').extract()
            text = [t for t in text if replace_nbsp(t).strip()]
            addresses = []
            for line_idx, line in enumerate(text):
                if 'Company:' == line.strip():
                    l.add_value('business_name', text[line_idx+1])
                    addresses = text[2].split(',')
                elif 'Company: ' in line:
                    l.add_value('business_name', line.split(':')[-1])
                    addresses = text[1].split(',')
                elif 'Phone' in line:
                    l.add_value('phone', text[line_idx+1])
                elif 'Fax' in line:
                    l.add_value('fax', text[line_idx+1])
                elif 'Email' in line:
                    l.add_value('email', text[line_idx+1])
            if not addresses and text:
                addresses = text[0].split(',')
            if addresses:
                state_zip = addresses[-1].strip()
                address_city = ', '.join(addresses[:-1]).strip()
                l.add_value('street_address', ', '.join(address_city.split(',')[:-1]).strip())
                l.add_value('city', address_city.split(',')[-1].strip())
                if len(state_zip.split(' ')) == 1:
                    l.add_value('postal_code', state_zip.split(' ')[0].strip())
                else:
                    l.add_value('state', state_zip.split(' ')[0].strip())
                    l.add_value('postal_code', state_zip.split(' ')[1].strip())
                l.add_value('country', 'USA')
            yield l.load_item()
