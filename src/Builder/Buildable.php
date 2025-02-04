<?php

namespace Da\Mailer\Builder;

use Da\Mailer\Helper\ConfigReader;
use Exception;

abstract class Buildable
{
    /**
     * @return array
     * @throws Exception
     */
    protected static function getConfig()
    {
        return ConfigReader::get();
    }

    /**
     * @param $config
     * @return void
     */
    public abstract static function make($config = null);
}
