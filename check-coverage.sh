#!/bin/bash

# Coverage Checker
# 
# Parses PHPUnit output and validates that all coverage metrics meet threshold.
# 
# Usage: 
#   XDEBUG_MODE=coverage vendor/bin/phpunit --path-coverage --coverage-text --only-summary-for-coverage-text | ./check-coverage.sh [threshold]

set -e
set -o pipefail

REQUIRED_COVERAGE="${1:-100.0}"

# Read all input from stdin
OUTPUT=$(cat)

# Check if PHPUnit ran successfully
if echo "$OUTPUT" | grep -q "FAILURES\|ERRORS"; then
    echo "❌ Error: PHPUnit tests failed"
    echo "$OUTPUT"
    exit 1
fi

# Extract coverage metrics
LINE_COV=$(echo "$OUTPUT" | grep -oP 'Lines:\s+\K[\d.]+(?=%)')
BRANCH_COV=$(echo "$OUTPUT" | grep -oP 'Branches:\s+\K[\d.]+(?=%)')
METHOD_COV=$(echo "$OUTPUT" | grep -oP 'Methods:\s+\K[\d.]+(?=%)')
PATH_COV=$(echo "$OUTPUT" | grep -oP 'Paths:\s+\K[\d.]+(?=%)')

if [ -z "$PATH_COV" ]; then
    echo "❌ Error: Could not parse path coverage from output"
    echo "$OUTPUT"
    exit 1
fi

# Extract counts
LINE_COUNTS=$(echo "$OUTPUT" | grep -oP 'Lines:.*?\(\K\d+/\d+')
BRANCH_COUNTS=$(echo "$OUTPUT" | grep -oP 'Branches:.*?\(\K\d+/\d+')
METHOD_COUNTS=$(echo "$OUTPUT" | grep -oP 'Methods:.*?\(\K\d+/\d+')
PATH_COUNTS=$(echo "$OUTPUT" | grep -oP 'Paths:.*?\(\K\d+/\d+')

# Display report
echo "═══════════════════════════════════════════════════════"
echo "               COVERAGE REPORT                         "
echo "═══════════════════════════════════════════════════════"
echo ""

# Check each metric
LINE_PASS=$(awk -v cov="$LINE_COV" -v req="$REQUIRED_COVERAGE" 'BEGIN {print (cov >= req) ? 1 : 0}')
BRANCH_PASS=$(awk -v cov="$BRANCH_COV" -v req="$REQUIRED_COVERAGE" 'BEGIN {print (cov >= req) ? 1 : 0}')
METHOD_PASS=$(awk -v cov="$METHOD_COV" -v req="$REQUIRED_COVERAGE" 'BEGIN {print (cov >= req) ? 1 : 0}')
PATH_PASS=$(awk -v cov="$PATH_COV" -v req="$REQUIRED_COVERAGE" 'BEGIN {print (cov >= req) ? 1 : 0}')

[ "$LINE_PASS" -eq 1 ] && LINE_ICON="✓" || LINE_ICON="✗"
[ "$BRANCH_PASS" -eq 1 ] && BRANCH_ICON="✓" || BRANCH_ICON="✗"
[ "$METHOD_PASS" -eq 1 ] && METHOD_ICON="✓" || METHOD_ICON="✗"
[ "$PATH_PASS" -eq 1 ] && PATH_ICON="✓" || PATH_ICON="✗"

printf "  Line Coverage:                %s %.2f%% (%s)\n" "$LINE_ICON" "$LINE_COV" "$LINE_COUNTS"
printf "  Branch Coverage:              %s %.2f%% (%s)\n" "$BRANCH_ICON" "$BRANCH_COV" "$BRANCH_COUNTS"
printf "  Method Coverage:              %s %.2f%% (%s)\n" "$METHOD_ICON" "$METHOD_COV" "$METHOD_COUNTS"
printf "  Path Coverage:                %s %.2f%% (%s)\n" "$PATH_ICON" "$PATH_COV" "$PATH_COUNTS"

echo ""
echo "───────────────────────────────────────────────────────"

if [ "$LINE_PASS" -eq 1 ] && [ "$BRANCH_PASS" -eq 1 ] && [ "$METHOD_PASS" -eq 1 ] && [ "$PATH_PASS" -eq 1 ]; then
    echo "  ✓ All coverage metrics meet ${REQUIRED_COVERAGE}% threshold"
    echo "═══════════════════════════════════════════════════════"
    echo ""
    exit 0
fi

echo "  ✗ Coverage check FAILED - Not all metrics at ${REQUIRED_COVERAGE}%"
echo "═══════════════════════════════════════════════════════"
echo ""

[ "$LINE_PASS" -eq 0 ] && echo "  ⚠ Line coverage below ${REQUIRED_COVERAGE}%"
[ "$BRANCH_PASS" -eq 0 ] && echo "  ⚠ Branch coverage below ${REQUIRED_COVERAGE}%"
[ "$METHOD_PASS" -eq 0 ] && echo "  ⚠ Method coverage below ${REQUIRED_COVERAGE}%"
[ "$PATH_PASS" -eq 0 ] && echo "  ⚠ Path coverage below ${REQUIRED_COVERAGE}%"

echo ""
exit 1
