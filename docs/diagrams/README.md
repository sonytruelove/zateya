# Архитектурные диаграммы «Затея»

Исходники — в `src/`, отрендеренные SVG — в `out/` (имена совпадают).
Инструменты рендера (`plantuml.jar`, C4-PlantUML, конфиг Puppeteer) лежат в
`.tools/` и в репозиторий не коммитятся (см. корневой `.gitignore`).

## Состав

| Файл | Нотация | Что показывает |
|------|---------|----------------|
| `c4-context` | C4, уровень 1 (контекст), PlantUML + C4-PlantUML | Система «Затея» в окружении: участник (веб / Telegram / VK), организатор кампаний, внешние системы — Telegram Bot API, VK API, Centrifugo, брокер сообщений RabbitMQ. Потоки и протоколы. |
| `c4-container` | C4, уровень 2 (контейнеры), PlantUML + C4-PlantUML | Контейнеры: веб-клиент (SPA, Vue), приложение Laravel 13 / Octane на RoadRunner, воркеры очередей, PostgreSQL, Redis, RabbitMQ, Centrifugo. На стрелках — протоколы (HTTPS/JSON, WebSocket, AMQP, SQL/TCP, RESP). |
| `c4-component-participation` | C4, уровень 3 (компоненты), PlantUML + C4-PlantUML | Компоненты приложения для сценария «розыгрыш попытки»: контроллер попыток → сценарий «Сыграть попытку» → доменные службы (Механика, ПризовойФонд, КнигаПромокодов) → порты (баланс попыток, хранилище рейтинга, публикатор событий, публикатор реального времени) → хранилища Eloquent и клиенты Redis / RabbitMQ / Centrifugo. |
| `class-domain` | UML, диаграмма классов, PlantUML | Доменный слой: агрегаты (Campaign, Mechanic и наследники, Participant, Attempt, PrizePool, Prize, PromoCodeBook, PromoCode, Leaderboard), объекты-значения, перечисления, доменные события (ПопыткаСыграна, ПризВыдан, ПромокодВыдан, КампанияОпубликована), интерфейсы хранилищ. Связи, кратности, ключевые методы `PrizePool::reserveOne()`, `PromoCodeBook::issueNext()`. |
| `sequence-play-attempt` | UML, диаграмма последовательности, PlantUML | «Розыгрыш попытки в механике»: проверка баланса попыток в Redis → `Mechanic.evaluate()` → атомарный `PrizePool.reserveOne()` (`UPDATE … WHERE remaining > 0`) → атомарный `PromoCodeBook.issueNext()` (`SELECT … FOR UPDATE`) → сохранение попытки в транзакции PostgreSQL → публикация доменных событий в RabbitMQ → рейтинг в Redis (`ZINCRBY`) → уведомление через Centrifugo → ответ. Ветки: попыток нет → 409; фонд пуст → выигрыш без приза / утешительный. |
| `state-campaign` | UML, диаграмма состояний, PlantUML | Жизненный цикл агрегата «Кампания»: Черновик → Опубликована → Активна → Приостановлена → Завершена → Архивная, с условиями переходов (`publish()`, наступление `startsAt` / `endsAt`, `pause()` / `resume()`, `finish()`, `archive()`). |
| `bpmn-prize-fulfillment` | BPMN, Mermaid `flowchart` | Процесс «Модерация и выдача приза». Пулы: Участник, Платформа «Затея», Организатор. Старт по событию «ПризВыдан» → постановка в очередь → шлюз «Требуется модерация?» → задача организатора «Проверка» → шлюз «Одобрено?» → отправка приза/промокода в канал → уведомление участника → конец; ветка отказа → возврат приза в фонд → конец. Сплошные стрелки — поток управления, пунктирные — поток сообщений между пулами. |
| `bpmn-promo-code-issue` | BPMN, Mermaid `flowchart` | Процесс «Выпуск и выдача промокода». Пулы: Организатор, Платформа «Затея», Участник. Загрузка пула → валидация и дедупликация → сохранение книги → (позже) событие «нужен код» → атомарная выдача следующего кода → шлюз «Пул не исчерпан?» → нет: сообщение организатору «Пополнить пул» → конец; да: привязка кода к участнику → выдача в канал → конец. |

## Пере-рендер

```bash
bash docs/diagrams/render.sh
```

Скрипт переходит в `docs/diagrams/`, при отсутствии `.tools/plantuml.jar` скачивает
релиз, затем прогоняет все `src/*.puml` через PlantUML (движок раскладки — Graphviz
`dot`) и все `src/*.mmd` через `@mermaid-js/mermaid-cli` (`npx`). Результат
перезаписывается в `out/`.

Все связи на диаграммах — строго ортогональные (`skinparam linetype ortho`): линии
идут только по горизонтали и вертикали, повороты — под прямым углом. Ортогональную
раскладку умеет только Graphviz; встроенный движок Smetana её игнорирует, поэтому
`dot` обязателен.

Требования: Java 21+, Graphviz (`dot`) и Node.js в `PATH`. Если `dot` не в `PATH`,
`render.sh` подхватывает портативную установку из `$HOME/graphviz/bin`.

### Рендер по одному файлу

```bash
# PlantUML
java -jar docs/diagrams/.tools/plantuml.jar -tsvg -o ../out docs/diagrams/src/c4-context.puml

# Mermaid
npx @mermaid-js/mermaid-cli -i docs/diagrams/src/bpmn-prize-fulfillment.mmd \
  -o docs/diagrams/out/bpmn-prize-fulfillment.svg \
  -p docs/diagrams/.tools/puppeteer.json -b white
```
