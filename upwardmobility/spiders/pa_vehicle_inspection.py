from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *
import pdftotext


class PaVehicleInspectionSpider(scrapy.Spider):
    name = 'pa_vehicle_inspection'
    allowed_domains = ['dot.state.pa.us']
    start_urls = ['https://www.dot.state.pa.us/public/dvspubsforms/BMV/BMV%20Publications/New%20Safety%20Station.pdf']

    def parse(self, response):
        # Load pdf to memory
        pdf_source = io.BytesIO(response.body)
        pages = pdftotext.PDF(pdf_source)
        tables = []
        for i, page in enumerate(pages):
            tables.append([list(filter(None, _.split('  '))) for _ in pages[i].split('\n')[1:-4]])

        for table in tables:
            seen_start = False
            before_seen = False
            after_seen = False
            for cnt, row in enumerate(table):
                if row and row[0] == 'trucks':
                    seen_start = True
                    continue
                elif row and 'Page' in row[0]:
                    break
                if seen_start:
                    if len(row) == 1 and not after_seen:
                        before_seen = True
                        continue
                    if after_seen:
                        after_seen = False
                        continue
                    if before_seen:
                        try:
                            check = table[cnt+1][0]
                        except:
                            continue
                        after_seen = True
                        before_seen = False
                        try:
                            phone = row[3]
                        except:
                            pass
                        if ',' in row[2]:
                            license_number = row[1].strip()
                            business_name = f"{table[cnt-1][0]}{table[cnt+1][0]}"
                            street_address, city, state, postal_code = self.get_address(row[2])
                        else:
                            license_number = row[2].strip()
                            business_name = row[1]
                            if len(row) == 3:
                                business_name = ' '.join(row[1].split(' ')[:-1]).strip()
                                license_number = row[1].split(' ')[-1].strip()
                                phone = row[2].split(' ')[-1]
                            elif row[3] == 'X':
                                business_name = ' '.join(row[1].split(' ')[:-1]).strip()
                                license_number = row[1].split(' ')[-1].strip()
                                phone = row[2].split(' ')[-1]
                            if len(table[cnt+1][0].split(' ')) == 2:
                                street_address = ' '.join(table[cnt-1][0].split(',')[:-1]).strip()
                                city = table[cnt-1][0].split(',')[-1]
                                state = table[cnt+1][0].split(' ')[0]
                                postal_code = table[cnt+1][0].split(' ')[1]
                            else:
                                postal_code = table[cnt+1][0]
                                city_state = table[cnt-1][0].split(',')[-1]
                                city = ' '.join(city_state.split(' ')[:-1]).strip()
                                state = city_state.split(' ')[-1].strip()
                                street_address = ' '.join(table[cnt-1][0].split(',')[:-1]).strip()
                        if phone == 'X':
                            continue
                        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
                        l.add_value('source', 'PA Vehicle Inspection')
                        l.add_value('business_name', business_name.strip())
                        l.add_value('phone', phone)
                        l.add_value('license_number', license_number)
                        l.add_value('street_address', street_address.strip())
                        l.add_value('city', city.strip())
                        l.add_value('state', state.strip())
                        l.add_value('postal_code', postal_code.strip())
                        l.add_value('country', 'USA')
                        yield l.load_item()
                        continue

                    l = CompanyLoader(item=UpwardMobilityItem(), response=response)
                    l.add_value('source', 'PA Vehicle Inspection')
                    business_name = row[1]
                    try:
                        license_number = row[2]
                    except:
                        continue
                    if len(row) > 4 and row[4].strip() == 'X':
                        phone = row[3].split(' ')[-1]
                        addresses = ' '.join(row[3].split(' ')[:-1])
                    elif len(row) == 4:
                        phone = row[3].split(' ')[-1]
                        addresses = ' '.join(row[3].split(' ')[:-1])
                    elif len(row) == 3:
                        business_name = ' '.join(row[1].split(' ')[:-1]).strip()
                        license_number = row[1].split(' ')[-1].strip()
                        phone = row[2].split(' ')[-1]
                        addresses = ' '.join(row[2].split(' ')[:-1])
                    else:
                        addresses = row[3]
                        phone = row[4]
                    if len(license_number) > 6:
                        business_name = ' '.join(row[1].split(' ')[:-1]).strip()
                        license_number = row[1].split(' ')[-1].strip()
                        phone = row[3]
                        addresses = row[2]

                    if phone.strip() == 'X':
                        continue
                    l.add_value('business_name', business_name)
                    l.add_value('phone', phone.strip())
                    l.add_value('license_number', license_number)
                    street_address, city, state, postal_code = self.get_address(addresses)
                    l.add_value('street_address', street_address.strip())
                    l.add_value('city', city.strip())
                    l.add_value('state', state.strip())
                    l.add_value('postal_code', postal_code.strip())
                    l.add_value('country', 'USA')
                    yield l.load_item()

    def get_address(self, row):
        street_address = city = state = postal_code = ''
        city_state_zip = row.split(',')[-1].strip()
        street_address = ' '.join(row.split(',')[:-1]).strip()
        city = ' '.join(city_state_zip.split(' ')[:-2]).strip()
        state = city_state_zip.split(' ')[-2].strip()
        postal_code = city_state_zip.split(' ')[-1].strip()
        
        return street_address, city, state, postal_code