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
