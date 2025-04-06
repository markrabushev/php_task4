# Задание 4

Структура БД следующая:

![image](https://github.com/user-attachments/assets/23be318e-1fe4-4827-b7d5-bbfff816fafd)

Пояснение: таблица feedback содержит ФИО, Email, Номер телефона и Описание. Таблица feedback_files содержит путь к файлу и его оригинальное название. Сами файлы храняться в папке uploads/.

Результат формы в лог файле:

![image](https://github.com/user-attachments/assets/373550cb-05b4-462a-beef-3eca30efe899)

Пример данных в таблице feedback:

![image](https://github.com/user-attachments/assets/b05bd8cc-ffd6-447b-b1a2-9493c78c6595)

Пример данных в таблице feedback_files:

![image](https://github.com/user-attachments/assets/1d2c0eb3-5e18-4183-a930-25b6524ba112)

В моём случае для запуска использовался сервер Apache из XAMPP. Для запуска необходимо изменить адрес отправки post запроса в файле index.html на 147 строке и далее отправлять данные через форму.
