from django.views.generic import CreateView, UpdateView, ListView, DeleteView
from django.contrib.auth import logout
from django.contrib.auth.mixins import LoginRequiredMixin
from django.urls import reverse_lazy
from django.http.response import JsonResponse, HttpResponseRedirect
from moxie.models import Category, Budget
from moxie.views.users import ConfigUserContextData


class CategoryView(LoginRequiredMixin, ConfigUserContextData, UpdateView, ListView):
    model = Category
    fields = ['parent', 'name', 'description', 'type', 'order']
    success_url = reverse_lazy('users')
    template_name = 'users/index.html'

    def get_object(self, queryset=None):
        instance = super().get_object(queryset)
        if instance.user != self.request.user:
            logout(self.request)
        return instance


class CategoryAddView(LoginRequiredMixin, CreateView):
    success_url = reverse_lazy('users')
    model = Category
    fields = ['parent', 'name', 'description', 'type', 'order']

    def form_valid(self, form):
        self.object = form.save(commit=False)
        self.object.user = self.request.user
        self.object.save()
        return HttpResponseRedirect(self.get_success_url())


class CategoryBudgetView(UpdateView):
    model = Category
    fields = ['id']
    success_url = reverse_lazy('users')

    # todo validate user
    def form_valid(self, form):
        try:
            amount = self.request.POST.get('amount')
            category = Category.objects.filter(pk=self.kwargs.get('pk'), user=self.request.user).first()
            if not category:
                raise Exception("Category not found")
            instance = Budget.objects.filter(category=category, date_ended__isnull=True).first()
            if not instance:
                instance = Budget(category=category, user=self.request.user)
            instance.amount = amount
            instance.save()
            response = JsonResponse({'success': 'ok'}, status=200)
        except Exception as e:
            response = JsonResponse({'errors': [str(e)]}, status=500)
        return response


def categories_bulk_update(request):
    if request.POST:
        update_list = []
        for key in request.POST:
            obj = Category.objects.filter(pk=request.POST.get(key), user=request.user).first()
            obj.order = key
            update_list.append(obj)
        Category.objects.bulk_update(update_list, ['order'], batch_size=100)
    return JsonResponse({"status": "success"}, status=202)


class CategoryDeleteView(LoginRequiredMixin, DeleteView):
    model = Category
    success_url = reverse_lazy('users')

    def get_object(self, queryset=None):
        obj = super().get_object(queryset)
        if obj.user != self.request.user:
            logout(self.request)
            return None
        return obj

    def get(self, request, *args, **kwargs):
        # todo check if category has children, make them hang by root
        # todo validate transactions are not deleted
        return self.delete(request, *args, **kwargs)
