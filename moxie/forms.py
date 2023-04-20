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
    category = ModelChoiceField(label=_('category'), queryset=Category.objects.none(), widget=forms.Select(attrs={'class': 'select form-control'}))
    tag = CharField(label=_('tag'), required=False)
    note = CharField(label=_('note'))
    amount = FloatField(label=_('amount'))
    date = DateField(label=_('date'))
    in_sum = BooleanField(label=_('in_sum'), initial=False)

    class Meta:
        model = Transaction
        exclude = ['income_update', 'user_owner']

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['category'].queryset = Category.get_categories_by_user(user)
        self.helper = FormHelper()
        self.helper.form_id = 'id-exampleForm'
        self.helper.form_class = 'form-horizontal'
        self.helper.label_class = 'col-lg-2'
        self.helper.field_class = 'col-lg-8'
        self.helper.form_method = 'post'
        self.helper.form_action = 'submit_survey'

        self.helper.add_input(Submit('submit', 'Submit'))


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
