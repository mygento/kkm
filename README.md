[![Build Status](https://travis-ci.com/mygento/kkm.svg?branch=v2.3)](https://travis-ci.com/mygento/kkm)
[![Latest Stable Version](https://poser.pugx.org/mygento/module-kkm/v/stable)](https://packagist.org/packages/mygento/module-kkm)
[![Total Downloads](https://poser.pugx.org/mygento/module-kkm/downloads)](https://packagist.org/packages/mygento/module-kkm)

# Модуль интеграции АТОЛ онлайн для Magento 1/2

Модуль разрабатывается для полной поддержки требований 54 ФЗ интернет-магазинами на Magento 1 и 2 для сервиса АТОЛ онлайн.
Модуль поддерживает версию сервиса АТОЛ v4 (ФФД 1.05).

## Функционал модуля

### Передача данных в АТОЛ
* отправляет данные о счете/возврате в АТОЛ 
  * при создании счета (настраивается в конфигурации)
  * при создании возврата (настраивается в конфигурации)
  * вручную одной из консольных команд (см. ниже)
  * вручную из админки кнопкой на странице Счета или Возврата

### Получение данных из АТОЛ
* получает из АТОЛ данные о статусе регистрации счета/возврата
  * автоматически (настраивается в конфигурации). После обработки данных АТОЛ отправляет реультат обратно (колбек). По умолчанию URL: http://shop.ru/kkm/frontend/callback
  * крон задачей для проверки статусов
  * вручную из админки кнопкой на странице Счета или Возврата

### Процесс отправки данных в АТОЛ
1. На основании сущности Invoice или Creditmemo формируется объект `Mygento\Kkm\Api\Data\RequestInterface`.
    1.1. При асинхронной передаче - объект помещается в очередь (см. Magento Queue Framework)
    1.2. При синхронной передаче - передается классу `Vendor` для отправки

2. Регистрируется попытка отправки данных. Создается сущность `Api\Data\TransactionInterface\TransactionAttemptInterface` со статусом `NEW` (1)

3. Осуществляется передача данных в виде JSON в АТОЛ.
    3.1. В случае **УСПЕШНОЙ** передачи (один из HTTP статусов `[200, 400, 401]`) 
    * создается `Magento\Sales\Api\Data\TransactionInterface` в который записываются UUID и все данные о передаче. В админке это грида Sales -> Transactions.
    * Сущность попытки отправки `TransactionAttemptInterface` получает статус `Sent` (2)
    * Создается комментарий к заказу

    3.2. В случае **НЕУСПЕШНОЙ** передачи (статусы отличные от `[200, 400, 401]`, отсутствие ответа от сервера, некорректные данные в инвойсе или возврате)
    * Сущность попытки отправки `TransactionAttemptInterface` получает статус `Error` (3)
    * Создается комментарий к заказу с описанием причины ошибки
    * Заказ получает статус "KKM Failed"


### Отчеты
Модуль отправляет отчеты об отправленных данных в АТОЛ на емейл (в конфиге). Неуспешные отправки отображаются в таблице с доп.деталями. Также этот отчет можно посмотреть в консоли.

* Еженедельный (за прошлую неделю), Ежедневный (за текущий день), Ежедневный (за вчерашний день)
* Верстка письма. Файл `view/adminhtml/templates/email/kkm_report-mjml.mjml` содержит верстку письма. Редактируется с помощью сервиса https://mjml.io/


### Поддержка новых версий сервиса АТОЛ Онлайн


### Использование очередей
* отправка сообщений в АТОЛ может осущетвляться в двух режимах:
  * синхронный (сразу после сохранения сущности или ручной отправки);
  * асинхронно (через нативный механизм очередей сообщений Magento).
* режим работы настраивается в конфигурации

### Ручная отправка данных в АТОЛ
* Отправка данных на странице сущности
* Отправка данных консольной командой с указанием IncrementId сущности

### Логирование сообщений
* Модуль логирует (при включенном режиме Debug) все запросы (и ответы) АТОЛ.
* Лог запросов доступен на странице конфигурации модуля

## Список Rewrite
нет

## Список событий и плагинов, Описание действий и причины

### События 
* sales_order_invoice_save_commit_after:
  * отправляет данные по инвойсу после его сохранения.
* sales_order_creditmemo_save_commit_after:
  * отправляет данные по возврату после сохранения.

### Плагины
* before `ExtraSalesViewToolbarButtons` на метод `Magento\Backend\Block\Widget\Button\Toolbar::pushButtons` добавляет кнопки Отправки в АТОЛ и кнопку проверки статуса на страницу сущности в админке

## Список доступных реализованных API
нет

## Список встроенных тестов, что и как они тестируют
нет

## Cron-процессы
* Отчет
* Обновление статуса

## Консольные команды
* `mygento:atol:report` - Отображает отчет. Аргументы: today, yesterday, week
* `mygento:atol:refund` - Отправляет возврат. Аргументы: IncrementId сущности
* `mygento:atol:sell` - Отправляет счет. Аргументы: IncrementId сущности
* `mygento:atol:update` - Запрашивает данные о статусе. Аргументы: UUID или "all"
