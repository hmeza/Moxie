from django.core.management.base import BaseCommand
from moxie.models import *
from django.db import transaction, connection
import logging
import ftfy


logger = logging.getLogger('django')


class Command(BaseCommand):
    help = 'Import data'

    def handle(self, *args, **options):
        self._migrate_transactions()
        # self.insertion()
        # self.taggetization()

    def _migrate_categories(self, cursor):
        cursor.execute("SET NAMES 'utf8'")
        cursor.execute("select * from categories")
        column_names = [col[0] for col in cursor.description]
        rows = cursor.fetchall()
        for row in rows:
            row_dict = dict(zip(column_names, row))
            self.stdout.write()
            counter = row[0]
            self.stdout.write(str(counter))
            note = row[4]
            self.stdout.write(note)

            fixed_col = self._fix_note(note, row)

            self.stdout.write(fixed_col)

            obj = Transaction(**row_dict)
            obj.save(force_insert=True)

    def _migrate_transactions(self):
        with connection.cursor() as cursor:
            cursor.execute("SET NAMES 'utf8'")
            cursor.execute("select * from transactions")
            column_names = [col[0] for col in cursor.description]
            rows = cursor.fetchall()
            for row in rows:
                row_dict = dict(zip(column_names, row))
                note = row[4]

                fixed_col = self._fix_note(note, row)
                fixed_col = fixed_col.encode('utf-8').decode('utf-8')

                self.stdout.write(f"{row[0]} {note} - fixed {fixed_col}")

                row_dict['user_id'] = row_dict['user_owner']
                row_dict.pop('user_owner')

                if row_dict['income_update'] == '0000-00-00 00:00:00':
                    row_dict['income_update'] = row_dict['date']

                insert_query = """
                INSERT IGNORE INTO moxie_transaction (id, amount, note, date, in_sum, income_update, category, user_id)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s);
                """
                # Recolectar los valores de la fila en el orden correcto
                values = (
                    row_dict['id'],
                    float(row_dict['amount']),  # Convertir Decimal a float
                    fixed_col,
                    row_dict['date'].strftime('%Y-%m-%d %H:%M:%S'),  # Formato de fecha
                    row_dict['in_sum'],
                    row_dict['income_update'].strftime('%Y-%m-%d %H:%M:%S'),  # Formato de fecha
                    row_dict['category'],
                    row_dict['user_id']
                )
                # Ejecutar la consulta
                cursor.execute(insert_query, values)

    def _fix_note(self, note, row):
        if isinstance(note, bytes):
            try:
                fixed_col = note.decode('utf-8')
            except UnicodeDecodeError:
                fixed_col = ftfy.fix_encoding(note.decode('latin1', errors='replace'))
            row[4] = fixed_col
        else:
            try:
                fixed_col = note.encode('latin-1').decode('utf-8')
            except (UnicodeDecodeError, UnicodeEncodeError) as e:
                self.stdout.write(self.style.ERROR(e))
                fixed_col = ftfy.fix_encoding(note)
                # fixed_col = note.encode('utf-8').decode('utf-8')
            # except Exception as e:
            #     fixed_col = note.encode('latin-1').decode('latin1').encode('utf-8').decode('utf-8')
        return fixed_col

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
