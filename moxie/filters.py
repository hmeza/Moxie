import django_filters
from django.utils.translation import gettext as _
from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from moxie.models import Transaction, Category
from django.forms import Select, TextInput


def get_category_queryset(request):
    user = request.user if request else None
    return Category.objects\
        .filter(user_owner=user, parent__isnull=False).order_by('name')\
        .all()


class ExpensesFilter(django_filters.FilterSet):
    amount__gte = django_filters.NumberFilter(field_name='amount', label=_('Minimum amount'))
    amount__lte = django_filters.NumberFilter(field_name='amount', label=_('Maximum amount'))
    category = django_filters.ModelChoiceFilter(
        field_name='category', label=_('category'), queryset=get_category_queryset,
        widget=Select(attrs={'class': 'select form-control'})
    )
    tag = django_filters.CharFilter(field_name='tag', label=_('tag'))
    note = django_filters.CharFilter(field_name='note', label=_('note'), lookup_expr='icontains')
    date = django_filters.DateFromToRangeFilter(
        field_name='date', label=_('date'),
        widget=django_filters.widgets.RangeWidget(attrs={'type': 'date'})
    )

    class Meta:
        model = Transaction
        fields = []

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.form.helper = FormHelper()
        self.form.helper.form_id = 'id-filterForm'
        self.form.helper.form_class = 'form-horizontal'
        self.form.helper.label_class = 'col-lg-3 col-md-3 col-sm-3 col-xs-5'
        self.form.helper.field_class = 'col-lg-9 col-md-9 col-sm-9 col-xs-7'
        # self.form.helper.form_method = 'post'
        # self.form.helper.form_action = ''

        self.form.helper.add_input(Submit('submit', _('filter_submit')))
