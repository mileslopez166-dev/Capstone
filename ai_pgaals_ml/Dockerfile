FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    ML_MODEL_PATH=/models/reading_level_model.pkl \
    ML_MODEL_ROOT=/models

WORKDIR /app

COPY requirements-api.txt ./requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY api ./api
COPY training ./training
COPY models/reading_level_model.pkl /app/default_models/reading_level_model.pkl

EXPOSE 8001

CMD ["sh", "-c", "mkdir -p /models && if [ ! -f /models/reading_level_model.pkl ]; then cp /app/default_models/reading_level_model.pkl /models/reading_level_model.pkl; fi && exec uvicorn api.main:app --host 0.0.0.0 --port 8001"]
