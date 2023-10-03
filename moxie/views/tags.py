from django.views.generic import CreateView
from django.contrib.auth.mixins import LoginRequiredMixin
from django.shortcuts import redirect
from django.urls import reverse_lazy
from moxie.models import Tag


class TagView(LoginRequiredMixin, CreateView):
    queryset = Tag.objects.none()
    fields = ['name']

    def get(self, request, *args, **kwargs):
        return redirect(reverse_lazy('users'))

    def form_valid(self, form):
        tag_list = form.data.get('tags').split(',')
        Tag.clean_tags(tag_list, self.request.user)
        Tag.create_new_tags(tag_list, self.request.user)

        return redirect(reverse_lazy('users'))
