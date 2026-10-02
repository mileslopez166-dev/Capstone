# Hosted ML API Deployment

The Docker Compose stack runs the ML API as a private service named `ml-api`.
Laravel reaches it at `http://ml-api:8001`; the API port is not exposed publicly.
The trained model is packaged into the ML API image at
`/models/reading_level_model.pkl`. Raw training datasets are not included.

## Deploy with Dokploy

1. Select the `main` branch and the repository's `compose.yaml`.
2. Deploy or rebuild the Compose application. The ML Dockerfile copies the model
   into its image, so no server-side volume upload is needed.
3. Check the `ml-api` service logs or its internal `/health` endpoint; it should
   report `model_available: true`.

New assessment submissions can receive predictions after deployment. Existing
submissions are not recalculated automatically. When retraining the model,
replace the model artifact and rebuild the image. The model artifact is committed
with the application; raw training datasets are not.

The current model returns a level classification for each submitted result. It
does not yet calculate or display a student growth trend across activities.
