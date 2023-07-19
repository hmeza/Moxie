from django.views.generic import CreateView, UpdateView, ListView
from django.contrib.auth.mixins import LoginRequiredMixin
from django.shortcuts import redirect
from django.urls import reverse_lazy
from django.http.response import JsonResponse
from moxie.models import Category
from moxie.views.users import ConfigUserContextData


class CategoryView(LoginRequiredMixin, ConfigUserContextData, CreateView, UpdateView, ListView):
    model = Category
    fields = ['parent', 'name', 'description', 'type', 'order']
    success_url = reverse_lazy('users')
    template_name = 'users/index.html'

    def form_valid(self, form):
        instance = form.save(commit=False)
        if not instance.user:
            instance.user = self.request.user
        instance.save()
        return redirect(self.success_url)


def categories_bulk_update(request):
    if request.POST:
        print(request.POST)
        update_list = []
        for key in request.POST:
            obj = Category.objects.get(pk=request.POST.get(key))
            obj.order = key
            update_list.append(obj)
        Category.objects.bulk_update(update_list, ['order'], batch_size=1000)
    return JsonResponse({"status": "success"}, status=202)

