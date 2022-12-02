from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from scrapy import Request, FormRequest, Spider


class MyfloridalicenseSpider(Spider):
    name = 'myfloridalicense'
    
    headers = {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Cache-Control': 'max-age=0',
        'Connection': 'keep-alive',
        'Content-Type': 'application/x-www-form-urlencoded',
        'Origin': 'https://www.myfloridalicense.com',
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36'
    }
    def start_requests(self):

        letters = 'abcdefghijklmnopqrstuvwxyz'
        url_list = ['{}'.format(first_letter) for first_letter in letters]
        for param in url_list:
            data = f'hSID=&hSearchType=Name&hLastName=&hFirstName=&hMiddleName=&hOrgName=&hSearchOpt=&hSearchOpt2=&hSearchAltName=&hSearchPartName=&hSearchFuzzy=&hDivision=ALL&hBoard=&hLicenseType=&hSpecQual=&hAddrType=&hCity=&hCounty=&hState=&hLicNbr=&hAction=&hCurrPage=&hTotalPages=&hTotalRecords=&hPageAction=&hDDChange=&hBoardType=&hLicTyp=&hSearchHistoric=&hRecsPerPage=&LastName=&FirstName={param}&MiddleName=&OrgName=&Board=%A0&City=&County=&State=&RecsPerPage=50&Search1=Search'
            url = 'https://www.myfloridalicense.com/wl11.asp?mode=2&search=Name&SID=&brd=&typ=N'
            yield Request(url=url, method='POST', meta={'param': param}, headers=self.headers, body=data, dont_filter=True)

    def parse(self, response):
        totalpage = response.xpath('//strong[contains(text(),"Search Results")]/text()').extract_first('').strip()
        try:
            result = re.findall(r'\d+', totalpage)[0]
        except:
            result = ''
        param = response.meta.get('param')
        if result:
            pages = response.xpath('//input[@name="Page"]/../text()[1]').extract_first('').strip().split(' of ')[1]
            for page in range(0, int(pages) + 1):
                if page == 0:
                    page == 1
                data = {
                    'hSID': '',
                    'hSearchType': 'Name',
                    'hLastName': '',
                    'hFirstName': param,
                    'hMiddleName': '',
                    'hOrgName': '',
                    'hSearchOpt': 'Organization',
                    'hSearchOpt2': '',
                    'hSearchAltName': 'Alt',
                    'hSearchPartName': '',
                    'hSearchFuzzy': '',
                    'hDivision': '',
                    'hBoard': '',
                    'hLicenseType': '',
                    'hSpecQual': '',
                    'hAddrType': '',
                    'hCity': '',
                    'hCounty': '',
                    'hState': '',
                    'hLicNbr': '',
                    'hAction': '',
                    'hCurrPage': str(page),
                    'hTotalPages': str(pages),
                    'hTotalRecords': str(result),
                    'hPageAction': '4',
                    'hDDChange': '',
                    'hBoardType': '',
                    'hLicTyp': '',
                    'hSearchHistoric': '',
                    'hRecsPerPage': '50',
                    'Page': '',
                    'SearchForward': 'Search',
                }
                url = 'https://www.myfloridalicense.com/wl11.asp?mode=3&search=Name&SID=&brd=&typ='
                yield FormRequest(url=url, callback=self.parse_list_page, headers=self.headers, formdata=data, dont_filter=True)
    
    def parse_list_page(self, response):
        details = response.xpath('//td//a[contains(@href,"LicenseDetail")]')
        for detail in details:
            url = response.urljoin(detail.xpath('./@href').extract_first())
            yield Request(url, callback=self.get_parse_detail)

    def get_parse_detail(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'DBPR')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//font[contains(text(),"Name:")]/../following-sibling::td//b/text()')
        l.add_xpath('street_address', '//font[contains(text(),"Main Address:")]/../following-sibling::td//b/text()[1]')
        address = response.xpath('//font[contains(text(),"Main Address:")]/../following-sibling::td//b/text()[2]').extract_first('').strip()
        if ' ' not in address:
            address = response.xpath('//font[contains(text(),"Main Address:")]/../following-sibling::td//b/text()[3]').extract_first('').strip()
        city = address.split('  ')[0]
        state = address.split('  ')[1]
        postal_code = address.split('  ')[-1]  
        l.add_value('city', city)
        l.add_value('state', state)
        l.add_value('postal_code', postal_code)
        l.add_value('country', 'USA')
        l.add_xpath('license_number', '//font[contains(text(),"License Number")]/../following-sibling::td//b/text()')
        l.add_xpath('license_type', '//font[contains(text(),"License Type")]/../following-sibling::td//b/text()')
        l.add_xpath('license_issue_date', '//font[contains(text(),"Licensure Date:")]/../following-sibling::td//b/text()')
        l.add_xpath('license_expiration_date', '//font[contains(text(),"Expires:")]/../following-sibling::td//b/text()')
        return l.load_item()
# rm myfloridalicense.csv; scrapy crawl myfloridalicense -o myfloridalicense.csv