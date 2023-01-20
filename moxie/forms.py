from django.forms import Form, ModelForm
from moxie.models import Category, Transaction
from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit


class CategoryForm(ModelForm):
    class Meta:
        model = Category
        exclude = []


class CategoryUpdateForm(CategoryForm):
    class Meta:
        model = Category
        fields = ['name', 'description', 'parent', 'type']


class ExpensesForm(ModelForm):
    class Meta:
        model = Transaction
        exclude = ['income_update']

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.form_id = 'id-exampleForm'
        self.helper.form_class = 'form-horizontal'
        self.helper.label_class = 'col-lg-2'
        self.helper.field_class = 'col-lg-8'
        self.helper.form_method = 'post'
        self.helper.form_action = 'submit_survey'

        self.helper.add_input(Submit('submit', 'Submit'))
