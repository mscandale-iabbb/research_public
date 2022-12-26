from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class AlBoardArchitectsSpider(scrapy.Spider):
    name = 'al_board_architects'
    allowed_domains = ['apps.boa.alabama.gov']
    start_urls = ['https://apps.boa.alabama.gov/RosterSearch/search.aspx']
    headers = {
        'authority': 'apps.boa.alabama.gov',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'origin': 'https://apps.boa.alabama.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        post_data = get_post_data(response)
        for str1 in string.ascii_lowercase:
            for str2 in string.ascii_lowercase:
                txtLastName = f"{str1}{str2}"
                post_data['ctl00$ContentPlaceHolder1$txtLastName'] = txtLastName
                post_data['ctl00$ContentPlaceHolder1$btnSubmit'] = 'Search'
                yield scrapy.FormRequest(
                    url='https://apps.boa.alabama.gov/RosterSearch/search.aspx',
                    formdata=post_data,
                    callback=self.get_data,
                    headers=self.headers,
                    dont_filter=True
                )

    def get_data(self, response):
        post_data = get_post_data(response)
        for href in response.xpath('//table[@id="ContentPlaceHolder1_gvArchitects"]//a/@href').extract():
            label = href.replace("javascript:__doPostBack('", "").split("','")[0]
            value = href.replace("javascript:__doPostBack('", "").split("','")[-1].replace("')", '')
            if 'ViewDetails' in value:
                post_data['__EVENTTARGET'] = label
                post_data['__EVENTARGUMENT'] = value
                yield scrapy.FormRequest(
                    url='https://apps.boa.alabama.gov/RosterSearch/results.aspx',
                    formdata=post_data,
                    callback=self.parse_profile,
                    headers=self.headers,
                    dont_filter=True
                )
            else:
                post_data['__EVENTTARGET'] = label
                post_data['__EVENTARGUMENT'] = value
                yield scrapy.FormRequest(
                    url='https://apps.boa.alabama.gov/RosterSearch/results.aspx',
                    formdata=post_data,
                    callback=self.get_more_profiles,
                    headers=self.headers,
                    dont_filter=True
                )

    def get_more_profiles(self, response):
        post_data = get_post_data(response)
        for href in response.xpath('//table[@id="ContentPlaceHolder1_gvArchitects"]//a/@href').extract():
            label = href.replace("javascript:__doPostBack('", "").split("','")[0]
            value = href.replace("javascript:__doPostBack('", "").split("','")[-1].replace("')", '')
            if 'ViewDetails' in value:
                post_data['__EVENTTARGET'] = label
                post_data['__EVENTARGUMENT'] = value
                yield scrapy.FormRequest(
                    url='https://apps.boa.alabama.gov/RosterSearch/results.aspx',
                    formdata=post_data,
                    callback=self.parse_profile,
                    headers=self.headers,
                    dont_filter=True
                )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'AL Board of Architects')
        license_number = response.xpath('//span[@id="ContentPlaceHolder1_lblLicense"]/text()').extract_first()
        if license_number not in self.buf:
            self.buf.append(license_number)
            l.add_value('license_number', license_number)
            full_name = response.xpath('//span[@id="ContentPlaceHolder1_lblName"]/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_xpath('business_name', '//span[@id="ContentPlaceHolder1_lblOrganization"]/text()')
            l.add_xpath('street_address', '//span[@id="ContentPlaceHolder1_lblAddress"]/text()')
            l.add_xpath('city', '//span[@id="ContentPlaceHolder1_lblCity"]/text()')
            l.add_xpath('state', '//span[@id="ContentPlaceHolder1_lblState"]/text()')
            l.add_xpath('postal_code', '//span[@id="ContentPlaceHolder1_lblZip"]/text()')
            l.add_xpath('license_status', '//span[@id="ContentPlaceHolder1_lblStatus"]/text()')
            l.add_xpath('license_issue_date', '//span[@id="ContentPlaceHolder1_lblRegisterDate"]/text()')
            l.add_xpath('license_expiration_date', '//span[@id="ContentPlaceHolder1_lblExpirationDate"]/text()')
            return l.load_item()