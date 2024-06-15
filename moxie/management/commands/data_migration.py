from django.core.management.base import BaseCommand
from moxie.models import *
from django.db import transaction, connection
import logging


logger = logging.getLogger('django')


class Command(BaseCommand):
    help = 'Import data'

    def handle(self, *args, **options):
        # with connection.cursor() as cursor:
        #     cursor.execute("select * from tags where id = 6")
        #     row = cursor.fetchone()
        #     tag = Tag.objects.filter(user_id=row[1], name=row[2].encode('latin1').decode('utf-8')).first()
        #     print(f"tag {tag}")
        # return

        # self.insertion()
        self.taggetization()

    def taggetization(self):
        # todo change to latin1 at this step
        import csv
        with open('tags.csv', newline='') as csvfile:
            spamreader = csv.reader(csvfile, delimiter=',')
            header = True
            for row in spamreader:
                if header:
                    header = False
                    continue
                # print(row[3])
                moxie_transaction = Transaction.objects.get(pk=row[1])
                tag = Tag.objects.filter(user=moxie_transaction.user, name=row[3].encode('latin1').decode('utf-8')).first()
                if not tag:
                    print(row)
                    raise Exception("No tag found")
                print(tag)

    def insertion(self):
        # todo use utf8mb4 at this step
        with connection.cursor() as cursor:
            cursor.execute("SET CHARACTER_SET_RESULTS = \'utf8mb4\'")
            cursor.execute("SELECT * FROM transactions t left join favourites f on t.id = f.id_transaction")
            for row in cursor.fetchall():
                transaction = Transaction.objects.create(
                    user_id=row[1],
                    amount=row[2],
                    category_id=row[3],
                    note=row[4],
                    date=row[5],
                    in_sum=row[6],
                    income_update=row[7]
                )
                if row[8] is not None:
                    print(row)
                    Favourite.objects.create(
                        transaction=transaction
                    )
                # now select * from tags
                with connection.cursor() as cursor2:
                    cursor2.execute("SELECT * FROM transaction_tags tt inner join tags t on tt.id_tag = t.id WHERE id_transaction = %s", [row[0]])
                    for row2 in cursor2.fetchall():
                        print(f"transaction,{transaction.pk},tag,{row2[7]}")
                        # tag = Tag.objects.filter(user_id=row2[6], name=row2[7].encode('latin1').decode('utf-8')).first()
                        # TransactionTag.objects.create(
                        #     transaction=transaction,
                        #     tag=tag,
                        #     created_at=row2[3],
                        #     updated_at=row2[4]
                        # )
