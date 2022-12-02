from upwardmobility.items import UpwardMobilityItem
from upwardmobility.loaders import CompanyLoader
from upwardmobility.utils import *


class FloridahealthfinderSpider(scrapy.Spider):
    name = 'floridahealthfinder'
    limit = 50
    def start_requests(self):
        headers = {
            'Accept': '*/*',
            'Connection': 'keep-alive',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin': 'https://www.floridahealthfinder.gov',
            'Referer': 'https://www.floridahealthfinder.gov/facilitylocator/FacilitySearch.aspx',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
            'X-MicrosoftAjax': 'Delta=true',
            'X-Requested-With': 'XMLHttpRequest'
        }
        url = 'https://www.floridahealthfinder.gov/facilitylocator/FacilitySearch.aspx'
        data = 'ctl00%24mainContentPlaceHolder%24ScriptManager1=ctl00%24mainContentPlaceHolder%24ScriptManager1%7Cctl00%24mainContentPlaceHolder%24FacilityType&__EVENTTARGET=ctl00%24mainContentPlaceHolder%24FacilityType&__EVENTARGUMENT=&__LASTFOCUS=&__VIEWSTATE=%2FwEPDwULLTEzNzg3NTEzNjAPZBYCZg9kFgQCBQ9kFgICAQ8WAh4JaW5uZXJodG1sBRlGYWNpbGl0eS9Qcm92aWRlciBMb2NhdG9yZAIHD2QWAgIBD2QWBgIJDxAPFggeFEFwcGVuZERhdGFCb3VuZEl0ZW1zZx4ORGF0YVZhbHVlRmllbGQFCkNsaWVudENvZGUeDURhdGFUZXh0RmllbGQFDEZhY2lsaXR5VHlwZR4LXyFEYXRhQm91bmRnZBAVJQwtLSBTZWxlY3QgLS0JQUxMIFRZUEVTD0Fib3J0aW9uIENsaW5pYxVBZHVsdCBEYXkgQ2FyZSBDZW50ZXIWQWR1bHQgRmFtaWx5IENhcmUgSG9tZRpBTEYgQ29yZSBUcmFpbmluZyBQcm92aWRlchpBbWJ1bGF0b3J5IFN1cmdpY2FsIENlbnRlchhBc3Npc3RlZCBMaXZpbmcgRmFjaWxpdHkMQmlydGggQ2VudGVyE0NsaW5pY2FsIExhYm9yYXRvcnk5Q29tbXVuaXR5IE1lbnRhbCBIZWFsdGggLSBQYXJ0aWFsIEhvc3BpdGFsaXphdGlvbiBQcm9ncmFtMENvbXByZWhlbnNpdmUgT3V0cGF0aWVudCBSZWhhYmlsaXRhdGlvbiBGYWNpbGl0eURDcmlzaXMgU3RhYmlsaXphdGlvbiBVbml0LyBTaG9ydCBUZXJtIFJlc2lkZW50aWFsIFRyZWF0bWVudCBGYWNpbGl0eR5FbmQtU3RhZ2UgUmVuYWwgRGlzZWFzZSBDZW50ZXIeRm9yZW5zaWMgVG94aWNvbG9neSBMYWJvcmF0b3J5EkhlYWx0aCBDYXJlIENsaW5pYxxIZWFsdGggQ2FyZSBDbGluaWMgRXhlbXB0aW9uGUhlYWx0aCBDYXJlIFNlcnZpY2VzIFBvb2wZSG9tZSBmb3IgU3BlY2lhbCBTZXJ2aWNlcxJIb21lIEhlYWx0aCBBZ2VuY3kcSG9tZSBIZWFsdGggQWdlbmN5IEV4ZW1wdGlvbh9Ib21lIE1lZGljYWwgRXF1aXBtZW50IFByb3ZpZGVyH0hvbWVtYWtlciBhbmQgQ29tcGFuaW9uIFNlcnZpY2UHSG9zcGljZQhIb3NwaXRhbDtJbnRlcm1lZGlhdGUgQ2FyZSBGYWNpbGl0eSBmb3IgdGhlIERldmVsb3BtZW50YWxseSBEaXNhYmxlZA5OdXJzZSBSZWdpc3RyeQxOdXJzaW5nIEhvbWUcT3JnYW4gQW5kIFRpc3N1ZSBQcm9jdXJlbWVudA5Qb3J0YWJsZSBYLVJheSlQcmVzY3JpYmVkIFBlZGlhdHJpYyBFeHRlbmRlZCBDYXJlIENlbnRlchVSZWhhYmlsaXRhdGlvbiBBZ2VuY3k5UmVzaWRlbnRpYWwgVHJlYXRtZW50IENlbnRlciBmb3IgQ2hpbGRyZW4gYW5kIEFkb2xlc2NlbnRzHlJlc2lkZW50aWFsIFRyZWF0bWVudCBGYWNpbGl0eRNSdXJhbCBIZWFsdGggQ2xpbmljFFNraWxsZWQgTnVyc2luZyBVbml0HFRyYW5zaXRpb25hbCBMaXZpbmcgRmFjaWxpdHkVJQwtLSBTZWxlY3QgLS0DQUxMBDEzICAEMTIgIAQ1MiAgAjEwBDE0ICAEMTEgIAQxNSAgBDI2ICAENDUgIAQxNiAgBDE3ICAEMTggIAQzNyAgBDc0ICAENzUgIAQ2NyAgBDIxICAEMTkgIAI2OAQ1NiAgBDM5ICAEMjIgIAQyMyAgBDI1ICAENDIgIAQzNSAgBDQxICAEMzAgIAQyOSAgBDQ3ICAENTcgIAQzMiAgBDMzICAENTQgIAQzNCAgFCsDJWdnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2cWAWZkAi8PEA8WCB8BZx8CBQRDb2RlHwMFBE5hbWUfBGdkEBVEA0FMTAdBbGFjaHVhBUJha2VyA0JheQhCcmFkZm9yZAdCcmV2YXJkB0Jyb3dhcmQHQ2FsaG91bglDaGFybG90dGUGQ2l0cnVzBENsYXkHQ29sbGllcghDb2x1bWJpYQZEZXNvdG8FRGl4aWUFRHV2YWwIRXNjYW1iaWEHRmxhZ2xlcghGcmFua2xpbgdHYWRzZGVuCUdpbGNocmlzdAZHbGFkZXMER3VsZghIYW1pbHRvbgZIYXJkZWUGSGVuZHJ5CEhlcm5hbmRvCUhpZ2hsYW5kcwxIaWxsc2Jvcm91Z2gGSG9sbWVzDEluZGlhbiBSaXZlcgdKYWNrc29uCUplZmZlcnNvbglMYWZheWV0dGUETGFrZQNMZWUETGVvbgRMZXZ5B0xpYmVydHkHTWFkaXNvbgdNYW5hdGVlBk1hcmlvbgZNYXJ0aW4KTWlhbWktRGFkZQZNb25yb2UGTmFzc2F1CE9rYWxvb3NhCk9rZWVjaG9iZWUGT3JhbmdlB09zY2VvbGEKUGFsbSBCZWFjaAVQYXNjbwhQaW5lbGxhcwRQb2xrBlB1dG5hbQpTYW50YSBSb3NhCFNhcmFzb3RhCFNlbWlub2xlCVN0LiBKb2hucwlTdC4gTHVjaWUGU3VtdGVyCFN1d2FubmVlBlRheWxvcgVVbmlvbgdWb2x1c2lhB1dha3VsbGEGV2FsdG9uCldhc2hpbmd0b24VRANBTEwBMQEyATMBNAE1ATYBNwE4ATkCMTACMTECMTICMTQCMTUCMTYCMTcCMTgCMTkCMjACMjECMjICMjMCMjQCMjUCMjYCMjcCMjgCMjkCMzACMzECMzICMzMCMzQCMzUCMzYCMzcCMzgCMzkCNDACNDECNDICNDMCMTMCNDQCNDUCNDYCNDcCNDgCNDkCNTACNTECNTICNTMCNTQCNTcCNTgCNTkCNTUCNTYCNjACNjECNjICNjMCNjQCNjUCNjYCNjcUKwNEZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dkZAI5DxAPFggfAWcfAgULRmllbGRPZmZpY2UfAwULRmllbGRPZmZpY2UfBGdkEBUMA0FMTAIwMQIwMgIwMwIwNAIwNQIwNgIwNwIwOAIwOQIxMAIxMRUMA0FMTAIwMQIwMgIwMwIwNAIwNQIwNgIwNwIwOAIwOQIxMAIxMRQrAwxnZ2dnZ2dnZ2dnZ2dkZBgBBR5fX0NvbnRyb2xzUmVxdWlyZVBvc3RCYWNrS2V5X18WCAUjY3RsMDAkbWFpbkNvbnRlbnRQbGFjZUhvbGRlciRjaGtDUkgFI2N0bDAwJG1haW5Db250ZW50UGxhY2VIb2xkZXIkQ291bnR5BShjdGwwMCRtYWluQ29udGVudFBsYWNlSG9sZGVyJEZpZWxkT2ZmaWNlBShjdGwwMCRtYWluQ29udGVudFBsYWNlSG9sZGVyJGNoa0Jha2VyQWN0BRljdGwwMCRGb290ZXIyMDE3JHJiWWVzT25lBRhjdGwwMCRGb290ZXIyMDE3JHJiTm9PbmUFGWN0bDAwJEZvb3RlcjIwMTckcmJZZXNUd28FGGN0bDAwJEZvb3RlcjIwMTckcmJOb1R3b%2F%2F5BOMUOGhNbTYEbw3TWh8xq9W4&__VIEWSTATEGENERATOR=3998EB1C&__EVENTVALIDATION=%2FwEdAIsBhGNf4ngRHxN7C%2BJE%2FKiw0l4ijSkA9W5qqhL3O6arrU3%2BR8iQw4Ni42Sm0On43sRIOfye5FnjagAAG3JVqHxCND6Ozwc3w9qNQo77Exi%2BqKkBlHeZBgjDTfL1jbOVatk4fLY7YobAw95PuFiJMn%2Bz%2Bh7%2Fld4Wjrn8kHn47QSoDGUXGKkWYe2p8DGzyQlDTaD9zvjf0xY4Iy5tLpyVL%2FIsU6FPvqJAptF63REY6BlSgks%2FSUeGWLIRZVjlZA9osM2O%2FtmJ9V43Wj7N%2F42I3KS%2B8JX475QV05%2FbQ8Z4XbGnfObi8FMpDQQgN4hFmYtFkcQ3b3nW9AhVIeEzXGxNa%2FaS5pRpeFH%2B7puoU0LUWhbI4%2FIUtX3W3rxIva0MPO%2BT5kAo1GeBW6Eo42K7ZPJ1AkUVQeKMzrXTHhdbig%2BGeorPb4PzzQbSGq6QUfPOI4rydw5xmf7wRGHmcouMv7ZpjTs7StvbfxvMKFtfXDekJGaB4AUBD8ODRJH7TkXXT2oN3nwHVW1SB06zhPl3ZZDwse1%2FLB5tOa8xUJtiLTCkWP69d4wsQG9hSt9xGZw161oKwL0Iat%2BPHFfclS0OV75AsfiuOYzdvluqDhXIafmYMHqfoyqzzVEjOzLrNH7943BuQj8JJCbEMsPxhry%2FX85reTtaw4CLpajORySnVacXl%2F%2BuAKBZQcGZ2WaMDsUAfsPNZ9zj0aSY0Ze6%2BJCBgJqbgfa3ewblbp2yvvTTxpm%2BvIk%2F1inpBN8MbWa%2FeFlhUa7%2FuIdIIALVSkokMMxpy87vgGYt%2FLVK0WkX2hRsELXm%2FZqovgSb3Xm0Fl1z8qnPq6cH7tiD5o5sajqyEz05yMvgFaXEvYaVMYTnSOSXbuVl4KUrwOiuj8XJQj90CuI18iesPDWB%2FXnq%2F9BeAVb4kZUHDvvWMnPgeiFN6JNSNcaY9H5bBIwcEjsZaeHjSkCm51Cp4ZVdodhgMSeWbWqzF6Qjh3sv3pyJb11c9srvRa%2BMdnUsyi23w58GnBuMucZ82ngt%2F4KsTxSC%2BNOlAdeIbfKI7myILHvd0uKr9sbfbQHko0rcVaWxr3N6Kny9nRZj4WSCXNKUwpWgrFtbYTysGzwj3M7yR6ev1EDAMzplcLSukDSpu8Xc2ZZJHRWmNnFroiqgoFulj%2Baeh73wMhXLdyVg19wxe42LZribI2TbxHrtqQgxJEgGsCip2HZ9GXD%2BPON1ivwyHYpOr1Ih71mINnjmkNIEFO8aREv4BupOLbN9qpylu%2FA62Gm7CaKKcum0HczB8wSnq6SlWJIs1HY3uboDrZ%2FY17%2BO%2FI2AjnOaV4BJApNnmPuPYEpeYhHujqyPFTvRGg5OQKe3lxbpok0EoUJVJvLDkxTmk8rjFm05wO71wsijr925m4ffuKUb8E4qNMqs4MMMYpWBG1%2BvYFM5GmC1tm7Oj0bscK8Gjv4J%2FWxoJNAPU0WK1PvNDssmznmp%2BUsnxAKF%2F21pDuuAqbm5CiXIRV54eTINf7aRldOuJcL1o1jxpye%2BQFLZ6a5HfUkVov05tkuZdGl2klkj3O8j%2Fyn4PUu3BSYdQW84mKRJ%2B4VsopSSvBgntev6aFaZs8wPCc5QZY5yaVMDsMPPhMifEMRu3t2yXyj6OdEtnT%2Bv6ZyLIvA1WIq2LY%2BVOwGHSW2Ph8t6XsMB6GhXYlmIk0RztVAghmgvTCAzOAcWE5pLkHVMf53UE34W7JciKGU3UrlW3ImDbRYeszG3uAzemgZ2PT4ViTb%2BPdu%2Bk6uBEdNDZkyzuOu4%2Bq%2BtZvlydUwFN1YJTmMZ%2BbJB%2FZpV4IUGSkos880i4KrIujZegveSWkv85OWRBMvoifFfkpzWXnLQ3yqPBSSjvFU%2Bsb3MQINwirCRpXWzjLwyg3jvx9I1V8dNHRmT5YhZ7lRaTZsvp%2B0bLc5MQfJU9PwP5MfTDi45fsHhiPFjMyYb1d6bzPqaevcTi61jbDDOIk1rsBhrePfVKJ0hWWsbK5etIfoiedpWwCELDQO6ffY%2FvfGPCg4pKEI%2FOprxvZ0f%2BJqY7KyLDvQPOzwHBw3vFQo2RWDm7MJ4Ud2laknz0H0AGGLdTPajqfrA8RGrFh9aP6ZmURxh0ZDaKydhAYO0m2lvVEyvk66reKta8XFT%2Fs0dNxk9ItI7TN8eh7q7SdkClJNag9rt45b40iU%2B2jdAU%2BgtRy03o%2FjVP5BeDWGR%2B5xwEeGGy4FCCR4A7ejhdikWpqmg48CK1BGHKQcgFkcFgnjjhK2prpWQM23sqpUpUiLW3y%2Bux3ur8z7GZo2QSrUBr9SKweIz3mF2Brd9Gm0NrGbsrjuq7UxmJUJuZe5rbIdaDH5Od%2BFzyxWhHlEmjstvYASFg7BdyXRAR3YoSQXU1nZRttiWLOOIRhAOlB83Sb4huWqsJTYJ%2Felnox3hofLQlFuTa0qQFNk05ZSJOeiCgnHxlUnOAXVVltJObLeQNLpw1sZtYtWVtS3thbjDfd8urUW3FVNEDoogwchGcVUz%2Fk%2Fa3pZjJvnxd9BphJcBCUID1tH7jEUP9aNTrnVl05BMZA34UhyugtJ48pA14sHRrsR47K21yswmOnSN%2BkVq4oMRacg%2B3pgFh%2B1q7OLUk2PIgzCv6lx5J479oRL%2FBHPEZBVljzRlrGnSYsL85K2LKcZx%2F9C68RNW3hAmA4lQUQnLr3kE%2FwzvMazeTU5JpE%2BjmYglOi95d89oL%2BIb4S01k1e459aE2nFC4SxO2TVniqxweEBbcDFpKnrJg1XACw18NdVhhQIB3%2Bt%2FRdKXnsw6yWZp38LT7WFlXWEZhWzvlozti0aTdupnU%2Fn1xIj9Lc8q1lqTa9UjOjH%2BPiCNPXiWC5Lb1BT170jg5aF%2F0WyEpwEzkLnM9t9suviRjPMgVyfgWNWAMuXK0JLJd7Yo21l%2BQTefsaWAfm4zPn7J47B2BcOHVtuKLyhVpTrShv0YT5wwKcPYvGxXQkpZF7Awo54kle6N4mWGZ%2Fs2jO3YRPm1lj2vE7Khg2JE&ctl00%24mainContentPlaceHolder%24FacilityType=ALL&ctl00%24mainContentPlaceHolder%24ddlLicStatus=&ctl00%24mainContentPlaceHolder%24FacilityName=&ctl00%24mainContentPlaceHolder%24FacilityAddress=&ctl00%24mainContentPlaceHolder%24City=FLORIDA%20CITY&ctl00%24mainContentPlaceHolder%24Zipcode=&ctl00%24mainContentPlaceHolder%24County=ALL&ctl00%24mainContentPlaceHolder%24AhcaNumber=&ctl00%24mainContentPlaceHolder%24FieldOffice=ALL&ctl00%24mainContentPlaceHolder%24LicenseNumber=&ctl00%24mainContentPlaceHolder%24Administrator=&ctl00%24mainContentPlaceHolder%24OwnershipType=ALL&ctl00%24mainContentPlaceHolder%24emergencyActionsList=ALL&ctl00%24mainContentPlaceHolder%24CollapsiblePanelExtender1_ClientState=true&__ASYNCPOST=true&'
        yield scrapy.Request(url=url, method='POST', body=data, headers=headers)

    def parse(self, response):
        sessionid = response.headers.getlist('Set-Cookie')[0].decode("utf-8").split(";")[0].split('=')[1]
        cookies = {
            'ASP.NET_SessionId': str(sessionid),
        }

        headers = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language': 'en-US,en;q=0.9',
            'Cache-Control': 'max-age=0',
            'Connection': 'keep-alive',
            'Referer': 'https://www.floridahealthfinder.gov/facilitylocator/FacilitySearch.aspx',
            'Upgrade-Insecure-Requests': '1',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36'
        }
        url = 'https://www.floridahealthfinder.gov/facilitylocator/ListFacilities.aspx'
        yield scrapy.Request(url, headers=headers, cookies=cookies, callback=self.get_parse_list)

    def get_parse_list(self, response):
        links = response.xpath('//div[@id="facilityProfiles"]/a')
        for link in links:
            url = response.urljoin(link.xpath('./@href').extract_first('').strip())
            if self.limit > 0:
                yield scrapy.Request(url, callback=self.get_parse_detail)
                self.limit -= 1


    def get_parse_detail(self, response):
        l = CompanyLoader(item=UpwardMobilityItem(), response=response)
        address = response.xpath('//span[@id="ctl00_mainContentPlaceHolder_lblStrCityStateZip"]/text()').extract_first('').strip()
        city = address.split(', ')[0]
        state = address.split(', ')[1].split(' ')[0]
        postal_code = address.split(', ')[1].split(' ')[1]
        l.add_value('source', 'FloridaHealthFinder')
        l.add_value('company_url', response.url)
        l.add_xpath('business_name', '//span[@id="ctl00_titleParagraphPlaceHolder_lblFacilityName"]/span/text()')
        l.add_xpath('street_address', '//span[@id="ctl00_mainContentPlaceHolder_lblStrAddress1"]/text()')
        l.add_value('city', city)
        l.add_value('state', state)
        l.add_value('postal_code', postal_code)
        l.add_value('country', 'USA')
        l.add_xpath('phone', '//span[@id="ctl00_mainContentPlaceHolder_lblStreetPhone"]/text()')
        l.add_xpath('license_number', '//span[@id="ctl00_mainContentPlaceHolder_lblLicenseNumber"]/text()')
        l.add_xpath('license_issue_date', '//span[@id="ctl00_mainContentPlaceHolder_lblLicenseEffective"]/text()')
        l.add_xpath('license_expiration_date', '//span[@id="ctl00_mainContentPlaceHolder_lblLicenseExpires"]/text()')
        l.add_xpath('license_status', '//span[@id="ctl00_mainContentPlaceHolder_lblLicenseType"]/text()')
        l.add_xpath('industry_type', '//span[@id="ctl00_mainContentPlaceHolder_lblFacilityType"]/text()')
        full_name = response.xpath('//span[@id="ctl00_mainContentPlaceHolder_lblAdminCEO"]/text()').extract_first().strip()
        if 'not available' not in full_name.lower():
            prename, postname, first_name, last_name, middle_name = parse_name(full_name)
            l.add_value('prename', prename)
            l.add_value('postname', postname)
            l.add_value('first_name', first_name)
            l.add_value('last_name', last_name)
            l.add_value('middle_name', middle_name)

        yield l.load_item()
# rm floridahealthfinder.csv; scrapy crawl floridahealthfinder -o floridahealthfinder.csv