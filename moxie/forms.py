from django.forms import Form, ModelForm, ModelChoiceField, CharField, FloatField, DateField
from moxie.models import Category, Transaction
from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from moxie.filters import get_category_queryset
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
    category = ModelChoiceField(label=_('category'), queryset=Category.objects.none())
    tag = CharField(label=_('tag'), required=False)
    note = CharField(label=_('note'))
    amount = FloatField(label=_('amount'))
    date = DateField(label=_('date'))

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
