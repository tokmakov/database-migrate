<?php
/**
 * Класс для изменения состояния базы данных и учета этих изменений
 */
class Migration {

    /**
     * Хост базы данных
     */
    private $host;
    /**
     * Имя базы данных
     */
    private $name;
    /**
     * Пользователь базы данных
     */
    private $user;
    /**
     * Пароль базы данных
     */
    private $pass;

    /**
     * Имя таблицы БД для учета миграций
     */
    private $stateTable;
    /**
     * Директория с SQL-файлами
     */
    private $sqlDir;
    /**
     * Директория с резервными копиями
     */
    private $backupDir;

    /**
     * Для хранения экземпляра класса для работы с базой данных
     */
    private $database;


    public function __construct($host, $name, $user, $pass, $stateTable, $sqlDir = 'sql', $backupDir = 'backup') {
        $this->host = $host;
        $this->name = $name;
        $this->user = $user;
        $this->pass = $pass;

        $this->stateTable = $stateTable;
        $this->sqlDir = str_replace('\\', '/', realpath($sqlDir)) . '/';
        $this->backupDir = str_replace('\\', '/', realpath($backupDir)) . '/';
 
        Database::init($host, $name, $user, $pass);
        $this->database = Database::getInstance();
    }

    /**
     * Функция несколько раз изменяет состояние базы данных, выполняет запросы
     * из тех SQL-файлов, которые еще не выполнялись ранее
     */
    public function migrate() {

        // получаем список файлов для миграции
        $files = $this->getNewFiles();

        // нечего делать, база данных в актуальном состоянии
        if (empty($files)) {
            echo 'Your database in latest state';
            return;
        }

        // создаем резервную копию текущего состояния
        if (!$this->isEmpty()) {
            $this->backup();
            echo PHP_EOL;
        }

        echo 'Start database migration', PHP_EOL;
        // выполняем SQL-запросы из каждого файла
        foreach ($files as $file) {
            $this->execute($file);
            echo 'Execute file ', basename($file), PHP_EOL;
        }

        echo 'Database migration complete';
    }

    /**
     * Функция показывает список SQL-файлов для миграций
     */
    public function state() {
        // выводим список старых файлов
        $oldFiles = $this->getOldFiles();
        echo 'Old files in folder ' . $this->sqlDir . ':';
        if (!empty($oldFiles)) {
            $i = 1;
            foreach ($oldFiles as $file) {
                echo PHP_EOL, '    ', $i, '. ', basename($file);
                $i++;
            }
        } else {
            echo PHP_EOL, '    Old files not found';
        }
        // выводим список новых файлов
        $newFiles = $this->getNewFiles();
        echo PHP_EOL, 'New files in folder ' . $this->sqlDir . ':';
        if (!empty($newFiles)) {
            $i = 1;
            foreach ($newFiles as $file) {
                echo PHP_EOL, '    ', $i, '. ', basename($file);
                $i++;
            }
        } else {
            echo PHP_EOL, '    New files not found';
        }
    }
    
    /**
     * Функция создает резервную копию базы данных
     */
    public function backup() {
        // резервную копию создаем, если в БД есть таблицы
        if ($this->isEmpty()) {
            echo 'No tables found in database, nothing to do';
            return;
        }
        // предупреждаем, если резервных копий накопилось много
        $items = scandir($this->backupDir);
        if (count($items) > 12) {
            echo 'Warning! Too many backup files', PHP_EOL;
        }
        // выполняем команду mysqldump
        echo 'Create backup of current state';
        $backupName = $this->backupDir . $this->name . '-' . date('d.m.Y-H.i.s') . '.sql';
        if ($this->pass != '') {
            $command = 'mysqldump -u' . $this->user . ' -p' . $this->pass . ' -h ' . $this->host .
                       ' -B ' . $this->name . ' > ' . $backupName;
        } else {
            $command = 'mysqldump -u' . $this->user . ' -h ' . $this->host .
                       ' -B ' . $this->name . ' > '.$backupName;
        }
        shell_exec($command);
    }

    /**
     * Функция восстанавливает базу данных из резервной копии
     */
    public function restore() {
        // получаем имя файла резервной копии
        $backupName = $this->choose();
        if (false === $backupName) {
            return;
        }
        // создаем резервную копию текущего состояния
        if (!$this->isEmpty()) {
            $this->backup();
            echo PHP_EOL;
        }
        // удаляем все таблицы из базы данных
        $query = 'SHOW TABLES';
        $rows = $this->database->fetchAll($query);
        foreach ($rows as $row) {
            $query = 'DROP TABLE `' . $row['Tables_in_'.$this->name] . '`';
            $this->database->execute($query);
            
        }
        // восстанавливаем базу данных
        echo 'Restore database from backup';
        if ($this->pass != '') {
            $command = 'mysql -u' . $this->user . ' -p' . $this->pass . ' -h '.$this->host .
                       ' -D ' . $this->name . ' < ' . $backupName;
        } else {
            $command = 'mysql -u' . $this->user . ' -h ' . $this->host .
                       ' -D ' . $this->name . ' < ' . $backupName;
        }
        shell_exec($command);
    }

    /**
     * Функция возвращает массив старых файлов миграций, т.е.
     * тех, которые уже были применены к БД
     */
    private function getOldFiles() {
        $oldFiles = array();
        if ($this->isEmpty()) {
            return $oldFiles;
        }
        $query = 'SELECT `name` FROM `'.$this->stateTable.'` WHERE 1';
        $rows = $this->database->fetchAll($query);
        foreach ($rows as $row) {
            $oldFiles[] = $this->sqlDir . $row['name'];
        }
        return $oldFiles;
    }

    /**
     * Функция возвращает массив новых файлов миграций, т.е.
     * тех, которые еще не были применены к БД
     */
    private function getNewFiles() {
        // получаем список всех sql-файлов
        $items = scandir($this->sqlDir);
        $allFiles = array();
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $allFiles[] = $this->sqlDir . $item;
        }
        // получаем список старых файлов
        $oldFiles = $this->getOldFiles();

        return array_diff($allFiles, $oldFiles);
    }

    /**
     * Функция выполняет запросы из sql-файла
     */
    private function execute($file) {
        if ($this->pass != '') {
            $command = 'mysql -u' . $this->user . ' -p' . $this->pass . ' -h ' . $this->host .
                       ' -D ' . $this->name . ' < ' . $file;
        } else {
            $command = 'mysql -u' . $this->user . ' -h ' . $this->host .
                       ' -D ' . $this->name . ' < ' . $file;
        }
        shell_exec($command);

        // добавляем запись в таблицу учета миграций, отмечая тот факт,
        // что состояние базы данных изменилось
        $query = 'INSERT INTO `' . $this->stateTable . '` (`name`) VALUES ("' . basename($file) . '")';
        $this->database->execute($query);
    }

    /**
     * Функция проверяет, есть ли в базе данных таблицы
     */
    private function isEmpty() {
        $query = 'SHOW TABLES';
        $rows = $this->database->fetchAll($query);
        return empty($rows);
    }

    /**
     * Вспомогательная функция для выбора файла резервной копии
     */
    private function choose() {
        $items = scandir($this->backupDir);
        if (count($items) == 2) {
            echo 'Backup files not found', PHP_EOL;
            return false;
        }
        // выводим список всех файлов резервных копий с номерами 1,2,3,...
        echo 'Choose backup file to restore:', PHP_EOL;
        $i = 0;
        $numbers = array(); // массив всех номеров файлов, для дальнейшей проверки
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $i++;
            $numbers[] = $i;
            echo $i, '. ', $item, PHP_EOL;
        }
        while (true) { // пока не будет выбран правильный номер файла
            echo 'Enter number of backup file: ';
            $number = fgets(STDIN);
            if (in_array($number, $numbers)) { // проверяем корректность номера файла
                break;
            }
        }
        // получаем имя файла резервной копии по ее номеру в списке
        $i = 0;
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $i++;
            if ($i == $number) {
                // возвраем полное имя файла резервной копии
                return $this->backupDir . $item;
            }
        }
    }

}