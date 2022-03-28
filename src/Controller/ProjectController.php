<?php

// declare(strict_types=1); Php ЯП динамической типизацией и поэтому тип переменных известен
// только во время выполнения программы, а не во время компиляции.
// Кроме того, тип переменной может быть изменен в течении жизни zval, то есть zval ранее хранимый как целое число позднее может содержать строку.
// Поэтому для улучшения производительности и быстрого выпонения программы необходимо
// в каждом блоке кода устанавливать директивы строгой типизации.
namespace Api\Controller;

use App\Model;
use App\Storage\DataStorage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Класс должен быть final
 */
class ProjectController 
{
    /**
     * @var DataStorage
     */
    private $storage;

    //Не будет работать DI в данном случае нужно создать
    public function __construct(DataStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param Request $request
     *
     * @Route("/project/{id}", name="project", method="GET")
     * Нет типа возвращаемого метода
     * В аннотации не указаны возможные исключения в данном методе
     */
    public function projectAction(Request $request)
    {
        // Что бы не делать постоянно try/catch можно резделить логические исключение и технические
        // Для этого необходимо создать  Listener который будет отлавливать тип исключения   
        try {
            // $request->get('id') явно указать метод GET -$request->query->get(key)
            // если это ID мы должны конкретно обозначить $request->query->getInt()
            $project = $this->storage->getProjectById($request->get('id'));
            //Ипользовать
            //return new JsonResponse(
            //                [
            //                    'action' => $project->toJson() // не совсем хорошо выбрасывать пачку данных длучше
            //                ]
            //            );
            return new Response($project->toJson());
        } catch (Model\NotFoundException $e) {
            // Response есть флаги HTTP что бы явно указать семантику
            return new Response('Not found', 404);
        } catch (\Throwable $e) {
            return new Response('Something went wrong', 500);
        }
    }

    /**
     * Уже есть type hint
     * @param Request $request
     *
     * @Route("/project/{id}/tasks", name="project-tasks", method="GET")
     */
    //
    public function projectTaskPagerAction(Request $request)
    {
        //$request->query->getInt() на все аргументы и хорошо бы передавать DTO а не передовать кучу аргументов в виде
        // массива и это лучше использовать если мы работаем с json OR jsonb данными в БД
        // Плюс навзвание метода getTasksByProjectIdWithLimitation
        $tasks = $this->storage->getTasksByProjectId(
            $request->get('id'),
            $request->get('limit'),
            $request->get('offset')
        );
        //return new JsonResponse(
        //                [
        //                    'task' =>  $tasks
        //                ]
        //            );
        return new Response(json_encode($tasks));
    }

    /**
     * @param Request $request
     * REST api - post / name тут необязатльно
     * @Route("/project/{id}/tasks", name="project-create-task", method="PUT")
     */
    public function projectCreateTaskAction(Request $request)
    {
		$project = $this->storage->getProjectById($request->get('id'));

        // Безсмысленная проверка getProjectById возвращает либо объект либо Exception
        // Лучше в единый слушатель Exception либо try/catch
		if (!$project) {

            // ProjectNotFoundException - и для каждой модели иметь свой Exception

			return new JsonResponse(['error' => 'Not found']);
		}

        /**
         * $_REQUEST массив с HTTP данными GET POST это лишнее и нет в нем необходимости, так же это не является безопасным
         * особенно если мы передаем это через HTTP то возможен прехват и изменение данных ну конечно же зависит от настроек
         * директивой конфигурации в php.ini.
         */
        //return new JsonResponse(
        //                [
        //                    'task_created' =>  $project->getId()
        //                ]
        //            );
		return new JsonResponse(

            //$_REQUEST тут ненужна совсем нам достаточно уже имеющийся ID - Project
			$this->storage->createTask($_REQUEST, $project->getId())
		);
    }
}
