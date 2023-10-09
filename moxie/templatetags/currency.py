from django import template

register = template.Library()


@register.filter
def currency_symbol(string):
    if string == 'eur':
        return '&euro;'
    elif string == 'gbp':
        return '&pound;'
    elif string == 'usd':
        return '&dollar;'
    elif string == 'nan':
        return '?'
    return '💱'
