from django.forms import Form, ModelForm, ModelChoiceField, CharField, FloatField, DateField
from django import forms
from moxie.models import Category, Transaction, User
from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from django.utils.translation import gettext as _


class CategoryForm(ModelForm):
    class Meta:
        model = Category
        exclude = []


class CategoryUpdateForm(CategoryForm):
    class Meta:
        model = Category
        fields = ['name', 'description', 'parent', 'type']


class ExpensesForm(ModelForm):
    category = forms.ChoiceField(
        label=_('category'),
        choices=Category.get_categories,
        widget=forms.Select(attrs={'class': 'select form-control'})
    )
    tag = CharField(label=_('tag'), required=False)
    note = CharField(label=_('note'))
    amount = FloatField(label=_('amount'))
    date = DateField(label=_('date'), widget=forms.DateInput(attrs={'type': 'date'}))

    class Meta:
        model = Transaction
        exclude = ['income_update', 'user_owner']

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.form_id = 'id-exampleForm'
        self.helper.form_class = 'form-horizontal'
        self.helper.label_class = 'col-lg-2'
        self.helper.field_class = 'col-lg-8'
        self.helper.form_method = 'post'
        self.helper.form_action = 'submit_survey'

        self.helper.add_input(Submit('submit', _('Save')))
