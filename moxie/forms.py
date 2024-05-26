import datetime
from django.forms import ModelForm, ModelChoiceField, CharField, DateField, \
    ChoiceField, BooleanField, ValidationError, DecimalField
from django import forms
from moxie.models import Category, Transaction, MoxieUser, Favourite, Tag, SharedExpensesSheet,\
    SharedExpensesSheetUsers, SharedExpense
from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from django.utils.translation import gettext as _
from django.urls import reverse_lazy
from captcha.fields import CaptchaField
from moxie.templatetags.currency import currency_symbol
from django.contrib.auth.forms import PasswordResetForm, SetPasswordForm
from crispy_forms.layout import Button


class CategoryForm(ModelForm):
    parent = forms.ModelChoiceField(label=_('Parent'), queryset=Category.objects.none(), widget=forms.Select(attrs={'class': 'form-control'}))

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
        url = reverse_lazy('category_edit', kwargs={'pk': self.instance.pk}) if self.instance and self.instance.pk else reverse_lazy('category_add')
        self.helper.form_action = url
        self.helper.add_input(Submit('submit', _('Submit')))
        if self.instance and self.instance.pk:
            del_url = reverse_lazy('category_delete', kwargs={'pk': self.instance.pk})
            self.helper.add_input(
                Button('delete', 'Delete', onclick='window.location.href="{}"'.format(del_url), css_class='btn btn-danger float-right')
            )

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

    language = forms.ChoiceField(label=_("Language"), choices=CHOICES)

    class Meta:
        model = MoxieUser
        exclude = ['login', 'created_at', 'updated_at', 'last_login', 'is_active', 'is_staff', 'username',
                   'is_superuser', 'groups', 'user_permissions', 'date_joined', 'password', 'user']

    def __init__(self, user, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.instance = user
        self.helper = FormHelper()
        self.helper.form_id = 'users_update_profile'
        self.helper.form_class = 'form-horizontal'
        self.helper.label_class = 'col-lg-3 col-md-3 col-sm-3 col-xs-5'
        self.helper.field_class = 'col-lg-9 col-md-9 col-sm-9 col-xs-7'
        self.helper.form_method = 'post'
        self.helper.form_action = reverse_lazy('users_update')
        if user.moxieuser_resource_file.language:
            self.fields['language'].initial = user.moxieuser_resource_file.language

        self.helper.add_input(Submit('submit', _('Save')))


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
    amount = DecimalField(label=_('amount'), widget=forms.TextInput(attrs={'type': 'float'}))
    date = DateField(label=_('date'), widget=forms.TextInput(attrs={'type': 'date'}))
    in_sum = BooleanField(label=_('in_sum'), initial=True, required=False)
    favourite = BooleanField(label=_('Favourite'), initial=False, required=False)

    class Meta:
        model = Transaction
        fields = ['favourites', 'amount', 'note', 'date', 'category', 'tag', 'in_sum', 'favourite']
        exclude = ['user', 'income_update']

    def __init__(self, user, *args, **kwargs):
        super().__init__(user, *args, **kwargs)
        self.fields['category'].queryset = Category.get_categories_tree(user)
        translation = _('Update expense') if self.instance else _('Add expense')
        self.helper.add_input(Submit('submit', translation))
        if self.initial.get('amount'):
            self.initial['amount'] = -self.initial.get('amount')
        self.fields['favourites'].choices = self.__mount_favourites(user)
        if self.instance:
            exists = Favourite.objects.filter(transaction=self.instance).exists()
            self.fields['favourite'].value = exists
            self.fields['favourite'].initial = exists

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
    amount = DecimalField(label=_('amount'), widget=forms.TextInput(attrs={'type': 'float'}))
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


class RegisterForm(forms.ModelForm):
    captcha = CaptchaField()
    password = forms.CharField(label=_("Password"), widget=forms.PasswordInput)
    new_password2 = forms.CharField(label=_("Repeat password"), widget=forms.PasswordInput)

    class Meta:
        model = MoxieUser
        fields = ['username', 'password', 'new_password2', 'email']

    def clean_new_password2(self):
        if self.cleaned_data.get('password') != self.cleaned_data.get('new_password2'):
            raise ValidationError(_("Passwords do not match"))
        return self.cleaned_data.get('new_password2')


class ChangePasswordForm(SetPasswordForm):
    class Meta:
        model = MoxieUser
        fields = ['new_password1', 'new_password2']


class UpdateUserData(forms.ModelForm, SetPasswordForm):
    captcha = CaptchaField()
    repeat_password = forms.CharField(label=_("Repeat password"))

    class Meta:
        model = MoxieUser
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


class ModelSingleChoiceFieldForPlatform(forms.ModelChoiceField):
    def label_from_instance(self, obj):
        return f"{obj.name}"


class SharedExpensesSheetsForm(forms.ModelForm):
    class Meta:
        model = SharedExpensesSheet
        fields = ['name', 'currency', 'change']

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', _('Add')))
        self.helper.form_action = reverse_lazy('sheet_add')


class SharedExpensesForm(forms.ModelForm):
    use_distinct_currency = forms.BooleanField(label=_('In sheet currency?'), initial=False, required=False)
    date = DateField(label=_('date'), widget=forms.TextInput(attrs={'type': 'date'}))

    class Meta:
        model = SharedExpense
        fields = ['user', 'amount', 'note', 'date', 'use_distinct_currency']

    def __init__(self, sheet, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', _('Add expense')))
        self.helper.form_action = reverse_lazy('sheet_expenses', kwargs={'unique_id': sheet.unique_id})

        self.fields['user'].queryset = sheet.users.all()
        symbol = currency_symbol(sheet.currency)
        self.fields['use_distinct_currency'].help_text = f"(1.00 € = {sheet.change:.2f} {symbol})"
        if sheet.currency == SharedExpensesSheet.DEFAULT_CURRENCY:
            self.fields['use_distinct_currency'].widget = forms.HiddenInput()


class SharedExpensesSheetAddUser(forms.ModelForm):
    email = forms.CharField(
        label=_('User or email'),
        help_text=_('Enter address of person with whom you are sharing expenses.&nbsp;'),
        required=True
    )

    class Meta:
        model = SharedExpensesSheetUsers
        fields = ['email']

    def __init__(self, unique_id, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', _('Add')))
        self.helper.form_action = reverse_lazy('sheet_add_user', kwargs={'unique_id': unique_id})

    def clean_email(self):
        return self.cleaned_data.get('email')


class MoxiePasswordResetForm(PasswordResetForm):
    captcha = CaptchaField()

    def __init__(self, *args, **kwargs):
        super(MoxiePasswordResetForm, self).__init__(*args, **kwargs)
        self.helper = FormHelper()
        self.helper.add_input(
            Submit('submit', _('Submit'), css_class='form-control moxie_login_button mt-2')
        )
