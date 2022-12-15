from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
from scrapy.selector import Selector

class NcAcupunctureBoardSpider(scrapy.Spider):
    name = 'nc_acupuncture_board'
    allowed_domains = ['ncalb.com']
    start_urls = ['https://www.ncalb.com/acupuncturist-directory/']
    headers = {
        'authority': 'www.ncalb.com',
        'accept': '*/*',
        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'origin': 'https://www.ncalb.com',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
        'x-requested-with': 'XMLHttpRequest',
    }
    data = {
        'action': 'staxx_infinite_scroll',
        'args[post_type]': 'acupuncturist',
    }
    def parse(self, response):
        nonce = re.search(r'"nonce":"(.*?)"', response.text).group(1)
        self.data['_ajax_nonce'] = nonce
        self.data['args[paged]'] = '1'
        yield scrapy.FormRequest(
            url='https://www.ncalb.com/wp-admin/admin-ajax.php',
            formdata=self.data,
            callback=self.get_data,
            headers=self.headers,
            meta={'pageNum': 1}
        )

    def get_data(self, response):
        r_text = response.text.replace('\\"','"').replace('\\n\\t','').replace('\\n','').replace('\\t','').replace('\\/','/')
        r_text = r_text[1:-1]
        profiles = Selector(text=r_text).xpath('//div[@class="acupuncturist"]')
        for tag in profiles:
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Acupuncture Board')
            full_name = tag.xpath('./h4/text()').extract_first()
            city = tag.xpath('.//span[@class="city"]/text()').extract_first()
            state = tag.xpath('.//span[@class="state"]/text()').extract_first()
            postal_code = tag.xpath('.//span[@class="zip"]/text()').extract_first()
            email = tag.xpath('./div[@class="email"]/text()').extract_first()
            license_status = tag.xpath('./div[@class="status"]/text()').extract_first()
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)
            l.add_value('city', city)
            l.add_value('state', state)
            l.add_value('postal_code', postal_code)
            l.add_value('email', email)
            l.add_value('license_status', license_status)
            l.add_value('country', 'USA')
            yield l.load_item()

        if len(profiles) == 10:
            pageNum = response.meta['pageNum']
            pageNum = pageNum + 1
            self.data['args[paged]'] = str(pageNum)
            yield scrapy.FormRequest(
                url='https://www.ncalb.com/wp-admin/admin-ajax.php',
                formdata=self.data,
                callback=self.get_data,
                headers=self.headers,
                meta={'pageNum': pageNum},
            )


