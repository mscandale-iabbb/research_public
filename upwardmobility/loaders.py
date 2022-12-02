from scrapy.loader import ItemLoader
from itemloaders.processors import Join, MapCompose, Compose
from builtins import str
from w3lib.html import remove_tags
from .utils import format_text, replace_nbsp

class CompanyLoader(ItemLoader):
	default_input_processor = MapCompose(format_text, remove_tags, str.strip, replace_nbsp)
	default_output_processor = Compose(Join('\n'), str.strip)
	requested_fields = set()

	def add_value(self, field_name, value, *processors, **kwargs):
		self.requested_fields.add(field_name)
		return super().add_value(field_name, value, *processors, **kwargs)

	def add_xpath(self, field_name, xpath, *processors, **kwargs):
		self.requested_fields.add(field_name)
		return super().add_xpath(field_name, xpath, *processors, **kwargs)

	def add_css(self, field_name, css, *processors, **kwargs):
		self.requested_fields.add(field_name)
		return super().add_css(field_name, css, *processors, **kwargs)