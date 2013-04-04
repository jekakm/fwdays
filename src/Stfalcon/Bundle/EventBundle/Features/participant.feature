# language: ru

Функционал: Тест контроллера ParticipantController

    Сценарий: Открыть страницу участников, убедиться в ее существовании и проверить кто выводится в списке участников
        Допустим я на странице "/event/zend-framework-day-2011"
        И кликаю по ссылке "Участники"
        Тогда код ответа сервера должен быть 200
        И я должен быть на странице "/event/zend-framework-day-2011/participants"
        И я должен видеть "Участники" внутри элемента "#content h2"
        И я должен видеть "Michael Jordan" внутри элемента ".part-name"
        И я должен видеть "Point Guard" внутри элемента ".part-occupation"
        И я должен видеть "Boston, USA" внутри элемента ".part-location"

    Сценарий: Открыть страницу события, убедиться в существовании виджета участников и проверить кто выводится в списке участников
        Допустим я на странице "/event/zend-framework-day-2011"
        И я должен видеть "Участники" внутри элемента "#side-left h2:nth-of-type(2)"
        И я должен видеть "Michael Jordan" внутри элемента ".side-participants"
