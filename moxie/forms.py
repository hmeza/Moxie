from django.forms import Form, ModelForm
from models.models import Category


class CategoryForm(ModelForm):
    class Meta:
        model = Category


class CategoryUpdateForm(CategoryForm):
    class Meta:
        model = Category
        fields = ['name', 'description', 'parent', 'type']
