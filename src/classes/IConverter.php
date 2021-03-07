<?php
declare(strict_types=1);

namespace protomuncher\classes;


interface IConverter
{
    function setmodality(string $modality): void;

    function setinput(string $input): void;

    function convert(): array;
}
