FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    ML_MODEL_PATH=/models/reading_level_model.pkl

WORKDIR /app

COPY requirements-api.txt ./requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY api ./api

EXPOSE 8001

CMD ["uvicorn", "api.main:app", "--host", "0.0.0.0", "--port", "8001"]
