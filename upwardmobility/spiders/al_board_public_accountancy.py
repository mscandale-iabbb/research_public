from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class AlBoardPublicAccountancySpider(scrapy.Spider):
    name = 'al_board_public_accountancy'
    allowed_domains = ['asbpa.alabama.gov']
    custom_settings={'CONCURRENT_REQUESTS': 1}
    start_urls = ['https://www.asbpa.alabama.gov/FindCPA.aspx']
    headers = {
        'authority': 'www.asbpa.alabama.gov',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'origin': 'https://www.asbpa.alabama.gov',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }
    buf = []

    def parse(self, response):
        post_data = get_post_data(response)
        post_data.update({
            'ctl00$ContentPlaceHolder1$txtCity': '',
            'ctl00$ContentPlaceHolder1$ddlState': 'AL',
            'ctl00$ContentPlaceHolder1$txtLicenseNumber': '',
            'ctl00$ContentPlaceHolder1$btnSearch': 'Search',
        })
        for lastName in string.ascii_lowercase:
            post_data['ctl00$ContentPlaceHolder1$txtLastName'] = lastName
            yield scrapy.FormRequest(
                url='https://www.asbpa.alabama.gov/FindCPA.aspx',
                formdata=post_data,
                callback=self.get_data,
                headers=self.headers,
                dont_filter=True,
                meta={'lastName': lastName}
            )

    def get_data(self, response):
        for href in response.xpath('//table[@class="GridViewStyle"]//a[contains(@href, "FindCPA_Details.aspx?id=")]/@href').extract():
            if href in self.buf:
                continue
            self.buf.append(href)
            yield scrapy.Request(response.urljoin(href), callback=self.parse_profile)

        next_href = response.xpath('//td[./span]/following-sibling::td[1]/a/@href').extract_first()
        if next_href:
            lastName = response.meta['lastName']
            pageNum = next_href.split("('")[-1].split("','")[-1].split("')")[0]
            placeHolder = next_href.split("('")[-1].split("','")[0]
            try:
                __VIEWSTATE = re.search(r"\|__VIEWSTATE\|([\s\S]+?)\|", response.text).group(1)
                __VIEWSTATEGENERATOR = re.search(r"\|__VIEWSTATEGENERATOR\|([\s\S]+?)\|", response.text).group(1)
                __EVENTVALIDATION = re.search(r"\|__EVENTVALIDATION\|([\s\S]+?)\|", response.text).group(1)
                post_data = {
                    '__VIEWSTATE': __VIEWSTATE,
                    '__VIEWSTATEGENERATOR': __VIEWSTATEGENERATOR,
                    '__EVENTVALIDATION': __EVENTVALIDATION,
                    'ctl00$ContentPlaceHolder1$ScriptManager1': f'ctl00$ContentPlaceHolder1$UpdatePanel1|{placeHolder}',
                    '__EVENTTARGET': placeHolder,
                    '__EVENTARGUMENT': pageNum,
                    'ctl00$ContentPlaceHolder1$txtCity': '',
                    'ctl00$ContentPlaceHolder1$ddlState': 'AL',
                    'ctl00$ContentPlaceHolder1$txtLicenseNumber': '',
                    'ctl00$ContentPlaceHolder1$txtLastName': lastName,
                    '__ASYNCPOST': 'true'
                }
            except:
                post_data = get_post_data(response)
                post_data.update({
                    'ctl00$ContentPlaceHolder1$ScriptManager1': f'ctl00$ContentPlaceHolder1$UpdatePanel1|{placeHolder}',
                    '__EVENTTARGET': placeHolder,
                    '__EVENTARGUMENT': pageNum,
                    'ctl00$ContentPlaceHolder1$txtCity': '',
                    'ctl00$ContentPlaceHolder1$ddlState': 'AL',
                    'ctl00$ContentPlaceHolder1$txtLicenseNumber': '',
                    'ctl00$ContentPlaceHolder1$txtLastName': lastName,
                    '__ASYNCPOST': 'true'
                })
            yield scrapy.FormRequest(
                url='https://www.asbpa.alabama.gov/FindCPA.aspx',
                formdata=post_data,
                callback=self.get_data,
                headers=self.headers,
                meta={'lastName': lastName},
                dont_filter=True
            )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'AL Board of Public Accountancy')
        full_name = response.xpath('//span[@id="ContentPlaceHolder1_lblName"]/text()').extract_first()
        if full_name:
            prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
        business_name = ''.join(response.xpath('//span[@id="ContentPlaceHolder1_lblEmployer"]//text()').extract()).strip()
        if business_name:
            l.add_value('business_name', business_name.replace('Employer', '').strip())
        l.add_xpath('street_address', '//span[@id="ContentPlaceHolder1_lblAddress"]/text()')
        city_state_zip = response.xpath('//span[@id="ContentPlaceHolder1_lblCsz"]/text()').extract_first()
        l.add_value('city', city_state_zip.split(',')[0])
        l.add_value('state', city_state_zip.split(',')[1].strip().split(' ')[0])
        l.add_value('postal_code', city_state_zip.split(',')[1].strip().split(' ')[1])
        l.add_value('country', 'USA')
        l.add_xpath('phone', '//span[@id="ContentPlaceHolder1_lblPhone"]/text()')
        l.add_xpath('license_number', '//span[@id="ContentPlaceHolder1_lblCertificate"]/text()')
        l.add_xpath('license_status', '//span[@id="ContentPlaceHolder1_lblStatus"]/text()')
        l.add_xpath('license_type', '//span[@id="ContentPlaceHolder1_lblType"]/text()')
        l.add_xpath('license_issue_date', '//span[@id="ContentPlaceHolder1_lblDatecertified"]/text()')
        return l.load_item()
