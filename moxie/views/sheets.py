import re
import uuid
from django.views.generic import CreateView, UpdateView, DeleteView, ListView, FormView
from django.http.response import HttpResponseRedirect
from django.urls import reverse_lazy
from django.shortcuts import redirect
from django.utils.translation import gettext_lazy as _
from django.core.mail import send_mail
from django.db.models import Case, When, Value, BooleanField
from django.contrib.auth import logout
from django.contrib.auth.mixins import LoginRequiredMixin
from moxie.models import SharedExpense, SharedExpensesSheet, Category, SharedExpensesSheetUsers, Transaction
from moxie.forms import SharedExpensesSheetsForm, SharedExpensesSheetAddUser, SharedExpensesForm


class SheetsView(LoginRequiredMixin, ListView, FormView):
	paginate_by = 10
	template_name = 'sheets/index.html'
	form_class = SharedExpensesSheetsForm

	def get_queryset(self):
		return SharedExpensesSheet.objects.filter(users__user=self.request.user)


class SheetCreateView(LoginRequiredMixin, CreateView):
	model = SharedExpensesSheet
	fields = ['name', 'currency', 'change']

	def form_valid(self, form):
		instance = form.save(commit=False)
		# TODO once migrated to Django, change id by uuid and generate automatically on create
		instance.unique_id = uuid.uuid4()
		instance.user = self.request.user
		instance.save()
		SharedExpensesSheetUsers.objects.create(
			sheet=instance,
			user=self.request.user
		)
		return HttpResponseRedirect(reverse_lazy('sheet_view', kwargs={'unique_id': instance.unique_id}))


# TODO: make this view accessible for non-users
class SheetView(LoginRequiredMixin, UpdateView):
	model = SharedExpensesSheet
	slug_url_kwarg = 'unique_id'
	query_pk_and_slug = True
	template_name = 'sheets/view.html'
	fields = ['name']

	def form_invalid(self, form):
		print(form.errors)
		return super().form_invalid(form)

	def get_slug_field(self):
		return 'unique_id'

	def get_queryset(self):
		queryset = super().get_queryset()
		queryset = queryset.prefetch_related('users', 'users__user', 'expenses')
		return queryset

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		unique_id = self.kwargs.get('unique_id')
		context['sheet_list'] = SharedExpensesSheet.objects.exists()
		context['shared_expenses_form'] = SharedExpensesForm(self.object)
		context['sheet_closed'] = bool(self.object.closed_at)

		sheet_user = SharedExpensesSheetUsers.objects.filter(user=self.request.user).first()
		conditional = Case(When(user=sheet_user, then=Value(True)), default_value=Value(False), output_field=BooleanField())

		sheet = SharedExpense.objects\
			.select_related('sheet').prefetch_related('sheet__users')\
			.annotate(my_expense=conditional).filter(sheet__unique_id=unique_id).order_by('date')
		context['sheet'] = sheet
		context['total'] = self.get_object().total
		context['user_categories'] = Category.get_categories_by_user(self.request.user, Category.EXPENSES)
		context['sheet_users'] = SharedExpensesSheet.objects.get(unique_id=unique_id).users
		context['pie_data'] = [[user.user.username, float(user.sheet_expense)] for user in self.object.users.all()]
		context['add_user_form'] = SharedExpensesSheetAddUser(unique_id=unique_id)
		return context


class SharedExpenseView(LoginRequiredMixin, CreateView):
	model = SharedExpense
	form_class = SharedExpensesForm

	def get_form_kwargs(self):
		kwargs = super().get_form_kwargs()
		kwargs['sheet'] = self._get_shared_expense_sheet()
		return kwargs

	def get_initial(self):
		kwargs = super().get_initial()
		kwargs['sheet'] = self._get_shared_expense_sheet()
		return kwargs

	def get_success_url(self):
		return reverse_lazy('sheet_view', kwargs={'unique_id': self.kwargs.get('unique_id')})

	def form_valid(self, form):
		instance = form.save(commit=False)  # type: SharedExpense
		instance.sheet = self._get_shared_expense_sheet()
		instance.currency = instance.sheet.currency if form.cleaned_data.get('use_distinct_currency') else SharedExpensesSheet.DEFAULT_CURRENCY
		instance.save()
		return HttpResponseRedirect(self.get_success_url())

	def form_invalid(self, form):
		# TODO change this
		print(form.errors)
		return self.form_invalid(form)

	def _get_shared_expense_sheet(self):
		return SharedExpensesSheet.objects.get(unique_id=self.kwargs.get('unique_id'))


class SheetExpenseDeleteView(LoginRequiredMixin, DeleteView):
	model = SharedExpense

	def get_object(self, queryset=None):
		instance = super().get_object(queryset)  # type: SharedExpense
		users_in_sheet = [a[0] for a in list(instance.sheet.users.all().values_list('sheet__users__user_id'))]
		if self.request.user.pk not in users_in_sheet:
			logout(self.request)
			return None
		return instance

	def get(self, request, *args, **kwargs):
		return self.delete(request, *args, **kwargs)

	def get_success_url(self):
		return reverse_lazy('sheet_view', kwargs={'unique_id': self.kwargs.get('unique_id')})


class SheetCopyView(SheetView):
	def get_success_url(self):
		return reverse_lazy('sheet_view', kwargs={'unique_id': self.kwargs.get('unique_id')})

	def post(self, request, *args, **kwargs):
		for key, element in dict(request.POST).items():
			if 'row' in key:
				index = re.search('row-(\d+)', key)
				category_id = int(element[0])
				if not category_id:
					continue
				Transaction.copy_from_shared_expense(int(index[1]), category_id)
		return HttpResponseRedirect(self.get_success_url())


class SheetAddUserView(SheetView):
	model = SharedExpensesSheet
	form_class = SharedExpensesSheetAddUser

	def form_valid(self, form):
		data = form.cleaned_data
		self.object.users.get_or_create(
			user=data.get('user'),
			email=data.get('email'),
		)
		email = data.get('user') if data.get('user') else data.get('email')
		self._send_user_added(self.object, email)
		return redirect(reverse_lazy('sheet_view'), kwargs={'unique_id': self.object.unique_id})

	def _send_user_added(self, sheet, user_email, registered=False):
		subject = "Moxie - " + _('New shared expenses sheet')
		url = reverse_lazy('sheet_view', kwargs={'unique_id': sheet.unique_id})
		text_1 = _('Someone shared an expenses sheet with you:')
		text_2 = """
		If you have a Moxie account, you will see this sheet in your shared expenses sheets.
		If you do not have an account, you can register and this sheet will be linked to your account
		automatically."""
		footer = _('Best regards\n\nMoxie team')
		body = f"""
		{text_1}

		{sheet.name}

		{url}

		{text_2}

		{footer}
		"""
		from_address = 'moxie@dootic.com'
		# TODO https://docs.djangoproject.com/en/4.2/topics/email/
		return send_mail(subject, body, from_address, [user_email])

		# 	private function sendUserAdded($sheetId, $userEmail, $sheetName, $registered=false) {
		# 		$s_server = Zend_Registry::get('config')->moxie->settings->url;
		# 		$s_site = Zend_Registry::get('config')->moxie->app->name;
		#
		# 		$headers = 'From: Moxie <moxie@dootic.com>' . "\r\n" .
		# 				'Reply-To: moxie@dootic.com' . "\r\n" .
		# 				'X-Mailer: PHP/' . phpversion() . "\r\n";


class SheetCloseView(SheetView):
	model = SharedExpensesSheet
	fields = ['closed_at']

	def get_object(self, queryset=None):
		instance = super().get_object(queryset)  # type: SharedExpense
		users_in_sheet = [a[0] for a in list(instance.sheet.users.all().values_list('sheet__users__user_id'))]
		if self.request.user.pk not in users_in_sheet:
			logout(self.request)
			return None
		return instance

	def get_success_url(self):
		return reverse_lazy('sheet_view', kwargs={'unique_id': self.object.unique_id})
