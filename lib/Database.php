<?php
/**
 * Класс Database, предоставляет доступ к базе данных,
 * реализует шаблон проектирования «Одиночка»
 */
class Database {

    /**
     * для хранения единственного экземпляра данного класса
     */
    private static $instance;

    /**
     * для хранения экземпляра класса PDO
     */
    private $pdo;


    /**
     * Метод инициализации, необходимо запускать перед началом работы
     */
    public static function init($host, $database, $user, $password){
        self::$instance = new self($host, $database, $user, $password);
    }

    /**
     * Функция возвращает ссылку на экземпляр данного класса,
     * реализация шаблона проектирования «Одиночка»
     */
    public static function getInstance() {
        return self::$instance;
    }

    /**
     * Закрытый конструктор, необходим для реализации шаблона
     * проектирования «Одиночка»
     */
    private function __construct($host, $database, $user, $password) {
        // создаем новый экземпляр класса PDO
        $this->pdo = new PDO(
            'mysql:host=' . $host . ';dbname=' . $database,
            $user,
            $password,
            array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            )
        );
    }

    /**
     *  Метод-обертка для PDOStatement::execute()
     */
    public function execute($query, $params = array()) {
        // подготавливаем запрос к выполнению
        $statementHandler = $this->pdo->prepare($query);
        // выполняем запрос
        return $statementHandler->execute($params);
    }

    /**
     * Метод-обертка для PDOStatement::fetchAll()
     */
    public function fetchAll($query, $params = array()) {
        // подготавливаем запрос к выполнению
        $statementHandler = $this->pdo->prepare($query);
        // выполняем запрос
        $statementHandler->execute($params);
        // получаем результат
        $result = $statementHandler->fetchAll(PDO::FETCH_ASSOC);
        // возвращаем результаты запроса
        return $result;
    }

    /**
     * Метод-обертка для PDOStatement::fetch()
     */
    public function fetch($query, $params = array()) {
        // подготавливаем запрос к выполнению
        $statementHandler = $this->pdo->prepare($query);
        // выполняем запрос
        $statementHandler->execute($params);
        // получаем результат
        $result = $statementHandler->fetch(PDO::FETCH_ASSOC);
        // возвращаем результат запроса
        return $result;
    }

    public function fetchOne($query, $params = array()) {
        // подготавливаем запрос к выполнению
        $statementHandler = $this->pdo->prepare($query);
        // выполняем запрос
        $statementHandler->execute($params);
        // получаем результат
        $result = $statementHandler->fetch(PDO::FETCH_NUM);
        // возвращаем результат запроса
        if (false === $result) {
            return false;
        }
        return $result[0];
    }

    public function lastInsertId() {
        return (int)$this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }
}
