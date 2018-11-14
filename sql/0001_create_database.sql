-- Структура таблицы `current_state_database`
CREATE TABLE IF NOT EXISTS `current_state_database` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- Структура таблицы `products`
CREATE TABLE IF NOT EXISTS `products` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `price` decimal(10,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3;

-- Дамп данных таблицы `products`
INSERT INTO `products` (`id`, `name`, `price`) VALUES
(1, 'Первый товар', '1000.00'),
(2, 'Второй товар', '2000.00');