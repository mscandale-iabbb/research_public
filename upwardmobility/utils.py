import collections
import json
import os
import re
import csv
import io
import string
import time
import scrapy
import requests

import lxml.etree
from lxml.html import fromstring


def format_text(text):
    text = "".join(text)
    text = re.sub('[^\S\n ]+', '', text)
    text = re.sub(' +', ' ', text)
    text = re.sub('(\n )+', '\n', text)
    text = re.sub('\n+', '\n', text)
    text = re.sub('&amp;', '&', text)
    return text


def replace_nbsp(text):
    return text and text.replace(u'\xa0', u' ')


def get_etree(html: str, url: str):
    etree = fromstring(html)
    etree.make_links_absolute(url)
    return etree


def parse_name(full_name: str):
    prename = postname = first_name = last_name = middle_name = ''
    prename_list = ['Mrs.', 'Mr.', 'Ms.', 'Dr.']
    postname_list = ['jr.', 'Jr.', 'JR.', 'DC']
    for pre_name in prename_list:
        if pre_name in full_name:
            prename = pre_name
            full_name = full_name.replace(pre_name, '').strip()
            break

    for post_name in postname_list:
        if post_name in full_name:
            postname = post_name
            full_name = full_name.split(post_name)[0].strip().rstrip(',')
            break

    name_list = full_name.split(' ')
    if len(name_list) == 2:
        first_name = name_list[0].rstrip(',')
        last_name = name_list[1].rstrip(',')
    elif len(name_list) == 3:
        first_name = name_list[0].rstrip(',')
        middle_name = name_list[1].rstrip(',')
        last_name = name_list[2].rstrip(',')

    return prename, postname, first_name, last_name, middle_name


def decodeEmail(self, e):
    de = ""
    k = int(e[:2], 16)
    for i in range(2, len(e)-1, 2):
        de += chr(int(e[i:i+2], 16)^k)
    return de

def get_post_data(response):
    post_data = {}
    for tag in response.xpath('//input[@type="hidden"]'):
        label = tag.xpath('@name').extract_first()
        value = tag.xpath('@value').extract_first()
        post_data[label] =  value if value else ''
    return post_data