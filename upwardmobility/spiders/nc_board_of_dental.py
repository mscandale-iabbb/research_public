from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcBoardofDentalSpider(scrapy.Spider):
    name = 'nc_board_of_dental'
    allowed_domains = ['membersbase.com']
    start_urls = ['https://www.membersbase.com/NCBDESearch/license_verification.htm']
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Origin': 'https://www.membersbase.com',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }

    def parse(self, response):
        for first_name in string.ascii_lowercase:
            for last_name in string.ascii_lowercase:
                data = {
                    'type': 'D',
                    'firstname': first_name,
                    'lastname': last_name,
                    'license': '',
                    'Submit': 'Submit',
                }
                yield scrapy.FormRequest(
                    url='https://www.membersbase.com/NCBDESearch/searchresult.asp',
                    formdata=data,
                    callback=self.parse_profile,
                    headers=self.headers
                )

    def parse_profile(self, response):
        for tag_idx, tag in enumerate(response.xpath('//table[contains(@class, "table")]/font/tr[./td[@colspan]]')):
            if tag_idx == 0:
                continue
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board of Dental')
            full_name = ''.join(tag.xpath('./following-sibling::tr[1]/td[1]/b/text()').extract())
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_value('city', tag.xpath('./following-sibling::tr[2]/td[1]/b/following-sibling::text()').extract_first())
            l.add_value('state', tag.xpath('./following-sibling::tr[3]/td[1]/b/following-sibling::text()').extract_first())
            l.add_value('license_number', tag.xpath('./following-sibling::tr[1]/td[2]/b/following-sibling::text()').extract_first())
            l.add_value('license_issue_date', tag.xpath('./following-sibling::tr[2]/td[2]/b/following-sibling::text()').extract_first())
            l.add_value('license_expiration_date', tag.xpath('./following-sibling::tr[3]/td[2]/b/following-sibling::text()').extract_first())
            l.add_value('license_status', tag.xpath('./following-sibling::tr[4]/td[2]/b/following-sibling::text()').extract_first())
            yield l.load_item()

