from django.views.generic import CreateView, DeleteView
from django.urls import reverse_lazy
from moxie.views.views import UserOwnerMixin
from moxie.models import Budget


class SnapshotView(CreateView):
    pass


class BudgetView(CreateView):
    pass


class BudgetDeleteView(UserOwnerMixin, DeleteView):
    model = Budget
    success_url = reverse_lazy('users')

    def get(self, request, *args, **kwargs):
        return self.delete(request, *args, **kwargs)


class BudgetSnapshotView(CreateView):
    pass
#     /**
#      * Makes a snapshot of current budget and generates a new one.
#      * @todo    Handle exception with proper message
#      * @author    hmeza
#      * @since    2011-11-12
#      */
#     public function snapshotAction() {
#         $result = true;
#         header("Cache-Control: no-cache");
#         try {
#             $this->budgets->snapshot($_SESSION['user_id']);
#         }
#         catch (Exception $e) {
#             error_log(__METHOD__.": ".$e->getMessage());
#             $result = false;
#         }
#         $this->render('index','categories');
#         return $result;
#     }