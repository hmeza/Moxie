from django.views.generic import ListView, UpdateView
from django.urls import reverse_lazy
from django.contrib.auth.forms import PasswordChangeForm
from django.contrib.auth import update_session_auth_hash
from django.http.response import HttpResponseRedirect
from moxie.forms import CategoryForm, MyAccountForm, TagsForm
from moxie.models import Category, Tag, Favourite, Budget, User
from django.contrib.auth.mixins import LoginRequiredMixin
from django.db.models import Sum


class ConfigUserContextData:
    def get_context_data(self, *args, **kwargs):
        context = super().get_context_data(*args, **kwargs)
        user = self.request.user
        context['my_account_form'] = MyAccountForm(user)
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
        context['budgets_list'] = Budget.objects.filter(user=user).order_by('-date_created').all()
        return context


class UserConfigurationView(LoginRequiredMixin, ConfigUserContextData, ListView):
    model = Category
    template_name = 'users/index.html'
    form_class = MyAccountForm

    # def get_form_kwargs(self):
    #     kwargs = super().get_form_kwargs()
    #     kwargs['user'] = self.request.user
    #     return kwargs

    def get_queryset(self):
        return Category.objects.filter(user=self.request.user)

    def get_object(self, queryset=None):
        return Category.objects.none()


class UserUpdateView(UpdateView):
    model = User
    success_url = reverse_lazy('users')


def user_password_change(request):
    if request.method == "POST":
        form = PasswordChangeForm(user=request.user, data=request.POST)
        if form.is_valid():
            form.save()
            update_session_auth_hash(request, form.user)
    return HttpResponseRedirect(reverse_lazy('users'))
