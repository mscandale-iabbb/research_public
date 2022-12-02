from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *

class LouisianaSpider(scrapy.Spider):
    name = 'louisiana'
    allowed_domains = ['lslbc.louisiana.gov']
    start_urls = ['https://lslbc.louisiana.gov/wp-admin/admin-ajax.php?action=api_actions&api_action=parishes']

    def parse(self, response):
        for r in json.loads(response.text)["results"]:
            url = f"https://lslbc.louisiana.gov/wp-admin/admin-ajax.php?api_action=advanced&contractor_parish={r['id']}&action=api_actions"
            yield scrapy.Request(url, callback=self.get_contractors)

    def get_contractors(self, response):
        for contractor in json.loads(response.text)["results"]:
            url = f"https://lslbc.louisiana.gov/wp-admin/admin-ajax.php?action=api_actions&api_action=company_details&company_id={contractor['id']}"
            yield scrapy.Request(url, callback=self.parse_profile)

    def parse_profile(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        c = json.loads(response.text)
        l.add_value('source', 'Louisiana Contractors')
        l.add_value('company_url', f"https://lslbc.louisiana.gov/contractor-search/contractor-details/{c['id']}/")
        l.add_value('business_name', c.get('company_name', ''))
        l.add_value('street_address', c.get('mailing_address2', ''))
        l.add_value('city', c.get('mailing_city', ''))
        l.add_value('state', c.get('mailing_state', ''))
        l.add_value('postal_code', c.get('mailing_zip', ''))
        l.add_value('country', 'USA')
        l.add_value('phone', c.get('phone_number', ''))
        l.add_value('email', c.get('email_address', ''))
        l.add_value('website', c.get('website', ''))
        l.add_value('fax', c.get('fax_number', ''))
        licenses = c.get('licenses', [])
        if licenses:
            l.add_value('license_type', licenses[0].get('type', ''))
            l.add_value('license_status', licenses[0].get('status', ''))
            l.add_value('license_number', licenses[0].get('license_number', ''))
            l.add_value('license_expiration_date', licenses[0].get('expiration_date', ''))
            l.add_value('license_issue_date', licenses[0].get('first_issued', ''))

        classifications = c.get('classifications', [])
        if classifications:
            l.add_value('industry_type', classifications[0].get('classification', ''))
            full_name = classifications[0].get('qualifying_party', '')
            if full_name:
                prename, postname, first_name, last_name, middle_name = parse_name(full_name)
                l.add_value('prename', prename)
                l.add_value('postname', postname)
                l.add_value('first_name', first_name)
                l.add_value('last_name', last_name)
                l.add_value('middle_name', middle_name)

        return l.load_item()
        
