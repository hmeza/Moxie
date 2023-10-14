# pull official base image
FROM python:3.11

# set work directory
WORKDIR /usr/src/moxie

# set environment variables
ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1
ENV DEBIAN_FRONTEND noninteractive
ENV TZ=Europe/Madrid

RUN apt-get update -y && apt-get install -y python3-pymysql

COPY requirements.txt requirements.txt

RUN python -m pip install -r requirements.txt

CMD ["python", "manage.py", "runserver", "0.0.0.0:8000"]
