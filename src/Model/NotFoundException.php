<?php
//declare(strict_types=1);

namespace App\Model;

//final - NotFoundException делать абстрактным и делать для кадой модели отдельно что бы была семантика
class NotFoundException extends \Exception
{
}
