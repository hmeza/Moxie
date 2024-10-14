import csv
from django.http.response import HttpResponse
from django.utils.translation import gettext_lazy as _
from moxie.models import TransactionTag, Tag, Transaction, Category
import logging


logger = logging.getLogger()


class UpdateTagsView:
    def update_tags(self, form, transaction, user):
        tags = form.data.get('tag').split(",")
        for tag_name in tags:
            try:
                (tag, created) = Tag.objects.get_or_create(user=user, name=tag_name)
                TransactionTag.objects.get_or_create(transaction=transaction, tag=tag)
            except Tag.MultipleObjectsReturned:
                last_tag = Tag.objects.filter(user=user, name=tag_name).last()
                TransactionTag.objects\
                    .filter(tag__user=user, tag__name=tag_name)\
                    .exclude(tag=last_tag)\
                    .update(tag=last_tag)
                dup_tags = Tag.objects.filter(user=user, name=tag_name).exclude(id=last_tag.id)
                dup_tags.delete()


class ExportView:
    def download_csv(self):
        queryset = self.filterset.queryset
        if not self.object_list:
            self.object_list = queryset
        model = queryset.model
        model_fields = model._meta.fields + model._meta.many_to_many
        field_names = [field.name for field in model_fields]

        response = HttpResponse(content_type='text/csv')
        response['Content-Disposition'] = 'attachment; filename="export.csv"'

        writer = csv.writer(response, delimiter=";")
        writer.writerow([_(field.name) for field in model_fields])
        for row in self.object_list:
            values = []
            for field in field_names:
                try:
                    value = getattr(row, str(field))
                    if callable(value):
                        try:
                            value = value() or ''
                        except:
                            value = 'Error retrieving value'
                    if value is None:
                        value = ''
                    values.append(value)
                except (Transaction.DoesNotExist, Category.DoesNotExist) as e:
                    logger.error(e)
            writer.writerow(values)
        return response


class TransactionView:
    def _get_grouped_object_list(self, object_list):
        object_grouped_list = []
        current_date = None
        current_group = {}
        for obj in object_list:
            if not current_date or obj.date != current_date:
                if current_date:
                    object_grouped_list.append(current_group)
                current_date = obj.date
                current_group = {
                    'date': current_date,
                    'object_list': []
                }
            current_group['object_list'].append(obj)
        return object_grouped_list
