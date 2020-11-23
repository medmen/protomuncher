<?php
declare(strict_types=1);

namespace protomuncher;


interface IParseProtocol
{
    function getfiletype($file) :string;

    function extract_data($html) :array;
}