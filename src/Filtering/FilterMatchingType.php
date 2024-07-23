<?php

declare(strict_types=1);

namespace Zoon\PyroSpy\Plugins\Filtering;

enum FilterMatchingType: int
{
    case SRC_METHOD = 1; //искать в имени метода
    case SRC_CALLEE = 2; //искать в адресе файла, откуда был вызов
    case SRC_TAG_NAME = 4; //искать в тегах
    case SRC_TAG_VALUE = 8; //искать в тегах

    case CHK_DIRECT = 16; //проверка на полное совпадение
    case CHK_REGEXP = 32; //проверка на совпадение по регулярке
    case CHK_CALLABLE = 64; //проверка в кложуре
}
