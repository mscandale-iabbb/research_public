from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class TheStateBarCaliforniaSpider(scrapy.Spider):
    name = 'the_state_bar_california'
    allowed_domains = ['apps.calbar.ca.gov']
    start_urls = ['https://apps.calbar.ca.gov/attorney/LicenseeSearch/QuickSearch']
    buf = []

    def parse(self, response):
        for freeText in string.ascii_lowercase:
            url = f"https://apps.calbar.ca.gov/attorney/LicenseeSearch/QuickSearch?FreeText={freeText}&SoundsLike=false"
            yield scrapy.Request(url, callback=self.get_data)

    def get_data(self, response):
        for href in response.xpath('//table[@id="tblAttorney"]/tbody/tr//a/@href').extract():
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'The State Bar of California')
        name_number = response.xpath('//div[@style="margin-top:1em;"]/h3/b/text()').extract_first()
        if not name_number:
            return
        full_name = name_number.split('#')[0].strip()
        prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
        l.add_value('prename', prename)
        l.add_value('postname', postname)
        l.add_value('first_name', first_name)
        l.add_value('last_name', last_name)
        l.add_value('middle_name', middle_name)
        l.add_value('license_number', name_number.split('#')[-1].strip())
        license_status = ''.join(response.xpath('//b[contains(., "License Status:")]//text()').extract()).strip()
        if license_status:
            l.add_value('license_status', license_status.split(':')[-1].strip())
        addresses = ''.join(response.xpath('//p[contains(., "Address:")]//text()').extract()).strip()
        if addresses:
            addresses = addresses.split('Address:')[1].strip()
            info_txt = addresses.split(',')
            if len(info_txt) == 4:
                l.add_value('business_name', info_txt[0])
                l.add_value('street_address', info_txt[1])
                l.add_value('city', info_txt[2])
                l.add_value('state', info_txt[3].strip().split(' ')[0])
                l.add_value('postal_code', info_txt[3].strip().split(' ')[1])
                l.add_value('country', 'USA')
            elif len(info_txt) == 3:
                if 'law' not in info_txt[0].lower() or 'llc' not in info_txt[0].lower():
                    l.add_value('street_address', info_txt[0])
                    l.add_value('city', info_txt[1])
                    l.add_value('state', info_txt[2].strip().split(' ')[0])
                    l.add_value('postal_code', info_txt[2].strip().split(' ')[1])
                    l.add_value('country', 'USA')
        phone_fax = response.xpath('//p[contains(., "Phone:")]/text()').extract_first()
        phone = phone_fax.split('|')[0].split(':')[-1]
        if 'Not Available' not in phone:
            l.add_value('phone', phone.strip())
        fax = phone_fax.split('|')[1].split(':')[-1]
        if 'Not Available' not in fax:
            l.add_value('fax', fax.strip())
        l.add_xpath('website', '//a[@id="websiteLink"]/text()')
        return l.load_item()