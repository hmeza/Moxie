from django.conf import settings


def debug(context):
    return {'DEBUG': settings.DEBUG}


def js_version(context):
    return {'JS_VERSION': settings.JS_VERSION}


def urls(context):
    return {'urls': ['incomes', 'expenses', 'stats', 'sheets', 'users']}
