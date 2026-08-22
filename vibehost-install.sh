#!/bin/sh
# VibeHost CLI installer (native binary, v3+).
#
# One-liner:   curl -sSL https://vibehost.com/install.sh | sh
#
# What it does:
#   1. Detects OS / arch (with Rosetta + musl fallbacks).
#   2. Resolves the target version from /dl/latest (or $VIBEHOST_VERSION).
#   3. Fetches /dl/<ver>/manifest.json, picks the platform descriptor,
#      downloads the binary, verifies SHA256.
#   4. Installs into ~/.vibehost/versions/<ver>/<binary>.
#   5. Atomically swaps ~/.vibehost/cli → ~/.vibehost/versions/<ver>.
#   6. Symlinks ~/.local/bin/vibehost → ~/.vibehost/cli/<binary>
#      (or another writable PATH dir).
#   7. Migrates legacy 2.x flat installs aside, then prunes versions
#      to keep the last two.
#
# Versioned layout means an in-flight `vibehost` invocation keeps
# running off its inode while the next launch picks up the new
# symlink target. Auto-update relies on this guarantee.
#
# Environment variables:
#   VIBEHOST_BASE_URL  — override host (default https://vibehost.com)
#   VIBEHOST_VERSION   — pin a version, e.g. "3.0.0" or "latest" (default)
#   VIBEHOST_PREFIX    — where to put the bin shim (skip auto-detect)
#   VIBEHOST_NO_SHIM   — just unpack, don't write a bin shim (for CI)
#   VIBEHOST_PLATFORM  — override detected platform (e.g. linux-x64-musl)
#   VIBEHOST_NO_VERIFY — skip SHA256 (only for self-hosted instances
#                        without a manifest endpoint)
#   VIBEHOST_NO_TELEMETRY — set to any non-empty value to suppress the
#                        anonymous install outcome beacon (platform,
#                        shell version, success/fail, elapsed). See
#                        docs/superpowers/specs/2026-05-23-installer-
#                        telemetry-design.md for what's sent.
#   VIBEHOST_TELEMETRY_URL — override telemetry endpoint (default
#                        https://api.vibehost.com/api/v1/install-event).
#                        Separate from BASE_URL because Phase 5 (#969)
#                        308-redirects vibehost.com/api/v1/* to
#                        app.vibehost.com, and curl -X POST does not
#                        follow 308 by default — sending to the api
#                        host directly avoids the redirect entirely.

set -eu

BASE_URL="${VIBEHOST_BASE_URL:-https://vibehost.com}"
# Telemetry endpoint defaults to the api host directly. Marketing host
# (vibehost.com) 308-redirects /api/v1/* to app.vibehost.com after
# Phase 5 — POST requests would drop on the floor instead of following.
TELEMETRY_URL="${VIBEHOST_TELEMETRY_URL:-https://api.vibehost.com/api/v1/install-event}"
VIBEHOST_DIR="${HOME}/.vibehost"
VERSIONS_DIR="${VIBEHOST_DIR}/versions"
CURRENT_LINK="${VIBEHOST_DIR}/cli"
TMP_DIR=""

# Set by the release pipeline before upload to R2. The placeholder
# token is intentional shell-invalid junk on its own (`__…__` would
# only ever appear here as a literal placeholder waiting for `sed`).
# If you see this token in your installer log instead of a git SHA,
# the publish step's substitution didn't run.
INSTALLER_VERSION="cb561306"

# Telemetry state: written as the script walks through phases so the
# EXIT trap knows which step we were on when (and if) something
# blew up. Plain shell var, no exotic indirection.
INSTALL_PHASE=""
INSTALL_START_EPOCH="$(date +%s 2>/dev/null || echo 0)"
INSTALL_PLATFORM=""
INSTALL_RESOLVED_VERSION=""

say() { printf '%s\n' "$*"; }
warn() { printf 'warn: %s\n' "$*" >&2; }
die() {
  printf 'error: %s\n' "$*" >&2
  exit 1
}

cleanup() {
  if [ -n "$TMP_DIR" ] && [ -d "$TMP_DIR" ]; then
    rm -rf "$TMP_DIR"
  fi
}

# Anonymous install-outcome beacon. Runs from the EXIT trap so the
# server learns about failures (and successes) without the script
# changing its own exit code. Fire-and-forget: a telemetry POST must
# NEVER fail the install. Opt-out via VIBEHOST_NO_TELEMETRY=1.
#
# Payload contract is defined in
# packages/shared/src/dto/install-event.ts — keep the field names and
# enum values in sync, or the server rejects the event (strict Zod).
# Minimal JSON string-value escape: backslash and double-quote get
# backslashed; everything else (including control chars in the rare
# weird $BASH_VERSION) is passed through. We don't ship through every
# JSON corner case because the server validates with Zod regexes that
# would reject anything weirder than that anyway -- this just keeps
# the request itself parseable so the validation can happen at all.
_json_escape() {
  printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g'
}

send_telemetry() {
  status="$1"
  [ -n "${VIBEHOST_NO_TELEMETRY:-}" ] && return 0
  command -v curl >/dev/null 2>&1 || return 0
  outcome="success"
  [ "$status" -eq 0 ] || outcome="error"
  now="$(date +%s 2>/dev/null || echo 0)"
  elapsed_ms=0
  if [ "$INSTALL_START_EPOCH" -gt 0 ] && [ "$now" -gt "$INSTALL_START_EPOCH" ]; then
    elapsed_ms=$(( (now - INSTALL_START_EPOCH) * 1000 ))
  fi
  # Server Zod caps elapsed_ms at 3,600,000 (one hour). A laptop that
  # slept mid-install could blow past that and the event would be
  # silently rejected. Clamp here to match install.ps1 and keep the
  # outlier visible as "took an hour" rather than telemetric dark
  # matter.
  if [ "$elapsed_ms" -gt 3600000 ]; then
    elapsed_ms=3600000
  fi
  shell_ver="${BASH_VERSION:-}"
  esc_phase=$(_json_escape "$INSTALL_PHASE")
  esc_plat=$(_json_escape "$INSTALL_PLATFORM")
  esc_shell=$(_json_escape "$shell_ver")
  esc_cli=$(_json_escape "$INSTALL_RESOLVED_VERSION")
  esc_iv=$(_json_escape "$INSTALLER_VERSION")
  # JSON assembled by hand. Every field this writes is constrained by
  # the Zod schema on the server, so we can't smuggle anything new
  # in -- but the script can craft a request that's missing fields,
  # which the server will reject silently (204 no-op). Keep this in
  # lockstep with install-event.ts.
  if [ "$outcome" = "error" ] && [ -n "$INSTALL_PHASE" ]; then
    body=$(printf '{"outcome":"%s","error_phase":"%s","platform":"%s","installer":"sh","shell_version":"%s","cli_version":"%s","installer_version":"%s","elapsed_ms":%d}' \
      "$outcome" "$esc_phase" "$esc_plat" "$esc_shell" "$esc_cli" "$esc_iv" "$elapsed_ms")
  else
    body=$(printf '{"outcome":"%s","platform":"%s","installer":"sh","shell_version":"%s","cli_version":"%s","installer_version":"%s","elapsed_ms":%d}' \
      "$outcome" "$esc_plat" "$esc_shell" "$esc_cli" "$esc_iv" "$elapsed_ms")
  fi
  curl -fsS --max-time 5 -X POST \
    -H 'Content-Type: application/json' \
    -d "$body" \
    "$TELEMETRY_URL" >/dev/null 2>&1 || true
}

# Chain telemetry into the cleanup trap so we don't replace cleanup's
# tmpdir-rm. Order: telemetry first (capture $? while it's still the
# real exit status), then the existing cleanup logic.
#
# Guard against double-firing: bash runs INT/TERM traps AND THEN the
# EXIT trap, so an unguarded chain on `trap … EXIT INT TERM` would
# POST twice on Ctrl+C. The guard lets the first invocation win and
# the subsequent EXIT trap no-op the telemetry but still run cleanup.
_telemetry_fired=0
_on_exit() {
  status=$?
  if [ "$_telemetry_fired" -eq 0 ]; then
    _telemetry_fired=1
    send_telemetry "$status"
  fi
  cleanup
}
trap _on_exit EXIT INT TERM

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "'$1' is required but not installed."
}

# Every version string the script consumes — env override, manifest
# response — runs through this gate before being interpolated into a
# path or URL. Mirrors apps/cli/src/auto-update.ts:VERSION_RE.
validate_version_id() {
  ver="$1"
  context="$2"
  case "$ver" in
    "" ) die "empty version id from $context" ;;
  esac
  # `-` MUST sit at the start or end of a POSIX character class —
  # anywhere else, BSD grep (the macOS default) treats it as a range
  # delimiter and refuses with `invalid character range`. The previous
  # form `\-` worked under GNU grep but blew up on macOS, so the
  # update path silently broke for every Mac user.
  if ! printf '%s' "$ver" | grep -qE '^[a-zA-Z0-9._-]+$'; then
    die "invalid version id from $context: '$ver' (must match [a-zA-Z0-9._-]+)"
  fi
  case "$ver" in
    *..*) die "invalid version id from $context: '$ver' (contains '..')" ;;
  esac
}

# Detect the canonical platform id for this host. Output matches
# packages/shared/src/dto/release.ts:PLATFORM_IDS so the URL we POST to
# r2 lines up with the manifest entry the CLI parses.
detect_platform() {
  if [ -n "${VIBEHOST_PLATFORM:-}" ]; then
    printf '%s' "$VIBEHOST_PLATFORM"
    return
  fi

  case "$(uname -s)" in
    Darwin) os="darwin" ;;
    Linux) os="linux" ;;
    MINGW*|MSYS*|CYGWIN*)
      die "Windows is not supported by this installer. See https://vibehost.com/docs."
      ;;
    *) die "unsupported operating system: $(uname -s)" ;;
  esac

  case "$(uname -m)" in
    x86_64|amd64) arch="x64" ;;
    arm64|aarch64) arch="arm64" ;;
    *) die "unsupported architecture: $(uname -m)" ;;
  esac

  # Rosetta 2 on Apple Silicon: shell reports x64 but we want native arm64.
  if [ "$os" = "darwin" ] && [ "$arch" = "x64" ]; then
    if [ "$(sysctl -n sysctl.proc_translated 2>/dev/null)" = "1" ]; then
      arch="arm64"
    fi
  fi

  # musl on Linux: separate Bun runtime, separate binary.
  if [ "$os" = "linux" ]; then
    if [ -f /lib/libc.musl-x86_64.so.1 ] \
        || [ -f /lib/libc.musl-aarch64.so.1 ] \
        || ldd /bin/ls 2>&1 | grep -q musl; then
      printf 'linux-%s-musl' "$arch"
      return
    fi
    printf 'linux-%s' "$arch"
    return
  fi

  if [ "$os" = "darwin" ]; then
    printf 'darwin-%s' "$arch"
    return
  fi
}

_detect_curl_retry_flags() {
  # `--retry 3` alone only retries on HTTP 5xx / 408 / 429 and a
  # narrow set of transient connect errors. It does NOT retry once
  # curl has started receiving a response and the stream dies
  # mid-body (HTTP/2 INTERNAL_ERROR, TCP RST, partial download —
  # exactly the CF edge flake we hit downloading the 92 MB binary
  # on 2026-05-23). `--retry-all-errors` (curl 7.71+, 2020) extends
  # retry to any non-zero exit, including stream-mid errors.
  # `--retry-connrefused` covers the case where the next attempt
  # races into a brief connection refusal during edge failover.
  # Fall back without these flags for the rare older curl.
  if curl --help all 2>&1 | grep -q -- '--retry-all-errors'; then
    printf '%s' "--retry 3 --retry-delay 2 --retry-all-errors --retry-connrefused"
  else
    printf '%s' "--retry 3 --retry-delay 2"
  fi
}

http_get() {
  url="$1"
  output="${2:-}"
  if command -v curl >/dev/null 2>&1; then
    # Cache the capability-detected flag set on first call. A typical
    # install hits http_get 3× (latest, manifest, binary); without
    # caching we'd fork `curl --help all | grep` 3 times.
    if [ -z "${VIBEHOST_CURL_RETRY_FLAGS:-}" ]; then
      VIBEHOST_CURL_RETRY_FLAGS=$(_detect_curl_retry_flags)
    fi
    curl_retry="$VIBEHOST_CURL_RETRY_FLAGS"
    if [ -n "$output" ]; then
      # shellcheck disable=SC2086 # word-splitting on $curl_retry is intentional
      curl -fSL $curl_retry -o "$output" "$url"
    else
      # shellcheck disable=SC2086
      curl -fSL $curl_retry "$url"
    fi
  elif command -v wget >/dev/null 2>&1; then
    # wget retries by default (`--tries=20`) on connection refused
    # and mid-stream failures; no extra flag needed.
    if [ -n "$output" ]; then
      wget -qO "$output" "$url"
    else
      wget -qO- "$url"
    fi
  else
    die "need curl or wget to download the CLI."
  fi
}

resolve_version() {
  if [ -n "${VIBEHOST_VERSION:-}" ] && [ "${VIBEHOST_VERSION}" != "latest" ]; then
    validate_version_id "$VIBEHOST_VERSION" "VIBEHOST_VERSION"
    printf '%s' "$VIBEHOST_VERSION"
    return
  fi
  ver=$(http_get "${BASE_URL}/dl/latest" 2>/dev/null | tr -d '[:space:]')
  if [ -z "$ver" ]; then
    die "could not resolve latest version from ${BASE_URL}/dl/latest"
  fi
  validate_version_id "$ver" "${BASE_URL}/dl/latest"
  printf '%s' "$ver"
}

# Three return shapes from resolve_manifest:
#   exit 0, prints "<binary>|<sha256>" → manifest fetched + parsed
#   exit 1                              → manifest doesn't exist (HTTP 4xx)
#   exit 2                              → network / unknown error; caller dies
resolve_platform_descriptor() {
  version="$1"
  platform="$2"
  url="${BASE_URL}/dl/${version}/manifest.json"
  body=""
  if command -v curl >/dev/null 2>&1; then
    body=$(curl -fsSL --retry 3 --retry-delay 2 "$url" 2>/dev/null)
    rc=$?
    case $rc in
      0) ;;
      22) return 1 ;;  # HTTP 4xx (typically 404 — manifest not yet published)
      *) return 2 ;;   # network / DNS / TLS
    esac
  elif command -v wget >/dev/null 2>&1; then
    body=$(wget -qO- "$url" 2>/dev/null) || return 1
  else
    return 2
  fi
  if [ -z "$body" ]; then
    return 1
  fi

  if command -v jq >/dev/null 2>&1; then
    bin=$(printf '%s' "$body" | jq -r --arg p "$platform" \
      '.platforms[$p].binary // empty')
    sha=$(printf '%s' "$body" | jq -r --arg p "$platform" \
      '.platforms[$p].checksum // empty')
  else
    # Pure-shell extraction — manifest schema is fixed, so a regex
    # against the platform key + binary/checksum is robust enough.
    json_one_line=$(printf '%s' "$body" | tr -d '\n\r\t' | sed 's/  */ /g')
    bin=$(printf '%s' "$json_one_line" \
      | sed -n "s/.*\"$platform\"[^}]*\"binary\"[[:space:]]*:[[:space:]]*\"\([a-zA-Z0-9._-]\{1,\}\)\".*/\1/p")
    sha=$(printf '%s' "$json_one_line" \
      | sed -n "s/.*\"$platform\"[^}]*\"checksum\"[[:space:]]*:[[:space:]]*\"\([a-f0-9]\{64\}\)\".*/\1/p")
  fi
  if [ -z "$bin" ] || [ -z "$sha" ]; then
    return 1
  fi
  printf '%s|%s' "$bin" "$sha"
}

verify_sha256() {
  file="$1"
  expected="$2"
  if command -v sha256sum >/dev/null 2>&1; then
    actual=$(sha256sum "$file" | cut -d' ' -f1)
  elif command -v shasum >/dev/null 2>&1; then
    actual=$(shasum -a 256 "$file" | cut -d' ' -f1)
  else
    die "neither sha256sum nor shasum available; cannot verify download integrity. Set VIBEHOST_NO_VERIFY=1 to skip."
  fi
  if [ "$actual" != "$expected" ]; then
    die "checksum mismatch (expected $expected, got $actual)"
  fi
}

# Migrate any legacy 2.x flat install at ~/.vibehost/cli (a real
# directory with dist/index.js inside, not a symlink) so the new
# layout takes over cleanly. We don't try to keep the old contents
# usable — 3.0+ binaries don't speak the old shim.
migrate_legacy_cli() {
  if [ -L "$CURRENT_LINK" ]; then
    return 0
  fi
  if [ ! -d "$CURRENT_LINK" ]; then
    return 0
  fi
  warn "removing legacy 2.x install at $CURRENT_LINK (replaced by native binary)"
  rm -rf "$CURRENT_LINK"
}

# Atomic symlink swap. `ln -snf` replaces the link in place on both
# Linux and macOS; -n is the load-bearing flag — without it, when
# CURRENT_LINK already points at a directory, ln (and mv -f) descend
# into that directory and create the new link *inside* it instead of
# replacing the dir-symlink.
update_symlink() {
  target="$1"
  ln -snf "$target" "$CURRENT_LINK"
}

prune_old_versions() {
  if [ ! -d "$VERSIONS_DIR" ]; then
    return 0
  fi
  to_delete=$(ls -t "$VERSIONS_DIR" 2>/dev/null | tail -n +3 || true)
  if [ -z "$to_delete" ]; then
    return 0
  fi
  current_target=""
  if [ -L "$CURRENT_LINK" ]; then
    current_target=$(basename "$(readlink "$CURRENT_LINK")")
  fi
  printf '%s\n' "$to_delete" | while IFS= read -r name; do
    [ -n "$name" ] || continue
    [ "$name" = "$current_target" ] && continue
    rm -rf "${VERSIONS_DIR}/${name}" 2>/dev/null || true
  done
}

pick_bin_dir() {
  if [ -n "${VIBEHOST_PREFIX:-}" ]; then
    printf '%s' "$VIBEHOST_PREFIX"
    return
  fi
  for candidate in "$HOME/.local/bin" "$HOME/bin" "/usr/local/bin"; do
    case ":$PATH:" in
      *":$candidate:"*)
        if mkdir -p "$candidate" 2>/dev/null && [ -w "$candidate" ]; then
          printf '%s' "$candidate"
          return
        fi
        ;;
    esac
  done
  mkdir -p "$HOME/.local/bin"
  printf '%s' "$HOME/.local/bin"
}

# The shim is now a plain symlink — no Node, no script wrapper. If a
# previous tarball install left a script at the same path, ln -snf
# atomically replaces it.
write_shim() {
  bin_dir="$1"
  binary="$2"
  shim="$bin_dir/vibehost"
  ln -snf "$CURRENT_LINK/$binary" "$shim"
  printf '%s' "$shim"
}

main() {
  say "Installing VibeHost CLI..."
  # Disclosure for the install-time beacon the EXIT trap fires at the
  # end. Printed BEFORE any fallible step so even a failed install
  # (which still emits an outcome=error event) sees this. Suppressed
  # under VIBEHOST_NO_TELEMETRY so an opt-out user doesn't get a
  # contradictory message about what we're not sending.
  if [ -z "${VIBEHOST_NO_TELEMETRY:-}" ]; then
    say "  telemetry: one anonymous event (outcome + platform — no IP, no identity)."
    say "             opt out: re-run with VIBEHOST_NO_TELEMETRY=1   *   https://vibehost.com/privacy"
  fi

  TMP_DIR=$(mktemp -d 2>/dev/null || mktemp -d -t vibehost)

  platform=$(detect_platform)
  say "  platform: $platform"
  INSTALL_PLATFORM="$platform"

  INSTALL_PHASE="manifest_fetch"
  version=$(resolve_version)
  say "  version:  $version"
  INSTALL_RESOLVED_VERSION="$version"

  binary=""
  expected_sha=""
  if [ -z "${VIBEHOST_NO_VERIFY:-}" ]; then
    set +e
    descriptor=$(resolve_platform_descriptor "$version" "$platform")
    rc=$?
    set -e
    case $rc in
      0)
        binary="${descriptor%%|*}"
        expected_sha="${descriptor##*|}"
        ;;
      1)
        die "manifest at ${BASE_URL}/dl/${version}/manifest.json doesn't list ${platform}. Either the release was hotfixed without this platform or your VIBEHOST_PLATFORM is wrong."
        ;;
      *)
        die "could not reach ${BASE_URL}/dl/${version}/manifest.json (network error). Set VIBEHOST_NO_VERIFY=1 to skip verification."
        ;;
    esac
  else
    # NO_VERIFY: skip manifest entirely, assume conventional binary name.
    case "$platform" in
      win32-*) binary="vibehost.exe" ;;
      *) binary="vibehost" ;;
    esac
    warn "VIBEHOST_NO_VERIFY=1 — installing without checksum verification"
  fi

  # Defense-in-depth: validate the binary name before path interpolation.
  case "$binary" in
    "" ) die "manifest descriptor missing binary name" ;;
    *..*|*/*) die "manifest binary name '$binary' contains forbidden characters" ;;
  esac
  # `-` must be first or last in a POSIX character class to avoid BSD/GNU grep incompatibility.
  if ! printf '%s' "$binary" | grep -qE '^[a-zA-Z0-9._-]+$'; then
    die "manifest binary name '$binary' has unexpected shape"
  fi

  INSTALL_PHASE="binary_download"
  url="${BASE_URL}/dist/${version}/${platform}/${binary}"
  staged="$TMP_DIR/$binary"
  say "  fetching: $url"
  http_get "$url" "$staged" || die "download failed: $url"

  if [ -n "$expected_sha" ]; then
    INSTALL_PHASE="sha_verify"
    verify_sha256 "$staged" "$expected_sha"
  fi

  INSTALL_PHASE="extract"
  chmod +x "$staged"

  migrate_legacy_cli

  mkdir -p "$VERSIONS_DIR"
  install_dir="${VERSIONS_DIR}/${version}"
  install_path="${install_dir}/${binary}"
  rm -rf "$install_dir"
  mkdir -p "$install_dir"
  mv "$staged" "$install_path"

  INSTALL_PHASE="symlink"
  update_symlink "$install_dir"
  prune_old_versions

  if [ -z "${VIBEHOST_NO_SHIM:-}" ]; then
    INSTALL_PHASE="path_setup"
    bin_dir=$(pick_bin_dir)
    shim=$(write_shim "$bin_dir" "$binary")
    say ""
    say "Installed vibehost CLI v$version"
    say "  binary:  $shim → $install_path"

    case ":$PATH:" in
      *":$bin_dir:"*) : ;;
      *)
        warn "$bin_dir is not on your PATH. Add this to your shell profile:"
        printf '  export PATH="%s:$PATH"\n' "$bin_dir" >&2
        ;;
    esac

    say ""
    say "Try it:  vibehost --help"
  else
    say "Installed vibehost CLI v$version to $install_path (no shim)"
  fi

  # Reaching here means every fallible step succeeded. Clearing the
  # phase lets the EXIT trap emit `outcome=success` without an
  # error_phase property (otherwise the schema sees a left-over phase
  # from the last guarded step and the funnel data is misleading).
  INSTALL_PHASE=""
}

main "$@"
