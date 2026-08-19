# pay-prod-healer

Cloud Function (2nd gen, `europe-central2`), automatycznie restartuje produkcyjną
VM (`instance-20260615-112018`), jesli zostala zatrzymana przez kogos innego niz
konto z domeny `@please-support-me.com`. Wyzwalana przez log sink
`pay-prod-instance-stop-sink` -> Pub/Sub topic `pay-prod-instance-stop`.

## Deploy

```bash
cd infra/pay-prod-healer
gcloud functions deploy pay-prod-healer \
  --gen2 \
  --project=please-support-me-499509 \
  --region=europe-central2 \
  --runtime=python312 \
  --source=. \
  --entry-point=handle_stop_event \
  --trigger-topic=pay-prod-instance-stop \
  --service-account=pay-prod-healer@please-support-me-499509.iam.gserviceaccount.com \
  --memory=512Mi \
  --timeout=60s \
  --max-instances=3 \
  --no-allow-unauthenticated
```

Uzywa dedykowanego service account `pay-prod-healer@please-support-me-499509.iam.gserviceaccount.com`
z custom rola `payProdHealerRestart` (tylko `compute.instances.start` + `compute.instances.get`),
ograniczonej przez IAM Condition do tej jednej instancji.

Zwiazane zasoby monitoringu (uptime check + alert po 5 min nieudanego healu) skonfigurowane
recznie w Cloud Monitoring, nie w tym repo.
