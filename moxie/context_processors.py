from django.conf import settings
from django.utils.translation import gettext_lazy as _


def debug(context):
    return {'DEBUG': settings.DEBUG}


def js_version(context):
    return {'JS_VERSION': settings.JS_VERSION}


def urls(context):
    _('incomes')
    _('expenses')
    _('stats')
    _('sheets')
    _('users')
    return {'urls': ['incomes', 'expenses', 'stats', 'sheets', 'users']}


from django.utils.text import get_text_list
from django.template.defaulttags import register


@register.filter
def text_list(tags, conjunction):
    tag_list = []
    for tag in tags:
        if tag.tag:
            tag_list.append(tag.tag.name)
    return get_text_list(tag_list, conjunction)
