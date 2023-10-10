import django_filters
from django.utils.translation import gettext as _
from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from moxie.models import Transaction, Category
from django.forms import Select, BooleanField
from django.forms.widgets import HiddenInput


class SubmitLightBlue(Submit):
    def __init__(self, *args, **kwargs):
        kwargs['css_id'] = 'submit-id-filter'
        super().__init__(*args, **kwargs)
        self.field_classes = 'btn btn-info'


class ExpensesFilter(django_filters.FilterSet):
    amount__gte = django_filters.NumberFilter(field_name='amount', label=_('Minimum amount'), lookup_expr="lte")
    amount__lte = django_filters.NumberFilter(field_name='amount', label=_('Maximum amount'), lookup_expr="gte")
    category = django_filters.ModelChoiceFilter(
        field_name='category', label=_('category'), queryset=Category.objects.none(),
        widget=Select(attrs={'class': 'select form-control'})
    )
    tag = django_filters.CharFilter(field_name='tag', label=_('tag'))
    note = django_filters.CharFilter(field_name='note', label=_('note'), lookup_expr='icontains')
    date = django_filters.DateFromToRangeFilter(
        field_name='date', label=_('date'),
        widget=django_filters.widgets.RangeWidget(attrs={'type': 'date'})
    )
    to_excel = BooleanField(widget=HiddenInput())

    class Meta:
        model = Transaction
        fields = []

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.filters['category'].queryset = Category.get_categories_tree(user)
        self.form.helper = FormHelper()
        self.form.helper.form_id = 'id-filterForm'
        self.form.helper.form_class = 'form-horizontal'
        self.form.helper.label_class = 'col-lg-3 col-md-3 col-sm-3 col-xs-5'
        self.form.helper.field_class = 'col-lg-9 col-md-9 col-sm-9 col-xs-7'

        self.form.helper.add_input(SubmitLightBlue('submit', _('filter_submit')))


class IncomesFilter(django_filters.FilterSet):
    amount__gte = django_filters.NumberFilter(field_name='amount', label=_('Minimum amount'))
    amount__lte = django_filters.NumberFilter(field_name='amount', label=_('Maximum amount'))
    category = django_filters.ModelChoiceFilter(
        field_name='category', label=_('category'), queryset=Category.objects.none(),
        widget=Select(attrs={'class': 'select form-control'})
    )
    # tag = django_filters.CharFilter(field_name='tag', label=_('tag'))
    note = django_filters.CharFilter(field_name='note', label=_('note'), lookup_expr='icontains')
    date = django_filters.DateFromToRangeFilter(
        field_name='date', label=_('date'),
        widget=django_filters.widgets.RangeWidget(attrs={'type': 'date'})
    )

    class Meta:
        model = Transaction
        fields = []

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.filters['category'].queryset = Category.get_categories_tree(user, type_filter=Category.INCOMES)
        self.form.helper = FormHelper()
        self.form.helper.form_id = 'id-filterForm'
        self.form.helper.form_class = 'form-horizontal'
        self.form.helper.label_class = 'col-lg-3 col-md-3 col-sm-3 col-xs-5'
        self.form.helper.field_class = 'col-lg-9 col-md-9 col-sm-9 col-xs-7'

        self.form.helper.add_input(SubmitLightBlue('submit', _('filter_submit')))
