# language: ru

Функционал: Тест екшена генерации и проверки билетов
    Тестируем создание билета с QR-кодом

    Сценарий: Проверяем не созданный или неоплаченный билет
        Допустим я вхожу в учетную запись с именем "user@fwdays.com" и паролем "qwerty"
        Допустим я оплатил билет для "user@fwdays.com"
        И я перехожу на "/event/zend-framework-day-2011/ticket"
        Тогда код ответа сервера должен быть 200
        И это PDF-файл

    Сценарий: Проверяем не созданный или неоплаченный билет
        Допустим я вхожу в учетную запись с именем "user@fwdays.com" и паролем "qwerty"
        Допустим я не оплатил билет для "user@fwdays.com"
        И я перехожу на "/event/zend-framework-day-2011/ticket"
        Тогда код ответа сервера должен быть 402
        И я должен видеть "Вы не оплачивали участие в \"Zend Framework Day\""

    #Тестируем проверку билета с QR-кодом
    Сценарий: Проверяем регистрацию билета в системе не администратором
        Допустим я вхожу в учетную запись с именем "user@fwdays.com" и паролем "qwerty"
        И я перехожу на страницу регистрации для "user@fwdays.com"
        Тогда код ответа сервера должен быть 403

    Сценарий: Проверяем регистрацию билета в системе администратором
        Допустим я вхожу в учетную запись с именем "admin@fwdays.com" и паролем "qwerty"
        И я перехожу на страницу регистрации для "user@fwdays.com"
        Тогда код ответа сервера должен быть 200
        И я должен видеть "Все ок"
        #Пробуем зарегестрироваться еще раз
        И я перехожу на страницу регистрации для "user@fwdays.com"
        Тогда код ответа сервера должен быть 409
        И я должен видеть "был использован"
        #Пробуем зарегестрироваться с неправильным хешем
        И я перехожу на страницу регистрации для "user@fwdays.com" с битым хешем
        Тогда код ответа сервера должен быть 403
        И я должен видеть "Невалидный хеш для билета"

    Сценарий: Проверяем активный и неоплаченный билет
        Допустим я вхожу в учетную запись с именем "admin@fwdays.com" и паролем "qwerty"
        И я перехожу на "/ticket/10/check/8737d5e14e2e9c334144863c7e000686"
        И я должен видеть "Билет №10 оплата не существует"

    Сценарий: Проверяем активный и оплаченный билет
        Допустим я вхожу в учетную запись с именем "admin@fwdays.com" и паролем "qwerty"
        Допустим я оплатил билет для "jack.sparrow@fwdays.com"
        И я перехожу на "/ticket/11/check/a332b6f6a91316b44af87818420a0941"
        И я должен видеть "Все ок. Билет №11 отмечаем как использованный"

    Сценарий: Проверяем неактивный и неоплаченный билет
        Допустим я вхожу в учетную запись с именем "admin@fwdays.com" и паролем "qwerty"
        И я перехожу на "/ticket/12/check/f667fe3620b9906eebc857dd9cbcb228"
        И я должен видеть "Билет №12 не оплачен"

    Сценарий: Проверяем неактивный и оплаченный билет
        Допустим я вхожу в учетную запись с именем "admin@fwdays.com" и паролем "qwerty"
        Допустим я оплатил билет для "jack.sparrow@fwdays.com"
        И я перехожу на "/ticket/13/check/a71b0c9d339402f297a2f4e33b3cb87c"
        И я должен видеть "Билет №13 был использован"
