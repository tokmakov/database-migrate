-- Новая колонка `active` таблицы `users`
ALTER TABLE
    `users`
ADD
    `active` TINYINT UNSIGNED NOT NULL DEFAULT '0'
AFTER
    `password`;