# pull official base image
FROM python:3.11

# set work directory
WORKDIR /usr/src/moxie

# set environment variables
ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1
ENV DEBIAN_FRONTEND noninteractive
ENV TZ=Europe/Madrid

COPY . .

RUN python -m pip install -r requirements.txt

RUN python -m pip install gunicorn

# RUN yarn install

# RUN python manage.py collectstatic --noinput

CMD ["gunicorn"  , "--preload", "-b", "0.0.0.0:8000", "--workers", "4", "--timeout", "60", "--bind", "unix:/tmp/app.sock", "--worker-tmp-dir", "/tmp", "moxie.wsgi:application"]
