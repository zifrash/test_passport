Нужно выполнить POST запрос на /passport/check, в теле запроса передать json с параметрами series и number:
```http request
POST /passport/check

Content-Type: application/json
{
    "series": "0000",
    "number": "000000"
}
```

В ответ сервис должен возвращать JSON следующего вида для недействительного паспорта:
```json
{
  "valid": false
}
```

И такой JSON для действительного:
```json
{
  "valid": true
}
```

Для разворачивания контейнеров:
```bash
make first-build
```

Рекомендую использовать make команды, что бы увидеть список команд и их описание, выполните в консоли:
```bash
make
```

Что бы добавить\обновить список паспортов нужно выполнить команду:
```bash
make add-passport-data
```

Обработчик csv организован на команде app:add-passport-data, его можно запускать как вручную, так и повесить на крон.
В среднем парсинг файла на 150 061 436 позиций у меня занимает 30 минут.

Команда ищет в папке files файл list_of_expired_passports.csv, парсит его и заливает в базу.
По 1 000 000 позиций (среднее время заливки 6 секунд).

Не успел сделать:<br>
Нужны какие-то манипуляции с файлом (бекап, сжатие, еще что-нибудь)<br>
Написать тесты
