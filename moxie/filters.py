import django_filters
from django.utils.translation import gettext as _
from moxie.models import Transaction, Category, User


class ExpensesFilter(django_filters.FilterSet):
    category = django_filters.ChoiceFilter(
        field_name='id', label=_('category'), null_value='---', choices=Category.get_categories
    )
    tag = django_filters.CharFilter(field_name='tag', label=_('tag'))
    note = django_filters.CharFilter(field_name='note', label=_('note'))
    amount = django_filters.NumericRangeFilter(field_name='amount', label=_('amount'))
    date = django_filters.DateFromToRangeFilter(field_name='date', label=_('date'))

    class Meta:
        model = Transaction
        fields = []
