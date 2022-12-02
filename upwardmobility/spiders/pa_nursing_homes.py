from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class PaNursingHomesSpider(scrapy.Spider):
    name = 'pa_nursing_homes'
    allowed_domains = ['sais.health.pa.gov']
    start_urls = ['https://sais.health.pa.gov/CommonPOC/Content/PublicWeb/NHInformation2.asp']
    custom_settings = { 
        'FEED_URI': 'pa_nursing_homes.csv',
        'FEED_FORMAT': 'csv',
        'FEED_EXPORT_FIELDS': [
            'source',
            'business_name',
            'street_address',
            'city',
            'state',
            'postal_code',
            'country',
            'phone',
            'type_of_ownership',
            'licensure_status',
            'last_inspection',
            'size_of_facility',
            'number_of_beds',
            'payment_options',
            'nursing_hours_per_resident_per_day',

        ]
    }
    def start_requests(self):
        headers = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        }
        url = 'https://sais.health.pa.gov/CommonPOC/Content/PublicWeb/NHInformation2.asp'
        yield scrapy.Request(url, callback=self.get_nursing_homes, headers=headers)

    def get_nursing_homes(self, response):
        for tag in response.xpath('//table/tr[@align="center"]'):
            item = {}
            item['source'] = 'PA Nursing Homes'
            item['business_name'] = tag.xpath('./td[2]//strong/text()').extract_first()
            text = tag.xpath('./td[2]//strong/following-sibling::text()').extract()
            item['street_address'] = text[0]
            city_state_zip = text[1].split('\xa0')
            item['city'] = city_state_zip[0]
            item['state'] = city_state_zip[1]
            item['postal_code'] = city_state_zip[2]
            item['country'] = 'USA'
            item['phone'] = text[2]
            item['type_of_ownership'] = ''.join(tag.xpath('./td[3]//text()').extract()).strip()
            item['licensure_status'] = ''.join(tag.xpath('./td[4]//text()').extract()).strip()
            item['last_inspection'] = ''.join(tag.xpath('./td[5]//text()').extract()).strip()
            item['size_of_facility'] = ''.join(tag.xpath('./td[6]//text()').extract()).strip()
            item['number_of_beds'] = ''.join(tag.xpath('./td[7]//text()').extract()).strip()
            payment_option = ''.join(tag.xpath('./td[8]//text()').extract()).strip()
            if payment_option == 'Private PaymentMedicareMedicaid':
                payment_option = 'Private Payment, Medicare, Medicaid'
            elif payment_option == 'Private PaymentMedicare':
                payment_option = 'Private Payment, Medicare'
            elif payment_option == 'Private PaymentMedicaid':
                payment_option = 'Private Payment, Medicaid'
            item['payment_options'] = payment_option
            item['nursing_hours_per_resident_per_day'] = ''.join(tag.xpath('./td[9]//text()').extract()).strip()
            yield item

