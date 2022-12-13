from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcBoardDieteticsNutritionSpider(scrapy.Spider):
    name = 'nc_board_dietetics_nutrition'
    allowed_domains = ['gateway.ncbdn.org']
    start_urls = ['https://gateway.ncbdn.org/verification/search.aspx']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://gateway.ncbdn.org',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        post_data = self.get_post_data(response)
        post_data['ctl00$Content$btnEnter'] = 'Search'
        for firstName in string.ascii_lowercase:
            for lastName in string.ascii_lowercase:
                post_data['ctl00$Content$txtFirstName'] = firstName
                post_data['ctl00$Content$txtLastName'] = lastName
                yield scrapy.FormRequest(
                    url='https://gateway.ncbdn.org/verification/search.aspx',
                    formdata=post_data,
                    headers=self.headers,
                    callback=self.get_data,
                    dont_filter=True
                )

    def get_data(self, response):
        for tag in response.xpath('//div[@class="search-results"]/div'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board of Dietetics/Nutrition')
            license_number = tag.xpath('.//dt[contains(., "License Number:")]/following-sibling::dd[1]/text()').extract_first()
            if license_number in self.buf:
                continue
            self.buf.append(license_number)
            l.add_value('license_number', license_number)
            full_name = tag.xpath('.//dt[contains(., "Name:")]/following-sibling::dd[1]/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_value('license_status', tag.xpath('.//dt[contains(., "License Status:")]/following-sibling::dd[1]/span/text()').extract_first())
            l.add_value('license_issue_date', tag.xpath('.//dt[contains(., "Issued Date:")]/following-sibling::dd[1]/text()').extract_first())
            if tag.xpath('.//dt[contains(., "Inactive Date:")]/following-sibling::dd[1]'):
                l.add_value('license_expiration_date', tag.xpath('.//dt[contains(., "Inactive Date:")]/following-sibling::dd[1]/text()').extract_first())
            else:
                l.add_value('license_expiration_date', tag.xpath('.//dt[contains(., "Expire Date:")]/following-sibling::dd[1]/text()').extract_first())

            l.add_value('license_type', tag.xpath('.//dt[contains(., "License Type:")]/following-sibling::dd[1]/text()').extract_first())
            l.add_value('NAICS', tag.xpath('.//dt[contains(., "Confirmation #:")]/following-sibling::dd[1]/text()').extract_first())
            yield l.load_item()


    def get_post_data(self, response):
        post_data = {}
        for tag in response.xpath('//input[@type="hidden"]'):
            label = tag.xpath('@name').extract_first()
            value = tag.xpath('@value').extract_first()
            post_data[label] = value if value else ''
        return post_data