from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class NcBoardOccupationalTherapySpider(scrapy.Spider):
    name = 'nc_board_occupational_therapy'
    allowed_domains = ['ncbot-online.org']
    start_urls = ['https://ncbot-online.org/ot_verification_new.aspx']
    headers = {
        'authority': 'ncbot-online.org',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'origin': 'https://ncbot-online.org',
        'user-agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
    }

    def parse(self, response):
        for tag in response.xpath('//table[contains(@class, "rgMasterTable")]/tbody/tr'):
            l = CompanyLoader(item=UpwardMobilityItem(), response=response)
            l.add_value('source', 'NC Board Of occupational Therapy')
            l.add_value('last_name', tag.xpath('./td[1]/text()').extract_first())
            firstname = tag.xpath('./td[2]/text()').extract_first()
            if len(firstname.split(' ')) == 2:
                l.add_value('first_name', firstname.split(' ')[0])
                l.add_value('middle_name', firstname.split(' ')[1])
            else:
                l.add_value('first_name', firstname)
            l.add_value('license_number', tag.xpath('./td[3]/a/text()').extract_first())
            l.add_value('license_type', tag.xpath('./td[4]/button/span[2]/text()').extract_first())
            l.add_value('license_issue_date', tag.xpath('./td[5]/text()').extract_first())
            l.add_value('license_expiration_date', tag.xpath('./td[6]/text()').extract_first())
            l.add_value('license_status', tag.xpath('./td[7]/text()').extract_first())
            yield l.load_item()
        
        next_button = response.xpath('//input[@id="ctl00_MainContentPlaceHolder_LicenseRadGrid_ctl00_ctl03_ctl01_NextButton"]/@value').extract_first()
        if '-' not in next_button:
            post_data = get_post_data(response)
            post_data.update({
                'ctl00_RadScriptManager1_TSM': ';;System.Web.Extensions, Version=4.0.0.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35:en-US:5bc44d53-7cae-4d56-af98-205692fecf1f:ea597d4b:b25378d2',
                'ctl00_MainContentPlaceHolder_LastRadTextBox_ClientState': '{"enabled":true,"emptyMessage":"","validationText":"","valueAsString":"","lastSetTextBoxValue":""}',
                'ctl00_MainContentPlaceHolder_LicenseRadTextBox_ClientState': '{"enabled":true,"emptyMessage":"","validationText":"","valueAsString":"","lastSetTextBoxValue":""}',
                'ctl00$MainContentPlaceHolder$LicenseRadGrid$ctl00$ctl03$ctl01$NextButton': '\xa0\xa0>\xa0\xa0',
            })
            yield scrapy.FormRequest(
                url='https://ncbot-online.org/ot_verification_new.aspx',
                formdata=post_data,
                callback=self.parse,
                headers=self.headers,
                dont_filter=True
            )
        