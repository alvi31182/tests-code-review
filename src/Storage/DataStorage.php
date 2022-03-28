<?php

namespace App\Storage;

use App\Model;

/*
 * "Божественный класс". Данный класс нарушает один из принципов S.O.L.I.D - SRP
 *  Что нужно сделать? Вынести подключение к базе данных в отдельный класс. Класс не должен подключаться
 *  к базе данных и выполнять запросы на чтение и запись, этот подход в дальнейшем усложнит поддерживаение
 *  и внедрение новой функциональности а так же усложняет тестирование.
 *  Сделать для моедли Task() отдельный TaskRepository либо TaskStorage аналогично и для модели Project
 *  имплиментирующий два разделенных интерфейса на чтение и запись (Interface segregation principle)
 *  Именнование классов играет немаловажную роль и для новых разработчиков в комманде и для тех кто уже давно в проекте.
 */
class DataStorage
{
    /**
     * @var \PDO 
     */
    public $pdo;

    // В конструтор нужно DI serivice  for DBConnection
    // Если уже есть компоненты symfony то не помешает подключить service
    //
    public function __construct()
    {
        //Todo так как у нас уже есть отдельный класс DBConnection нужно не забывать про парль для пользователя))
        //обернуть в блок try/catch если соединение оборвалось, так мы можем поймать исключение PDOException
        // где увидим причину обрыва соединения при подключении к БД.
        // Что бы явно не передавать cridentionals надо завести в ENV для Gitlab or Githab что бы не святить в проекте
        $this->pdo = new \PDO('mysql:dbname=task_tracker;host=127.0.0.1', 'user');
    }

    /**
     * type hint
     * @param int $projectId
     * @throws Model\NotFoundException
     */
    // Не нужно тянуть всю модель со всеми полями тут нужен просто ID Project. Выбрасвать целую модель
    // наружу очень плохая практика. Ошибки несогласованных данных, объек не дает гарантий детерменированности
    // объект уже мутабельный и с ним можно делать все что угодно что в дальнейшем при разрастании проекта это может
    // привести к side effects
    // Приведение типов для значений аргументов в данно случае int
    public function getProjectById($projectId) // type hint ?int
    {
        // Возможны sql иньекции
        // не включайть данные которые мы полчем от пользователя напрямую в запрос как в этом примере
        // Лучше придерживаться подготовленных запросов с именованными параметрами метода prepare()
        // посредством метода execute() объекта PDOStatement()
        // $sql = SELECT * FROM project WHERE id = ?
        // $stmt = $this->pdo->prepare($sql);
        // $stmt->execute(['id' => $projectId])

        // со стороны производительности доствать все строки не имеет смысла если нам нужно только id
        $stmt = $this->pdo->query('SELECT * FROM project WHERE id = ' . (int) $projectId);


        // Присвоенине к переменной $ROW в операторе не есть хорошо потому что надо проверить $ROW на правильный тип/значения
        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Model\Project($row);
        }

        // Лучше для каждой модели иметь свой NotFoundException
        throw new Model\NotFoundException();
    }

    /**
     * //return  Model\Task[]
     * @param int $project_id
     * @param int $limit
     * @param int $offset
     */
    public function getTasksByProjectId(int $project_id, $limit, $offset)//type-hint
    {
        // ну тоже что и описано выше
        $stmt = $this->pdo->query("SELECT * FROM task WHERE project_id = $project_id LIMIT ?, ?");


        $stmt->execute([$limit, $offset]);

        $tasks = [];
        foreach ($stmt->fetchAll() as $row) {

            //конкретика для семантики класса модели неизвестно что и какие данные/поля
            // new Model($row['id'], $row['name'])
            $tasks[] = new Model\Task($row);
        }

        //Массив чего. type-hint Task[]
        return $tasks;
    }

    /**
     * @param array $data // $_REQUEST? убираем - ответсвенность контроллера а не инфраструктуры
     * @param int $projectId
     * @return Model\Task
     */

    //$projectId int
    //
    // Вместо $data которая тянется из данных $_REQUEST со всеми вытекающими последствиями лучше -
    // передавать ДТО с конкретными данными и из этого нам не нужна $projectId если нужно проверить на существование по $projectId -
    // это не зона ответсвенности инфраструктуры, а контроллера, либо сервсиса приложения в котром может быть проверка -
    // на то, что есть ли уже данная Task $projectId или нет

    // createTask(ProjectData $projectId) этого достаточно
    public function createTask(array $data, $projectId)//void
    {
        // ну лишнее явно и не нужна эта магия - игра в наперстки))
        $data['project_id'] = $projectId;

        // да сложный каламбур но все это не нужно если у нас есть уже ДТО
        // с конкретными данными и скалярными типами в свойствах
        $fields = implode(',', array_keys($data));
        $values = implode(',', array_map(function ($v) {
            return is_string($v) ? '"' . $v . '"' : $v;
        }, $data));

        //$fields береться из
        <<<SQL
            INSERT INTO task (конекретно указать какаие поля мы создаем) VALUES (?,?,?)
SQL;

        $this->pdo->query("INSERT INTO task ($fields) VALUES ($values)");

        // Незачем запрашивать выборку ID из БД снижая тем самым производительность при больших нагрузках
        //можно просто обойтись $taskID = (int)$stmt->lastInsertId();
        // не зачем перезаписывать $data[]
        //ну и как говорил то что мы передаем методу
        $data['id'] = $this->pdo->query('SELECT MAX(id) FROM task')->fetchColumn();

        // при создании лучше не выбрасывать всю можель наружу лучше вернуть return $taskID
        return new Model\Task($data);
    }

    // На этой ноте я навреное закончу в остальных примерах уже внес ясность как в целом организовать подход
    // прошу прощения но работы много 
}
