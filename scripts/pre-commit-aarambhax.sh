#!/usr/bin/env bash
# Optional git hook: refresh site.css ?v= (CSS bundle content hash) when HTML/CSS under the site is staged.
# Install from studyai repo root:
#   cp _external/aarambhax-website/scripts/pre-commit-aarambhax.sh .git/hooks/pre-commit
#   chmod +x .git/hooks/pre-commit

set -euo pipefail
ROOT=$(git rev-parse --show-toplevel)
WEB="$ROOT/_external/aarambhax-website"
[[ -d "$WEB" ]] || exit 0
STAGED=$(git diff --cached --name-only)
if ! echo "$STAGED" | grep -qE '^_external/aarambhax-website/.*\.(html|css)$'; then
  exit 0
fi
python3 "$WEB/scripts/set_css_cache_bust.py"
find "$WEB" -name '*.html' -exec git add {} +
