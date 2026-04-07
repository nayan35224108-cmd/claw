#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:3001}"
TO_EMAIL="${2:-}"

post_json() {
  local url="$1"
  local data="$2"
  curl -sS -X POST "$url" \
    -H 'Content-Type: application/json' \
    -d "$data" \
    -w '\nHTTP_STATUS:%{http_code}\n'
}

get_url() {
  local url="$1"
  curl -sS "$url" -w '\nHTTP_STATUS:%{http_code}\n'
}

echo "[1/5] Health check: $BASE_URL/api/health"
HEALTH="$(get_url "$BASE_URL/api/health")"
echo "$HEALTH"

echo "[2/5] Create invoice"
TODAY="$(date +%F)"
CREATE_PAYLOAD=$(cat <<JSON
{
  "tanggal": "$TODAY",
  "pengirim": {"name": "QA Bot", "email": "qa@example.com"},
  "klien": {"name": "Test Client", "email": "${TO_EMAIL:-client@example.com}"},
  "items": [{"desc": "Service Fee", "qty": 1, "price": 100000}],
  "subtotal": 100000,
  "pajak": 11000,
  "diskon": 0,
  "total": 111000,
  "status": "draft"
}
JSON
)
CREATE_RAW="$(post_json "$BASE_URL/api/invoice" "$CREATE_PAYLOAD")"
echo "$CREATE_RAW"

CREATE_BODY="$(printf '%s\n' "$CREATE_RAW" | sed '/^HTTP_STATUS:/d')"
INVOICE_ID="$(python3 - <<'PY' "$CREATE_BODY"
import json,sys
try:
    print(json.loads(sys.argv[1]).get('id',''))
except Exception:
    print('')
PY
)"

if [[ -z "$INVOICE_ID" ]]; then
  echo "ERROR: Failed to parse created invoice id"
  exit 1
fi

echo "Parsed invoice id: $INVOICE_ID"

echo "[3/5] List invoices"
get_url "$BASE_URL/api/invoices"

echo "[4/5] Get invoice by id"
get_url "$BASE_URL/api/invoice/$INVOICE_ID"

if [[ -n "$TO_EMAIL" ]]; then
  echo "[5/5] Send invoice email to: $TO_EMAIL"
  SEND_PAYLOAD="{\"to\":\"$TO_EMAIL\"}"
  post_json "$BASE_URL/api/invoice/$INVOICE_ID/send-email" "$SEND_PAYLOAD"
else
  echo "[5/5] Skip send-email (pass recipient as 2nd arg to enable)"
  echo "Example: $0 $BASE_URL recipient@example.com"
fi

echo "Smoke test finished."
