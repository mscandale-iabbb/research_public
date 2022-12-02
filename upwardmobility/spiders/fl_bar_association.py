from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class FloridaBarAssociationSpider(scrapy.Spider):
    name = 'fl_bar_association'

    def start_requests(self):
        url = 'https://www.floridabar.org/directories/find-mbr/?locType=S&locValue=Florida&sdx=Y&eligible=N&deceased=N&services=HIT%7CPBS&pageNumber=1&pageSize=10'
        yield scrapy.Request(url, headers={'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36'})
    
    def parse(self, response):
        total_pages = response.xpath('//ul[@class="member-pagination paging-controls"]/li//a/text()').extract()[-1]
        for page in range(1, int(total_pages)):
            url = f'https://www.floridabar.org/directories/find-mbr/?locType=S&locValue=Florida&sdx=Y&eligible=N&deceased=N&services=HIT%7CPBS&pageNumber={page}&pageSize=10'
            yield scrapy.Request(url, callback=self.parse_list_page, headers={'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36'})     

    def parse_list_page(self, response):
        for link in response.xpath('//p[@class="profile-bar-number"]'):
            bar_number = link.xpath('./span/text()').extract_first().replace('#', '')
            url = f'https://www.floridabar.org/about/volbars/profile/?num={bar_number}'
            yield scrapy.Request(url, callback=self.parse_detail_page, headers={'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36'})

    def parse_detail_page(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Florida Bar Association (Attorney)')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//label[contains(text(),"Firm:")]/../following-sibling::div/p/text()')
        street_address = response.xpath('//label[contains(text(),"Mail Address")]/../following-sibling::div/p[1]/text()[2]').extract_first('').strip()
        l.add_value('street_address', street_address)
        address = response.xpath('//label[contains(text(),"Mail Address")]/../following-sibling::div/p/text()').extract()
        for city_state in address:
            try:
                postal5 = re.findall(r'\d{5}', city_state)
            except:
                postal5 = ''
            if ', ' in city_state and postal5: 
                city = city_state.split(', ')[0]
                state = city_state.split(', ')[1].split(' ')[0] 
                postal_code = city_state.split(', ')[1].split(' ')[1]
                city_state = response.xpath('//label[contains(text(),"Mail Address")]/../following-sibling::div/p[1]/text()[3]').extract_first('').strip()    
        l.add_value('city', city)
        l.add_value('state', state)
        l.add_value('postal_code', postal_code)
        l.add_value('country', 'USA')
        phone = response.xpath('//p[contains(text(),"Cell")]/a/text()').extract_first('').strip()
        fax = response.xpath('//p[contains(text(),"Fax")]/text()').extract_first('').strip().replace('Fax:', '')
        l.add_value('phone', phone)
        email = response.xpath('//span[@class="__cf_email__"]/@data-cfemail').extract_first('')
        if email:
            l.add_value('email', decodeEmail(email))
        l.add_value('fax', fax)
        l.add_xpath('license_number', '//label[contains(text(),"Bar Number")]/../following-sibling::div/p/text()')
        l.add_xpath('license_type', '//label[contains(text(),"Firm Position")]/../following-sibling::div/p/text()')
        full_name = ''.join(response.xpath('//div[@id="mProfile"]/h1/text()').extract()).strip()
        prename, postname, first_name, last_name, middle_name = parse_name(full_name)
        l.add_value('prename', prename)
        l.add_value('postname', postname)
        l.add_value('first_name', first_name)
        l.add_value('last_name', last_name)
        l.add_value('middle_name', middle_name)

        return l.load_item()     

# rm floridabar.csv; scrapy crawl floridabar