<?php
namespace App\AuditLog;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
class LogHandler extends AbstractProcessingHandler{

    public function __construct($level = Logger::DEBUG, $bubble = true) {
        parent::__construct($level, $bubble);
    }
    protected function write(array $record):void
    {
        try{
        $rowId = $record['message'];
        $entityObject = AccessLogEntity::where('ROW_ID', $rowId)->first();
        if($entityObject){
            AccessLogEntity:: where('ROW_ID', $rowId)
                ->update(
                [
                    'ACTION_NAME'=>isset($record['context']['ACTION_NAME'])?$record['context']['ACTION_NAME']:'',
                    'MODEL_NAME'=>isset($record['context']['MODEL_NAME'])?$record['context']['MODEL_NAME']:'',
                    'PROCE_NAME'=>isset($record['context']['PROCE_NAME'])?$record['context']['PROCE_NAME']:'',
                    'RESPONSE_DATA'=>isset($record['context']['RESPONSE_DATA'])?$record['context']['RESPONSE_DATA']:'',
                    'UPDATED_AT'=> isset($record['context']['UPDATED_AT'])?$record['context']['UPDATED_AT']:'',
                    'UPDATED_BY'=> auth()->id(),
                ]
            );
        }else{
            AccessLogEntity::insert(
                [
                    'ROW_ID'=>$rowId,
                    'ACTION_NAME'=>isset($record['context']['ACTION_NAME'])?$record['context']['ACTION_NAME']:'',
                    'MODEL_NAME'=>isset($record['context']['MODEL_NAME'])?$record['context']['MODEL_NAME']:'',
                    'PROCE_NAME'=>isset($record['context']['PROCE_NAME'])?$record['context']['PROCE_NAME']:'',
                    'PARAMS_DATA'=>isset($record['context']['PARAMS_DATA'])?$record['context']['PARAMS_DATA']:'',
                    'CREATED_AT'=> isset($record['context']['CREATED_AT'])?$record['context']['CREATED_AT']:'',
                    'CREATED_BY'=> auth()->id(),
                ]
            );
        }


        }catch (\Exception $exception){
            /*create table PMIS.ACCESS_LOG
            (
                ACTION_NAME   nvarchar(max),
                MODEL_NAME    nvarchar(max),
                PROCE_NAME    nvarchar(max),
                PARAMS_DATA   nvarchar(max),
                RESPONSE_DATA nvarchar(max),
                CREATED_AT    datetime,
                UPDATED_AT    datetime,
                CREATED_BY    bigint,
                UPDATED_BY    bigint,
                ROW_ID        varchar(max)
            ) */



            Log::info('There is no table found in database as the name ACCESS_LOG');
        }
    }
}