# Moxie

Moxie is an application that will allow you to control home expenses, incomes and to elaborate a home budget. You will also be able to adapt the categories and subcategories to your own needs.

If you need more than a spreadsheet, or you have never controlled your home economy, this application can help you to have a better control of it.

Moxie is built with Python3+Django stack, plus jQuery for the front-end.

See it online and register at [https://moxie.dootic.com](https://moxie.dootic.com)

## Requirements and dependencies

Please check requirements.txt for Python + Django.

### .env
Note that there is a .env t``emplate. Copy this file to a .env file and edit the contents to match your environment.

### Docker

In the root folder there are a Dockerfile and a composer.yml files to build a container to help development in local and run tests.

You can either run it once built with

> docker run --env-file .env -p 8000:8000 --network bridge hmeza/moxie:dev

or use compose.yml to run it in the same way.

There are also Dockerfile and composer.yml files for production environment in the infrastructure folder.

## Deployment

If deploying to production, remember to do a

> yarn install

> python3 manage.py collectstatic

before start using it.
If you find that no styles are loaded, these two steps are required. 