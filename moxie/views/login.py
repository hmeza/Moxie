from django.views.generic import CreateView
from django.contrib.auth import authenticate, login, logout
from django.contrib.auth.models import User
from django.core.mail import send_mail
from django.contrib.sites.shortcuts import get_current_site
from django.http import HttpResponseRedirect
from django.shortcuts import redirect, render
from django.template.loader import render_to_string
from django.urls import reverse_lazy
from django.utils.translation import gettext_lazy as _
from django.contrib.auth.models import User
from django.utils.http import urlsafe_base64_encode, urlsafe_base64_decode
from moxie.forms import RegisterForm, SetPasswordForm, MoxiePasswordResetForm
from django.contrib import messages
from django.db.models.query_utils import Q
from django.utils.encoding import force_bytes, force_str
from django.conf import settings
from django.contrib.auth.tokens import PasswordResetTokenGenerator
import six


def login_view(request):
	username = request.POST.get("username")
	password = request.POST.get("password")
	user = authenticate(request, username=username, password=password)
	if user is not None and password is not None:
		login(request, user)
		return redirect(reverse_lazy('expenses'))
	else:
		messages.error(request, _('Login incorrect'))
		return redirect(reverse_lazy('index'))


def logout_view(request):
	logout(request)
	return HttpResponseRedirect(reverse_lazy('index'))


class RegisterView(CreateView):
	model = User
	form_class = RegisterForm
	template_name = 'index/register.html'

	def form_valid(self, form):
		instance = form.save(commit=False)
		instance.set_password(form.cleaned_data.get('password'))
		instance.save()
		return HttpResponseRedirect(self.get_success_url())

	def get_success_url(self):
		messages.info(self.request, _('Registered successfully.'))
		return reverse_lazy('index')


class ActivationTokenGenerator(PasswordResetTokenGenerator):
	def _make_hash_value(self, user, timestamp):
		return six.text_type(user.pk) + six.text_type(timestamp) + six.text_type(user.is_active)


def password_change(request):
	def get_associated_user(user_email):
		return User.objects.filter(Q(email=user_email)).first()

	if request.method == 'POST':
		form = MoxiePasswordResetForm(request.POST)
		if form.is_valid():
			associated_user = get_associated_user(form.cleaned_data['email'])  # type: User
			account_activation_token = ActivationTokenGenerator()
			if associated_user:
				subject = "Password Reset request"
				message = render_to_string("index/template_reset_password.html", {
					'user': associated_user,
					'domain': get_current_site(request).domain,
					'uid': urlsafe_base64_encode(force_bytes(associated_user.pk)),
					'token': account_activation_token.make_token(associated_user),
					"protocol": 'https' if request.is_secure() else 'http'
				})
				try:
					if send_mail(subject, message, settings.FROM_EMAIL, [associated_user.email]):
						messages.success(request, _("""
	<h2>Password reset sent</h2><hr>
	<p>
		We've emailed you instructions for setting your password, if an account exists with the email you entered. 
		You should receive them shortly.<br>If you don't receive an email, please make sure you've entered the address 
		you registered with, and check your spam folder.
	</p>
	"""))
					else:
						messages.error(request, _("Problem sending reset password email"))
				except ConnectionRefusedError:
					messages.error(request, _("Connection error"))
					# TODO remove this
					messages.info(request, message)

			return redirect('index')

		for key, error in list(form.errors.items()):
			if key == 'captcha' and error[0] == 'This field is required.':
				messages.error(request, _("You must pass the reCAPTCHA test"))
				continue

	form = MoxiePasswordResetForm()
	return render(
		request=request,
		template_name="index/password_reset.html",
		context={"form": form}
	)


def password_reset_confirm(request, uidb64, token):
	try:
		uid = force_str(urlsafe_base64_decode(uidb64))
		user = User.objects.get(pk=uid)
	except:
		user = None

	account_activation_token = ActivationTokenGenerator()
	if user is not None and account_activation_token.check_token(user, token):
		if request.method == 'POST':
			form = SetPasswordForm(user, request.POST)
			if form.is_valid():
				form.save()
				messages.success(request, _("Your password has been set. You may go ahead and log in now."))
				return redirect('index')
			else:
				for error in list(form.errors.values()):
					messages.error(request, error)

		form = SetPasswordForm(user)
		return render(request, 'index/password_reset_confirm.html', {'form': form})
	messages.error(request, _("Link is expired"))
	return redirect("index")
