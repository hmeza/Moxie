from django.views.generic import DeleteView, View
from django.urls import reverse_lazy
from django.utils.decorators import method_decorator
from django.views.decorators.http import require_http_methods
from django.http.response import HttpResponse, HttpResponseRedirect
from moxie.models import Budget


class BudgetDeleteView(DeleteView):
    model = Budget
    success_url = reverse_lazy('users')

    def get(self, request, *args, **kwargs):
        self.object = Budget.objects.get(pk=self.kwargs.get('pk'))
        Budget.delete_budget_set(self.kwargs.get('pk'), self.request.user)
        return HttpResponseRedirect(self.get_success_url())


@method_decorator(require_http_methods(["POST"]), name='dispatch')
class BudgetSnapshotView(View):
    def post(self, request, *args, **kwargs):
        Budget.snapshot(self.request.user.pk)
        return HttpResponse(reverse_lazy('users'), status=201)
