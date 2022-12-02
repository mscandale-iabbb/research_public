# Define here the models for your scraped items
#
# See documentation in:
# https://docs.scrapy.org/en/latest/topics/items.html

import scrapy


class UpwardMobilityItem(scrapy.Item):
    # define the fields for your item here like:
    # BBB name

    source = scrapy.Field()
    company_url = scrapy.Field()
    '''
        Required 
        Most prominent business name known to the public, not necessarily officially registered/incorporated name
    '''
    business_name = scrapy.Field()

    '''
        Required
        If there are two address lines, combine into one line.  Physical location if available (PO Box only if not).
    '''
    street_address = scrapy.Field()

    # Required 
    city = scrapy.Field()

    '''
        Required
        Must be 2-character abbreviation 
    '''
    state = scrapy.Field()

    '''
        Required 
        Can be 5-digit or Zip+4 
    '''
    postal_code = scrapy.Field()

    '''
        Required 
        Only if not USA, otherwise assumed to be USA 
    '''
    country = scrapy.Field()

    '''
        Important
        Main or most prominent phone number, requires at least 10 digits, punctuation/spacing unimportant, don't include extensions
    '''
    phone = scrapy.Field()

    '''
        Important
        Main or most prominent email address known to the public
    '''
    email = scrapy.Field()

    '''
        Important
        Don't worry as much about portions like "http" or "www" which we will clip
    '''
    website = scrapy.Field()

    # Important
    industry_type = scrapy.Field()

    '''
        Important
        Based on 2022 official NAICS categories
    '''
    NAICS = scrapy.Field()

    '''
        Optional 
        Principal, owner, most prominent, or otherwise whoever is available
    '''
    prename = scrapy.Field()

    # Optional 
    name = scrapy.Field()

    first_name = scrapy.Field()

    # Optional 
    middle_name = scrapy.Field()

    # Optional 
    last_name = scrapy.Field()

    # Optional 
    postname = scrapy.Field()

    # Optional 
    title = scrapy.Field()
    
    # Optional 
    fax = scrapy.Field()
    
    # Required 
    license_type = scrapy.Field()

    '''
        Required 
        Values could be active, inactive, expired, revoked, etc. but boil down to active or inactive
    '''    
    license_status = scrapy.Field()

    # Important
    license_number = scrapy.Field()

    # Important
    license_expiration_date = scrapy.Field()

    # Optional 
    license_issue_date = scrapy.Field()

    # Optional 
    secondary_business_name = scrapy.Field()

    # Optional 
    secondary_phone = scrapy.Field()

    # Optional 
    secondary_email = scrapy.Field()

    # Optional 
    date_business_started = scrapy.Field()

    # Optional 
    number_of_employees = scrapy.Field()

    # Optional 
    additional_NAICS = scrapy.Field()

