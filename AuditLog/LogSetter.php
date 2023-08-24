<?php
namespace App\AuditLog;
use Monolog\Logger;
class LogSetter{

    public function __invoke(array $config){
        $logger = new Logger("LogHandler");
        return $logger->pushHandler(new LogHandler());
    }
}