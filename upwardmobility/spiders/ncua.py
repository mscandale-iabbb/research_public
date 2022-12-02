from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcuaSpider(scrapy.Spider):
    name = 'ncua'
    allowed_domains = ['mapping.ncua.gov']
    start_urls = ['https://mapping.ncua.gov/ResearchCreditUnion.aspx']
    headers = {
        'authority': 'mapping.ncua.gov',
        'accept': 'application/json',
        'accept-language': 'en-US,en;q=0.9',
        'content-type': 'application/json',
        'origin': 'https://mapping.ncua.gov',
        'referer': 'https://mapping.ncua.gov/ResearchCreditUnion.aspx',
        'sec-ch-ua': '"Google Chrome";v="107", "Chromium";v="107", "Not=A?Brand";v="24"',
        'sec-ch-ua-mobile': '?0',
        'sec-ch-ua-platform': '"Linux"',
        'sec-fetch-dest': 'empty',
        'sec-fetch-mode': 'cors',
        'sec-fetch-site': 'same-origin',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'x-requested-with': 'XMLHttpRequest',
    }

    json_data = {
        'CuName': None,
        'CuType': None,
        'CuStatus': None,
        'Region': None,
        'State': None,
        'City': None,
        'ZipCode': None,
        'FomType': None,
        'LowIncome': None,
        'IsMdi': None,
        'skip': 0,
        'take': 20,
    }
    search_api = 'https://mapping.ncua.gov/ResearchCreditUnion/DetailSearch'
    def start_requests(self):
        yield scrapy.Request(
            url=self.search_api,
            method='POST',
            body=json.dumps(self.json_data),
            callback=self.get_credits,
            headers=self.headers
        )

    def get_credits(self, response):
        results = json.loads(response.text)['results']
        for r in results:
            credit_url = f"https://mapping.ncua.gov/CreditUnionDetails/{r['charterNumber']}"
            yield scrapy.Request(credit_url, callback=self.parse_credit)

        if len(results) == 20:
            cur_skip = self.json_data['skip']
            self.json_data['skip'] = cur_skip + 20
            yield scrapy.Request(
                url=self.search_api,
                method='POST',
                body=json.dumps(self.json_data),
                callback=self.get_credits,
                headers=self.headers
            )

    def parse_credit(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'Credit Unions (All States)')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//td[contains(., "Credit Union Name:")]/following-sibling::td[1]/text()')
        l.add_xpath('street_address', '//td[contains(., "Address:")]/following-sibling::td[1]/text()')
        city_state_zip = response.xpath('//td[contains(., "City, State Zip code:")]/following-sibling::td[1]/text()').extract_first()
        csp_list = city_state_zip.split(',')
        l.add_value('city', csp_list[0].strip())
        l.add_value('state', csp_list[1].strip())
        l.add_value('postal_code', csp_list[2].strip())
        l.add_value('country', 'USA')
        l.add_xpath('phone', '//td[contains(., "Phone:")]/following-sibling::td[1]/text()')
        website = response.xpath('//td[contains(., "Website:")]/following-sibling::td[1]/text()').extract_first()
        if website and website.strip() != 'http://':
            l.add_value('website', website.strip())
        full_name = ''.join(response.xpath('//td[contains(., "CEO/Manager:")]/following-sibling::td[1]//text()').extract()).strip()
        prename, postname, first_name, last_name, middle_name = parse_name(full_name)
        l.add_value('prename', prename)
        l.add_value('postname', postname)
        l.add_value('first_name', first_name)
        l.add_value('last_name', last_name)
        l.add_value('middle_name', middle_name)

        l.add_xpath('industry_type', '//td[contains(., "Field of Membership Type:")]/following-sibling::td[1]/text()')
        l.add_xpath('license_number', '//td[contains(., "Charter Number:")]/following-sibling::td[1]/text()')
        l.add_xpath('license_type', '//td[contains(., "Credit Union Type:")]/following-sibling::td[1]/text()')
        l.add_xpath('license_status', '//td[contains(., "Credit Union Status:")]/following-sibling::td[1]/text()')
        l.add_xpath('license_issue_date', '//td[contains(., "Issue Date:")]/following-sibling::td[1]/text()')
        l.add_xpath('license_expiration_date', '//td[contains(., "Date Insured:")]/following-sibling::td[1]/text()')
        l.add_xpath('number_of_employees', '//td[contains(., "Number of Members:")]/following-sibling::td[1]/text()')
        return l.load_item()

