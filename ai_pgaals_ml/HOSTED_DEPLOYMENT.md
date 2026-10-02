# Hosted ML API Deployment

The Docker Compose stack runs the ML API as a private service named `ml-api`.
Laravel reaches it at `http://ml-api:8001`; the API port is not exposed publicly.
The trained model is stored in the persistent Docker volume `ml_models`, not in
Git.

## Deploy

1. Push the Compose and `ai_pgaals_ml` API changes to the branch configured in
   Coolify, then redeploy the Compose application.
2. Upload `ai_pgaals_ml/models/reading_level_model.pkl` from the training machine
   to the VPS, for example as `/tmp/reading_level_model.pkl` using SCP or SFTP.
3. In an SSH terminal on the VPS host, find the volume Coolify created:

   ```sh
   docker volume ls --filter name=ml_models
   ```

4. Copy the uploaded model into that volume, replacing `<volume-name>` with the
   full name from the previous command:

   ```sh
   docker run --rm \
     -v <volume-name>:/models \
     -v /tmp/reading_level_model.pkl:/tmp/reading_level_model.pkl:ro \
     alpine cp /tmp/reading_level_model.pkl /models/reading_level_model.pkl
   ```

5. Restart the `ml-api` service from Coolify. Its health endpoint should report
   `model_available`: `true`. The endpoint is private to the Compose network;
   use the service terminal or the app container to check it.

The volume persists when Coolify redeploys the containers. When retraining the
model, upload the replacement file and repeat the copy command. Existing
predictions are snapshots and are not recalculated automatically.

Newly submitted activities can receive predictions after the API is connected.
The current model returns a level classification for each submitted result; it
does not yet calculate or display a student growth trend across activities.
