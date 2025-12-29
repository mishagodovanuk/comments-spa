#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"

TARGET="${ROOT_DIR}/storage/app/public"
LINK="${ROOT_DIR}/public/storage"

mkdir -p "${TARGET}"

if [ -L "${LINK}" ]; then
  CURRENT="$(readlink "${LINK}" || true)"
  if [ "${CURRENT}" != "${TARGET}" ]; then
    rm -f "${LINK}"
  fi
fi

if [ -e "${LINK}" ] && [ ! -L "${LINK}" ]; then
  rm -rf "${LINK}"
fi

if [ ! -e "${LINK}" ]; then
  ln -s "${TARGET}" "${LINK}"
fi
