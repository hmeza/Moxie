import datetime

from django.forms import ModelForm, ModelChoiceField, CharField, FloatField, DateField, DateTimeField, \
    ChoiceField, BooleanField, ValidationError
from django import forms
from moxie.models import Category, Transaction, User, Favourite, Tag
from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from django.utils.translation import gettext as _
from django.urls import reverse_lazy
from captcha.fields import CaptchaField


class CategoryForm(ModelForm):
    parent = forms.ModelChoiceField(queryset=Category.objects.none(), widget=forms.Select(attrs={'class': 'form-control'}))

    class Meta:
        model = Category
        exclude = ['user']

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['parent'].queryset = Category.get_categories_tree(user, type_filter=Category.BOTH)

        self.helper = FormHelper()
        self.helper.form_id = 'id-categoriesForm'
        self.helper.form_class = 'form-horizontal'
        self.helper.label_class = 'col-lg-3 col-md-3 col-sm-3 col-xs-5'
        self.helper.field_class = 'col-lg-9 col-md-9 col-sm-9 col-xs-7'
        self.helper.form_method = 'post'
        url = reverse_lazy('category_edit', kwargs={'pk': self.instance.pk}) if self.instance and self.instance.pk else reverse_lazy('category_view')
        self.helper.form_action = url
        self.helper.add_input(Submit('submit', _('Submit')))

        self.fields['type'].widget.attrs.update({'class': 'form-control'})


class TagsForm(ModelForm):
    tags = forms.Textarea()

    class Meta:
        model = Tag
        exclude = []

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)


class CategoryUpdateForm(CategoryForm):
    class Meta:
        model = Category
        fields = ['name', 'description', 'parent', 'type']


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

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)

    password = CharField(max_length=50)
    email = CharField(max_length=12)
    language = ChoiceField(choices=CHOICES)


class TransactionForm(ModelForm):
    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.form_id = 'id-exampleForm'
        self.helper.form_class = 'form-horizontal'
        self.helper.label_class = 'col-lg-3 col-md-3 col-sm-3 col-xs-5'
        self.helper.field_class = 'col-lg-9 col-md-9 col-sm-9 col-xs-7'
        self.helper.form_method = 'post'
        self.helper.form_action = 'submit_survey'

        if self.initial.get('date'):
            self.initial['date'] = self.initial['date'].date
        else:
            self.initial['date'] = datetime.date.today()


class ExpensesForm(TransactionForm):
    favourites = ChoiceField(label=_('Favourites'),
                             widget=forms.Select(
                                 attrs={'class': 'select form-control', 'onchange': 'use_favourite_as_expense'}
                             ),
                             choices=())
    category = ModelChoiceField(label=_('category'), queryset=Category.objects.none(),
                                widget=forms.Select(attrs={'class': 'select form-control'}))
    tag = CharField(label=_('tag'), required=False)
    note = CharField(label=_('note'))
    amount = FloatField(label=_('amount'), widget=forms.TextInput(attrs={'type': 'float'}))
    date = DateField(label=_('date'), widget=forms.TextInput(attrs={'type': 'date'}))
    in_sum = BooleanField(label=_('in_sum'), initial=True, required=False)
    favourite = BooleanField(label=_('Favourite'), initial=False, required=False)

    class Meta:
        model = Transaction
        fields = ['favourites', 'amount', 'note', 'date', 'category', 'tag', 'in_sum']
        exclude = ['user', 'income_update']

    def __init__(self, user, *args, **kwargs):
        super().__init__(user, *args, **kwargs)
        self.fields['category'].queryset = Category.get_categories_tree(user)
        translation = _('Update expense') if self.instance else _('Add expense')
        self.helper.add_input(Submit('submit', translation))
        if self.initial.get('amount'):
            self.initial['amount'] = -self.initial.get('amount')
        self.fields['favourites'].choices = self.__mount_favourites(user)

    def __mount_favourites(self, user):
        values_list = Favourite.objects.filter(transaction__user=user)\
            .select_related('transaction')\
            .values_list('transaction__id', 'transaction__note')
        return [('0', '-----')] + list(values_list)

    def clean_in_sum(self):
        self.cleaned_data['in_sum'] = 1 if self.data.get('in_sum') else 0
        return self.cleaned_data['in_sum']

    def clean_amount(self):
        self.cleaned_data['amount'] = -self.cleaned_data['amount']
        return self.cleaned_data['amount']


class IncomesForm(TransactionForm):
    category = ModelChoiceField(label=_('category'), queryset=Category.objects.none(),
                                widget=forms.Select(attrs={'class': 'select form-control'}))
    note = CharField(label=_('note'))
    amount = FloatField(label=_('amount'), widget=forms.TextInput(attrs={'type': 'float'}))
    date = DateField(label=_('date'), widget=forms.TextInput(attrs={'type': 'date'}))
    in_sum = BooleanField(label=_('in_sum'), initial=True, required=False, widget=forms.HiddenInput())

    class Meta:
        model = Transaction
        fields = ['amount', 'note', 'date', 'category', 'in_sum']
        exclude = ['user', 'income_update', 'tag', 'favourite']

    def __init__(self, user, *args, **kwargs):
        super().__init__(user, *args, **kwargs)
        self.fields['category'].queryset = Category.get_categories_tree(user, type_filter=Category.INCOMES)
        self.helper.form_id = 'id-incomesForm'
        self.helper.add_input(Submit('submit', _('Add income')))
        self.helper.form_action = reverse_lazy('incomes_edit', kwargs={'pk': self.instance.pk}) if self.instance.pk else reverse_lazy('incomes_add')

    def clean_in_sum(self):
        return 1


from django.contrib.auth.forms import SetPasswordForm


class RegisterForm(forms.ModelForm, SetPasswordForm):
    captcha = CaptchaField()
    repeat_password = forms.CharField(label=_("Repeat password"))

    class Meta:
        model = User
        fields = ['username', 'password', 'new_password2', 'email']

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', _('Submit')))
        self.helper.form_action = reverse_lazy('register')

    def clean_new_password2(self):
        if self.cleaned_data.get('password') != self.cleaned_data.get('repeat_password'):
            raise ValidationError(_("Passwords do not match"))
        return self.cleaned_data.get('repeat_password')


class ChangePasswordForm(SetPasswordForm):
    class Meta:
        model = User
        fields = ['new_password1', 'new_password2']


class UpdateUserData(forms.ModelForm, SetPasswordForm):
    captcha = CaptchaField()
    repeat_password = forms.CharField(label=_("Repeat password"))

    class Meta:
        model = User
        fields = ['username', 'password', 'new_password2', 'email']

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', _('Submit')))
        self.helper.form_action = reverse_lazy('register')

    def clean_new_password2(self):
        if self.cleaned_data.get('password') != self.cleaned_data.get('repeat_password'):
            raise ValidationError(_("Passwords do not match"))
        return self.cleaned_data.get('repeat_password')