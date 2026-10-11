# Hosted ML API Deployment

The Docker Compose stack runs the ML API as a private service named `ml-api`.
Laravel reaches it at `http://ml-api:8001`; the API port is not exposed publicly.
The baseline model is copied into the persistent `ml_models` volume on first
startup. New comparison artifacts and the active-model registry are also stored
there. Raw training datasets are not included in the image.

## Deploy with Dokploy

1. Select `main` (the production branch) and the repository's `compose.yaml`.
2. Set a long random `ML_ADMIN_TOKEN` in the Compose environment. Laravel and
   the ML API receive the same value.
   Set `QUEUE_CONNECTION=database`, replacing any previous `sync` value.
3. Deploy or rebuild the Compose application. Confirm that the `app`, `queue`,
   `ml-api`, and `database` services are running.
4. Run `php artisan migrate --force` in the app container.
   Then run `php artisan queue:restart`. This release adds the AI evaluation,
   recommendation, and queue tables, plus the per-question assessment timer.
5. Keep the `ml_models` volume during redeployment so trained artifacts and the
   active registry survive image replacement.
6. Check the `ml-api` service logs or its internal `/health` endpoint. It should
   report `model_available: true` and `admin_enabled: true`.

The queue service processes administrator comparisons on the `ai` queue.
Its 1200-second timeout is shorter than the 1260-second database retry window.
The PHP image includes PCNTL for worker timeouts and graceful stops.
Administrator evaluations train from anonymized application records through the
private API. Activation always names an exact validated model version. Do not
publish the ML API port or accept user-uploaded pickle files.

New assessment submissions can receive predictions after deployment. Existing
submissions are not recalculated automatically. Official assessment scores are
saved even when the ML API is unavailable.

See `docs/ai-algorithm-comparison.md` for data requirements, evaluation rules,
activation, rollback, and known limitations.
