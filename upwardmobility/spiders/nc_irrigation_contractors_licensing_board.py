from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcIrrigationContractorsLicensingBoardSpider(scrapy.Spider):
    name = 'nc_irrigation_contractors_licensing_board'
    allowed_domains = ['myaccount.nciclb.org']
    start_urls = ['https://myaccount.nciclb.org/licensees/licensees-individual']

    def parse(self, response):
        for tag in response.xpath('//tr[@class="title"]'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Irrigation Contractors Licensing Board')
            l.add_value('license_number', tag.xpath('./td[3]/text()').extract_first())
            full_name = tag.xpath('./td[1]/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_value('business_name', tag.xpath('./td[2]/text()').extract_first())
            l.add_value('email', tag.xpath('./following-sibling::tr[1]/td[2]/p/a/text()').extract_first())
            addresses = tag.xpath('./following-sibling::tr[1]/td[1]/p/text()').extract()
            addresses = [a.replace("\n", "").strip() for a in addresses if a.replace("\n", "").strip()]
            if len(addresses) > 3:
                l.add_value('street_address', addresses[-2])
                l.add_value('city', ', '.join(addresses[-1].split(',')[:-1]).strip())
                l.add_value('state', addresses[-1].split(',')[-1].strip().split(' ')[0])
                l.add_value('postal_code', addresses[-1].split(',')[-1].strip().split(' ')[1])
                l.add_value('country', 'USA')
            contacts = tag.xpath('./following-sibling::tr[1]/td[2]/p/text()').extract()
            contacts = [c.replace("\n", "").strip() for c in contacts if c.replace("\n", "").strip()]
            dates = tag.xpath('./following-sibling::tr[1]/td[3]/p/text()').extract()
            dates = [d.replace("\n", "").strip() for d in dates if d.replace("\n", "").strip()]
            for c in contacts:
                if '(phone)' in c:
                    l.add_value('phone', c.split("(p")[0].strip())
                elif '(fax)' in c:
                    l.add_value('fax', c.split("(f")[0].strip())
            for d in dates:
                if 'Valid until:' in d:
                    l.add_value('license_expiration_date', d.split(':')[-1].strip())
            yield l.load_item()

        next_href = response.xpath('//a[@class="next"]/@href').extract_first()
        if next_href:
            yield scrapy.Request(response.urljoin(next_href))

