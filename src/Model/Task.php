<?php
//declare(strict_types=1);
namespace App\Model;

//Тоже самое что и в модели Project и не нужно имплиментровать интерфейс JsonSerializable
// тут мы не работаем с полями json
class Task implements \JsonSerializable
{
    /**
     * @var array
     */
    private $_data;
    
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        // а если работаем с данными json в СУБД то нужно указать что мы возвращаем
        // return [
        //            'id' => $this->id,
        //            'name' => $this->name
        //        ];
        return $this->_data;
    }
}
