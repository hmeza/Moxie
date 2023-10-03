from django.views.generic import CreateView, UpdateView, ListView, DeleteView
from django.contrib.auth.mixins import LoginRequiredMixin
from django.urls import reverse_lazy
from django.http.response import JsonResponse, HttpResponseRedirect
from moxie.models import Category, Budget
from moxie.views.users import ConfigUserContextData
from moxie.views.views import UserOwnerMixin


class CategoryView(LoginRequiredMixin, ConfigUserContextData, UpdateView, ListView):
    model = Category
    fields = ['parent', 'name', 'description', 'type', 'order']
    success_url = reverse_lazy('users')
    template_name = 'users/index.html'


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
            obj = Category.objects.get(pk=request.POST.get(key))
            obj.order = key
            update_list.append(obj)
        Category.objects.bulk_update(update_list, ['order'], batch_size=1000)
    return JsonResponse({"status": "success"}, status=202)


class CategoryDeleteView(LoginRequiredMixin, DeleteView, UserOwnerMixin):
    model = Category
    success_url = reverse_lazy('users')

    def get(self, request, *args, **kwargs):
        return self.delete(request, *args, **kwargs)


#     public function deleteAction() {
#         // TODO: check if category has expenses or incomes
#         // if so, assign it before deleting
#         // delete category
#         $i_id = $this->getRequest()->getParam('id');
#         try {
#             // delete children categories
#             $this->categories->delete('parent = '.$i_id);
#             $this->categories->delete('id = '.$i_id);
#         }
#         catch (Exception $e) {
#             error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
#         }
#         $this->_helper->redirector('index','categories');
#     }
