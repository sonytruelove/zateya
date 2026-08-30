#!/usr/bin/env bash
# Гаунтлет «Затеи»: единый прогон всех проверок качества и безопасности.
# Заменяет построчное чтение изменений отчётом метрик (методология code-gauntlet).
#
# Использование: scripts/quality_gate.sh [--fast]
#   --fast  — пропустить мутационное тестирование (долгий шаг)

set -uo pipefail
cd "$(dirname "$0")/.."

FAST=0
[ "${1:-}" = "--fast" ] && FAST=1
PASS=1
line() { printf '%-12s %-34s %s\n' "$1" "$2" "$3"; }

run() {
    local label="$1"; shift
    if "$@" >/tmp/gate.$$ 2>&1; then
        line "OK" "$label" ""
    else
        line "FAIL" "$label" "см. вывод ниже"
        sed 's/^/    /' /tmp/gate.$$ | tail -40
        PASS=0
    fi
}

echo "== Гаунтлет «Затеи» =="

run "формат кода"          php vendor/bin/php-cs-fixer fix --dry-run --diff
run "статический анализ"   php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress
run "границы слоёв"        php vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress
run "цикломат. сложность"  php vendor/bin/phpmd src text phpmd.xml
run "модульные тесты"      php -d pcov.enabled=1 vendor/bin/phpunit
run "приёмочные сценарии"  php vendor/bin/behat -n --no-colors
run "аудит зависимостей"   composer audit --no-interaction

if command -v semgrep >/dev/null 2>&1; then
    run "SAST (semgrep)" semgrep --config p/php --config p/security-audit --config p/owasp-top-ten \
        --error --metrics=off --quiet --exclude vendor --exclude frontend --exclude node_modules src
else
    line "SKIP" "SAST (semgrep)" "pip install semgrep — обязателен в конвейере"
fi

if [ "$FAST" -eq 0 ]; then
    run "мутационное тестирование" php -d pcov.enabled=1 vendor/bin/infection \
        --min-msi=72 --min-covered-msi=72 --threads=4 --only-covered --no-progress --no-interaction
else
    line "SKIP" "мутационное тестирование" "исключено флагом --fast"
fi

echo "======================"
if [ "$PASS" -eq 1 ]; then
    echo "ГЕЙТ: ПРОЙДЕН — задачу можно считать выполненной без построчного чтения кода."
    exit 0
fi
echo "ГЕЙТ: НЕ ПРОЙДЕН — вернуться к реализации и починить, прежде чем звать человека."
exit 1
