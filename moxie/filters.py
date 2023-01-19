import django_filters
from moxie.models import Transaction


class ExpensesFilter(django_filters.FilterSet):
    class Meta:
        model = Transaction
        exclude = []
