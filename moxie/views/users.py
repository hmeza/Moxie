from django.views.generic import ListView, UpdateView
from django.urls import reverse_lazy
from django.contrib.auth.forms import PasswordChangeForm
from django.contrib.auth import update_session_auth_hash
from django.http.response import HttpResponseRedirect
from moxie.forms import CategoryForm, MyAccountForm, TagsForm
from moxie.models import Category, Tag, Favourite, Budget, MoxieUser
from django.contrib.auth.mixins import LoginRequiredMixin
from django.db.models import Sum
from django.conf import settings


class ConfigUserContextData:
    def get_context_data(self, *args, **kwargs):
        context = super().get_context_data(*args, **kwargs)
        user = self.request.user  # type: MoxieUser
        context['my_account_form'] = MyAccountForm(user, instance=user)
        context['change_password_form'] = PasswordChangeForm(user)
        if 'category' in self.request.path:
            instance = Category.objects.get(pk=self.kwargs.get('pk'))
            context['category'] = instance
            category_form = CategoryForm(user, instance=instance)
        else:
            category_form = CategoryForm(user)
        context['categories_form'] = category_form
        context['categories_list'] = Category.get_categories_tree(user)
        context['tags_form'] = TagsForm(user)
        context['tag_list'] = Tag.get_tags(user)
        context['favourites'] = Favourite.get_for_config(user)
        current_budget = Budget.get_budget(user)
        context['current_budget'] = current_budget
        context['current_budget_amount'] = current_budget.aggregate(total=Sum('amount'))['total']
        context['budgets_list'] = Budget.closed_budgets(user)
        return context


class UserConfigurationView(LoginRequiredMixin, ConfigUserContextData, ListView):
    model = Category
    template_name = 'users/index.html'
    form_class = MyAccountForm

    def get_queryset(self):
        return Category.objects.filter(user=self.request.user)


class UserUpdateView(UpdateView):
    model = MoxieUser
    success_url = reverse_lazy('users')

    def get_form_class(self):
        return MyAccountForm

    def get_form_kwargs(self):
        kwargs = super().get_form_kwargs()
        if not self.request.user.moxieuser_resource_file:
            moxie_user = MoxieUser(user=self.request.user)
            moxie_user.save()
        kwargs['user'] = self.request.user
        return kwargs

    def get_object(self, queryset=None):
        return self.request.user

    def form_valid(self, form):
        instance = form.save()
        instance.moxieuser_resource_file.language = form.cleaned_data.get('language')
        instance.moxieuser_resource_file.save()
        response = super().form_valid(form)
        response.set_cookie(settings.LANGUAGE_COOKIE_NAME, instance.moxieuser_resource_file.language)
        return response


def user_password_change(request):
    if request.method == "POST":
        form = PasswordChangeForm(user=request.user, data=request.POST)
        if form.is_valid():
            form.save()
            update_session_auth_hash(request, form.user)
    return HttpResponseRedirect(reverse_lazy('users'))
