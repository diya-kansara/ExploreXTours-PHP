#!/usr/bin/env bash
# Rebuilds database/explorex_v2_fresh_install.sql = original v1 data + migrations 001-004.
# (Only needed if you edit the migrations; the built file is committed.)
set -euo pipefail
cd "$(dirname "$0")"
OUT=explorex_v2_fresh_install.sql
{
  echo "-- ============================================================================"
  echo "-- ExploreX Tours 2.0 - FRESH INSTALL for a NEW / EMPTY database."
  echo "-- Creates database \`tours_travels\`, loads the original v1 data, then applies"
  echo "-- migrations 001-004. WARNING: it contains DROP TABLE statements, so do NOT import"
  echo "-- it into a database whose data you want to keep. To upgrade an existing v1"
  echo "-- database, run 001, 002, 003 and 004 in order instead (see README)."
  echo "-- ============================================================================"
  echo "CREATE DATABASE IF NOT EXISTS \`tours_travels\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  echo "USE \`tours_travels\`;"
  echo
  echo "-- ---------- part 1: original v1 export (unchanged data) ----------"
  sed 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' legacy/tours_travels_v1.sql
  echo
  echo "SET time_zone = 'SYSTEM';"
  echo "SET SQL_MODE = '';"
  echo "SET NAMES utf8mb4;"
  for f in 001_innodb_core_schema 002_migrate_legacy_data 003_enrich_demo_catalog 004_drop_legacy_columns; do
    echo; echo "-- ---------- part: migration $f ----------"
    cat "$f.sql"
  done
} > "$OUT"
echo "built $OUT ($(wc -l < "$OUT") lines)"
