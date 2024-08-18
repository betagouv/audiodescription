FROM python:3.12

ENV TZ="Europe/Paris"

RUN pip install poetry

COPY ./src /usr/app/src

WORKDIR /usr/app/src

RUN poetry install