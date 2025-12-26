#!/usr/bin/env bash
set -e

TARGET="../storage/app/public"
LINK="public/storage"

if [ -L "$LINK" ]; then
  CURRENT="$(readlink "$LINK")"
  if [ "$CURRENT" != "$TARGET" ]; then
    rm -f "$LINK"
  fi
fi

if [ ! -e "$LINK" ]; then
  ln -s "$TARGET" "$LINK"
fi
