import django_filters
from django.utils.translation import gettext as _
from moxie.models import Transaction, Category


def get_category_queryset(request):
    return Category.objects\
        .filter(user_owner=1, parent__isnull=False).order_by('name')\
        .all()


class ExpensesFilter(django_filters.FilterSet):
    amount__gte = django_filters.NumberFilter(field_name='amount', label=_('Minimum amount'))
    amount__lte = django_filters.NumberFilter(field_name='amount', label=_('Maximum amount'))
    category = django_filters.ModelChoiceFilter(
        field_name='category', label=_('category'), queryset=get_category_queryset
    )
    tag = django_filters.CharFilter(field_name='tag', label=_('tag'))
    note = django_filters.CharFilter(field_name='note', label=_('note'))
    date = django_filters.DateRangeFilter(field_name='date', label=_('date'))

    class Meta:
        model = Transaction
        fields = []
