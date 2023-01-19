from django.forms import Form, ModelForm
from moxie.models import Category


class CategoryForm(ModelForm):
    class Meta:
        model = Category
        exclude = []


class CategoryUpdateForm(CategoryForm):
    class Meta:
        model = Category
        fields = ['name', 'description', 'parent', 'type']
