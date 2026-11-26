#!/usr/bin/env bash
# Reusable test runner script
# Usage: ./tests/scripts/run_all_tests.sh (from plugin dir, WordPress root, or any directory)

set -u

# Resolve absolute plugin directory from script location
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"

PHP_BIN="${PHP_BIN:-/Applications/ServBay/package/php/8.4/8.4.27/bin/php}"
PHPUNIT_BIN="${PLUGIN_DIR}/vendor/bin/phpunit"
PHPUNIT_CONFIG="${PLUGIN_DIR}/phpunit.xml.dist"

# Preflight checks
if [ ! -x "${PHP_BIN}" ]; then
    echo "ERROR: PHP binary not found or not executable at '${PHP_BIN}'" >&2
    exit 1
fi

if [ ! -f "${PHPUNIT_BIN}" ]; then
    echo "ERROR: PHPUnit binary not found at '${PHPUNIT_BIN}'" >&2
    exit 1
fi

if [ ! -f "${PHPUNIT_CONFIG}" ]; then
    echo "ERROR: PHPUnit config not found at '${PHPUNIT_CONFIG}'" >&2
    exit 1
fi

# Define test suites (from phpunit.xml.dist)
SUITES=("Architecture" "Integration" "Unit")
LOG_DIR="$(mktemp -d /tmp/test_runs_XXXXXX)"

# Timeouts: 20s per test, 120s wall-clock global cap per suite
PER_TEST_TIMEOUT=20
SUITE_WALL_TIMEOUT=120

echo "Starting test suites in parallel (${SUITES[*]}) from ${PLUGIN_DIR}..."
echo "PHP: ${PHP_BIN}"
echo "Logs: ${LOG_DIR}"
START=$(date +%s)

PIDS=()
for SUITE in "${SUITES[@]}"; do
    (
        "${PHP_BIN}" "${PHPUNIT_BIN}" \
            -c "${PHPUNIT_CONFIG}" \
            --testsuite "${SUITE}" \
            --colors=never \
            --fail-on-warning \
            --enforce-time-limit \
            --default-time-limit "${PER_TEST_TIMEOUT}" \
            --timeout "${SUITE_WALL_TIMEOUT}" \
            > "${LOG_DIR}/${SUITE}.log" 2>&1
    ) &
    PIDS+=("$!")
done

FAILED=0
STATUSES=()

for i in "${!SUITES[@]}"; do
    PID="${PIDS[$i]}"
    if wait "${PID}"; then
        STATUSES+=("0")
    else
        EXIT_CODE=$?
        STATUSES+=("${EXIT_CODE}")
        FAILED=1
    fi
done

echo ""
echo "=== Test Suite Results ==="

for i in "${!SUITES[@]}"; do
    SUITE="${SUITES[$i]}"
    STATUS="${STATUSES[$i]}"
    LOG_FILE="${LOG_DIR}/${SUITE}.log"

    if [ "${STATUS}" -eq 0 ] && grep -q -E "^OK \(" "${LOG_FILE}"; then
        SUMMARY_LINE="$(grep -E "^OK \(" "${LOG_FILE}" | tail -n 1)"
        echo "[PASSED] ${SUITE}: ${SUMMARY_LINE}"
    else
        FAILED=1
        echo "[FAILED] ${SUITE} (exit code: ${STATUS})"
        echo "----------------------------------------------------------------------"
        echo "Failure Details for ${SUITE}:"
        echo "----------------------------------------------------------------------"

        # If PHPUnit reported failures or errors, display the failure details section
        if grep -q -E "There (was|were) [0-9]+ (failure|error)" "${LOG_FILE}"; then
            # Extract from the first failure/error summary line to the end of the log
            awk '/There (was|were) [0-9]+ (failure|error)/{flag=1} flag' "${LOG_FILE}"
        elif grep -q -E "(FAILURES!|ERRORS!)" "${LOG_FILE}"; then
            awk '/(FAILURES!|ERRORS!)/{flag=1} flag' "${LOG_FILE}"
        else
            # Print last 50 lines of log if no standard PHPUnit failure header was found (e.g. fatal error or crash)
            tail -n 50 "${LOG_FILE}"
        fi
        echo "----------------------------------------------------------------------"
    fi
done

END=$(date +%s)
echo ""
echo "Total duration: $((END - START)) seconds"
echo "Logs saved in: ${LOG_DIR}"

exit "${FAILED}"
