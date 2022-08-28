#!/usr/bin/env bash

set -e

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [[ "$APP_ENV" != "" ]]; then
    ENV="$APP_ENV"
else
    ENV="dev"
fi

source "${SCRIPT_FOLDER}/../.env"
[ -f "${SCRIPT_FOLDER}/../.env.local" ] && source "${SCRIPT_FOLDER}/../.env.local" || true
[ -f "${SCRIPT_FOLDER}/../.env.${ENV}" ] && source "${SCRIPT_FOLDER}/../.env.${ENV}" || true
[ -f "${SCRIPT_FOLDER}/../.env.${ENV}.local" ] && source "${SCRIPT_FOLDER}/../.env.${ENV}.local" || true
