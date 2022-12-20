from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcArboristSpider(scrapy.Spider):
    name = 'nc_arborist'
    allowed_domains = ['treesaregood.org']
    start_urls = ['https://www.treesaregood.org/findanarborist/findanarborist']
    headers = {
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
    }

    def parse(self, response):
        payloads = get_post_data(response)
        for first_letter in string.ascii_lowercase:
            for last_letter in string.ascii_lowercase:
                payloads.update({
                    'dnn$dnnSearch$txtSearch': '',
                    'dnn$ctr437$FindAnArborist$txt_strFirstName': first_letter,
                    'dnn$ctr437$FindAnArborist$txt_strLastName': last_letter,
                    'dnn$ctr437$FindAnArborist$btnNameSearch': 'Search',
                    'dnn$ctr437$FindAnArborist$ddl_strCountry': 'Please select...'
                })

                yield scrapy.FormRequest(
                    url=self.start_urls[0],
                    formdata=payloads,
                    callback=self.get_data,
                    headers=self.headers,
                    meta={'page_num': 1},
                    dont_filter=True
                )

    def get_data(self, response):
        detail_payloads = get_post_data(response)
        user_tags = response.xpath('//table[@class="gridView1"]/tr')
        for tag_idx, tag in enumerate(user_tags):
            if tag_idx == 0:
                continue
            param = tag.xpath('./td[2]/a/@href').extract_first().split("'")[1]
            detail_payloads.update({
                'dnn$dnnSearch$txtSearch': '',
                '__EVENTTARGET': param
            })

            yield scrapy.FormRequest(
                url=self.start_urls[0],
                formdata=detail_payloads,
                callback=self.parse_profile,
                headers=self.headers,
                dont_filter=True
            )

        if len(user_tags) == 102:
            page_num = response.meta['page_num']
            next_payloads = get_post_data(response)
            next_payloads.update({
                'ScriptManager_TSM': ';;System.Web.Extensions, Version=4.0.0.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35:en:ba1d5018-bf9d-4762-82f6-06087a49b5f6:ea597d4b:b25378d2',
                '__EVENTTARGET': 'dnn$ctr437$FindAnArborist$GridViewFindAnArborist',
                '__EVENTARGUMENT': 'Page${}'.format(page_num+1)
            })
            yield scrapy.FormRequest(
                url=self.start_urls[0],
                formdata=next_payloads,
                callback=self.get_data,
                headers=self.headers,
                meta={'page_num': page_num+1},
                dont_filter=True
            )

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        l.add_value('source', 'NC Arborist')
        full_name = response.xpath('//span[@class="ArboristName"]/text()').extract_first()
        if full_name:
            prename, postname, first_name, last_name, middle_name = parse_name(full_name.strip())
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)
        l.add_xpath('business_name', '//span[contains(@id, "lbl_strCompany")]/text()')
        l.add_xpath('phone', '//span[contains(@id, "lbl_strPhone")]/text()')
        l.add_xpath('email', '//span[contains(@id, "lbl_strEmail")]/a/text()')
        l.add_xpath('website', '//span[contains(@id, "lbl_strWebsite")]/a/text()')
        addresses = response.xpath('//span[contains(@id, "lbl_strAddress")]/text()').extract()
        if len(addresses) == 3:
            l.add_value('street_address', addresses[0])
            l.add_value('city', addresses[1].split(',')[0].strip())
            l.add_value('state', addresses[1].split(',')[1].strip().split(' ')[0].strip())
            l.add_value('postal_code', addresses[1].split(',')[1].strip().split(' ')[1].strip())
            country = addresses[2]
            if country.lower() == 'united states':
                l.add_value('country', 'USA')
                return l.load_item()

            
        