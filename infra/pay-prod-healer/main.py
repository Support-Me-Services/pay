import base64
import json
import logging

import functions_framework
from google.cloud import compute_v1

logging.basicConfig(level=logging.INFO, force=True)

PROJECT = "please-support-me-499509"
ZONE = "europe-central2-a"
INSTANCE = "instance-20260615-112018"

# Jesli stop wykonal ktos z domeny firmowej (recznie, np. do konserwacji),
# healer NIE ma auto-restartowac, bo to zamierzone dzialanie.
HUMAN_DOMAIN = "@please-support-me.com"


@functions_framework.cloud_event
def handle_stop_event(cloud_event):
    data = cloud_event.data
    message = data.get("message", {})
    raw = message.get("data", "")
    payload = json.loads(base64.b64decode(raw).decode("utf-8")) if raw else {}

    proto_payload = payload.get("protoPayload", {})
    principal = (
        proto_payload.get("authenticationInfo", {}).get("principalEmail", "unknown")
    )
    method = proto_payload.get("methodName", "unknown")

    logging.info(
        "Stop event on %s: principal=%s method=%s", INSTANCE, principal, method
    )

    if principal.lower().endswith(HUMAN_DOMAIN):
        logging.info(
            "Stop wykonany recznie przez %s (domena firmowa) - to zamierzone "
            "dzialanie, healer NIE restartuje VM.",
            principal,
        )
        return

    instances_client = compute_v1.InstancesClient()
    instance = instances_client.get(project=PROJECT, zone=ZONE, instance=INSTANCE)

    if instance.status == "RUNNING":
        logging.info("Instancja juz dziala (status=%s) - nic do zrobienia.", instance.status)
        return

    logging.warning(
        "Stop niezainicjowany przez czlowieka (principal=%s) - restartuje instancje %s.",
        principal,
        INSTANCE,
    )
    operation = instances_client.start(project=PROJECT, zone=ZONE, instance=INSTANCE)
    logging.info("Zlecono start instancji, operation id=%s", operation.name)
