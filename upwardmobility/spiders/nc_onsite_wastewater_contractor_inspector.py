from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
import pdftotext


class NcOnsiteWastewaterContractorInspectorSpider(scrapy.Spider):
    name = 'nc_onsite_wastewater_contractor_inspector'
    allowed_domains = ['ncowcicb.info']
    start_urls = ['https://ncowcicb.info/wp-content/uploads/2022/12/11-30-22-NCOWCICB-CERTIFIED-LIST-BY-LAST-NAME.pdf']

    def parse(self, response):
        pdf_source = io.BytesIO(response.body)
        pages = pdftotext.PDF(pdf_source)
        tables = []
        for i, page in enumerate(pages):
            tables.append([list(filter(None, _.split('  '))) for _ in pages[i].split('\n')[1:-4]])

        for table in tables:
            for cnt, row in enumerate(table):
                if row[0] == 'County':
                    continue
                l = CompanyLoader(item=UpwardMobilityItem(), response=response)
                l.add_value('source', 'NC Onsite Wastewater Contractor Inspector Certification  Board')
                last_name = row[1].strip()
                if len(last_name.split(' ')) > 3:
                    first_name = ' '.join((last_name.split(' ')[2:])).strip()
                    last_name = ' '.join((last_name.split(' ')[:2])).strip()
                    business_name = row[2]
                else:
                    first_name = row[2].strip()
                    business_name = row[3]

                phone = row[-1].strip()
                if len(phone.split(' ')) > 2:
                    city = phone.split('(')[0].strip()
                    phone = '('+phone.split('(')[-1].strip()
                else:
                    city = row[-2]
                if len(first_name.split(' ')) > 2:
                    business_name = ' '.join(first_name.split(' ')[2:]).strip()
                    first_name = ' '.join(first_name.split(' ')[:2]).strip()
                city = city.split('I ')[-1].strip()
                if ' JR' in last_name:
                    l.add_value('postname', 'JR')
                    l.add_value('last_name', last_name.replace(' JR', '').strip())
                else:
                    l.add_value('last_name', last_name)
                if len(first_name.split(' ')) == 2:
                    l.add_value('first_name', first_name.split(' ')[0])
                    l.add_value('middle_name', first_name.split(' ')[1])
                else:
                    l.add_value('first_name', first_name)
                l.add_value('business_name', business_name)
                l.add_value('phone', phone)
                l.add_value('city', city)
                yield l.load_item()

