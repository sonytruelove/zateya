#!/usr/bin/env bash
# Пере-рендер всех диаграмм проекта «Затея».
# Исходники: docs/diagrams/src/*.puml (PlantUML) и docs/diagrams/src/*.mmd (Mermaid).
# Результат: docs/diagrams/out/*.svg (имена совпадают с исходниками).
#
# Требования:
#   - Java 21+ в PATH (java -version).
#   - Node.js в PATH (для Mermaid; используется npx @mermaid-js/mermaid-cli).
#   - Файл .tools/plantuml.jar. Если его нет — скрипт попытается скачать релиз.
#
# Запуск:  bash docs/diagrams/render.sh
#
# Примечание: скрипт переходит в свой каталог и вызывает инструменты по
# ОТНОСИТЕЛЬНЫМ путям — так рендер не ломается на пути с не-ASCII символами.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

PLANTUML_JAR=".tools/plantuml.jar"
PUPPETEER_CFG=".tools/puppeteer.json"
PLANTUML_VERSION="1.2025.4"

mkdir -p out .tools

if [[ ! -f "$PLANTUML_JAR" ]]; then
  echo "plantuml.jar не найден — скачиваю релиз $PLANTUML_VERSION…"
  curl -sL -o "$PLANTUML_JAR" \
    "https://github.com/plantuml/plantuml/releases/download/v${PLANTUML_VERSION}/plantuml-mit-${PLANTUML_VERSION}.jar"
fi

if [[ ! -f "$PUPPETEER_CFG" ]]; then
  echo '{ "args": ["--no-sandbox", "--disable-setuid-sandbox"] }' > "$PUPPETEER_CFG"
fi

echo "== PlantUML =="
# Smetana-раскладка задана внутри каждого .puml (!pragma layout smetana), graphviz не нужен.
for f in src/*.puml; do
  [[ -e "$f" ]] || continue
  echo "  $f -> out/$(basename "${f%.puml}").svg"
  java -jar "$PLANTUML_JAR" -tsvg -nometadata -o ../out "$f"
done

echo "== Mermaid =="
for f in src/*.mmd; do
  [[ -e "$f" ]] || continue
  name="$(basename "${f%.mmd}")"
  echo "  $f -> out/$name.svg"
  npx --yes @mermaid-js/mermaid-cli -i "$f" -o "out/$name.svg" -p "$PUPPETEER_CFG" -b white
done

echo "Готово. Результат в: $(pwd)/out"
