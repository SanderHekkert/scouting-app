#!/usr/bin/env bash
set -euo pipefail

OWNER="${GHCR_OWNER:-sanderhekkert}"
OWNER="$(echo "$OWNER" | tr '[:upper:]' '[:lower:]')"
REGISTRY="ghcr.io"
TAG="${1:-latest}"
VITE_KEY="${VITE_WEBPUSH_VAPID_PUBLIC_KEY:-}"

if [ -z "${GHCR_TOKEN:-}" ]; then
    echo "Set GHCR_TOKEN (GitHub PAT with write:packages) and optionally GHCR_OWNER."
    echo "Example: GHCR_TOKEN=ghp_... ./docker/publish-ghcr.sh latest"
    exit 1
fi

echo "$GHCR_TOKEN" | docker login "$REGISTRY" -u "$OWNER" --password-stdin

build_args=(--build-arg "VITE_APP_NAME=FN12 App")
if [ -n "$VITE_KEY" ]; then
    build_args+=(--build-arg "VITE_WEBPUSH_VAPID_PUBLIC_KEY=$VITE_KEY")
fi

docker build --target php "${build_args[@]}" -t "$REGISTRY/$OWNER/scouting-app-php:$TAG" .
docker build --target web "${build_args[@]}" -t "$REGISTRY/$OWNER/scouting-app-web:$TAG" .

docker push "$REGISTRY/$OWNER/scouting-app-php:$TAG"
docker push "$REGISTRY/$OWNER/scouting-app-web:$TAG"

echo "Published:"
echo "  $REGISTRY/$OWNER/scouting-app-php:$TAG"
echo "  $REGISTRY/$OWNER/scouting-app-web:$TAG"
