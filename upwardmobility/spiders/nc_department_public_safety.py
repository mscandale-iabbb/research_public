from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcDepartmentPublicSafetySpider(scrapy.Spider):
    name = 'nc_department_public_safety'
    allowed_domains = ['ppsapplication.permitium.com']
    custom_settings = {'CONCURRENT_REQUESTS': 1}
    start_urls = ['http://ppsapplication.permitium.com/']
    api_url = "https://ppsapplication.permitium.com/api/searchactives?firstName=&lastName=&companyName={}&id=&city=&types=&entityType=licensee&page={}&pageSize=25"
    headers = {
        'authority': 'ppsapplication.permitium.com',
        'accept': '*/*',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def start_requests(self):
        pageNum = 1
        for companyName in string.ascii_lowercase:
            url = self.api_url.format(companyName, pageNum)
            yield scrapy.Request(url, callback=self.get_data, headers=self.headers, meta={'pageNum': pageNum, 'companyName': companyName})

    def get_data(self, response):
        json_data = json.loads(response.text)
        for c in json_data:
            if c['licUserId'] in self.buf:
                continue
            self.buf.append(c['licUserId'])
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Department of Public Safety')
            l.add_value('first_name', c['firstName'])
            l.add_value('last_name', c['lastName'])
            l.add_value('middle_name', c['middleName'])
            l.add_value('postname', c['suffix'])
            l.add_value('business_name', c['companyName'])
            l.add_value('city', c['city'])
            l.add_value('country', 'USA')
            l.add_value('license_number', c['licenses'][0]['licenseeId'])
            if c['licenses'][0]['isActive']:
                l.add_value('license_status', 'Active')
            else:
                l.add_value('license_status', 'Inactive')
            try:
                l.add_value('license_expiration_date', c['licenses'][0]['orderData']['meta']['printData']['expirationDate'])
            except:
                pass
            try:
                bpn = c['bpns'][0]['bpn']
            except:
                bpn = None
            if bpn:
                u = f"https://ppsapplication.permitium.com/api/searchactives?firstName=&lastName=&companyName=&id=&licuid={bpn}&types=&entityType=office&page=1&pageSize=1"
                yield scrapy.Request(u, callback=self.parse_address, headers=self.headers, meta={'item':l.load_item()})
            else:
                yield l.load_item()
        if len(json_data) == 25:
            pageNum = response.meta['pageNum']
            companyName = response.meta['companyName']
            url = self.api_url.format(companyName, pageNum+1)
            yield scrapy.Request(url, callback=self.get_data, headers=self.headers, meta={'pageNum': pageNum+1, 'companyName': companyName})

    def parse_address(self, response):
        l = CompanyLoader(response.meta.get('item', UpwardMobilityItem()), response=response)
        json_data = json.loads(response.text)
        try:
            phone = json_data[0]['details']['branchPhoneNumber']
            l.add_value('phone', phone)
        except:
            pass
        try:
            l.add_value('street_address', json_data[0]['details']['branchAddress']['mailingAddress']['addressLine1'])
            l.add_value('state', json_data[0]['details']['branchAddress']['mailingAddress']['state'])
            l.add_value('postal_code', json_data[0]['details']['branchAddress']['mailingAddress']['zip'])
        except:
            pass

        yield l.load_item()




