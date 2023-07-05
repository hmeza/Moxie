from django.forms import ModelForm, ModelChoiceField, CharField, FloatField, DateField, DateTimeField, \
    ChoiceField, BooleanField
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
    # todo add favorites
    # todo add checkbox to add as favorite
    category = ModelChoiceField(label=_('category'), queryset=Category.objects.none(),
                                widget=forms.Select(attrs={'class': 'select form-control'}))
    tag = CharField(label=_('tag'), required=False)
    note = CharField(label=_('note'))
    amount = FloatField(label=_('amount'))
    date = DateField(label=_('date'), widget=forms.TextInput(attrs={'type': 'date'}))
    in_sum = BooleanField(label=_('in_sum'), initial=True, required=False)

    class Meta:
        model = Transaction
        fields = ['amount', 'note', 'date', 'category', 'tag', 'in_sum']
        exclude = ['user', 'income_update']

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['category'].queryset = Category.get_categories_by_user(user)
        self.helper = FormHelper()
        self.helper.form_id = 'id-exampleForm'
        self.helper.form_class = 'form-horizontal'
        self.helper.label_class = 'col-lg-3 col-md-3 col-sm-3 col-xs-5'
        self.helper.field_class = 'col-lg-9 col-md-9 col-sm-9 col-xs-7'
        self.helper.form_method = 'post'
        self.helper.form_action = 'submit_survey'

        self.helper.add_input(Submit('submit', _('Add expense')))

        if self.initial.get('amount'):
            self.initial['amount'] = -self.initial.get('amount')

    def clean_in_sum(self):
        self.cleaned_data['in_sum'] = 1 if self.data.get('in_sum') else 0
        return self.cleaned_data['in_sum']

    def clean_amount(self):
        self.cleaned_data['amount'] = -self.cleaned_data['amount']
        return self.cleaned_data['amount']


class MyAccountForm(ModelForm):
    CHOICES = (
        ('es', 'Español'),
        ('ca', 'Català'),
        ('en', 'English')
    )

    class Meta:
        db_table = 'users'
        model = User
        exclude = ['login', 'created_at', 'updated_at', 'last_login']

    password = CharField(max_length=50)
    email = CharField(max_length=12)
    language = ChoiceField(choices=CHOICES)
