<?php

namespace App\AuditLog;

use Illuminate\Database\Eloquent\Model;

class AccessLogEntity extends Model
{
    protected $table = 'pmis.ACCESS_LOG';
    protected $primaryKey = 'ACCESS_LOG_ID';
    public $timestamps=false;

    protected $fillable = ['ACTION_NAME', 'MODEL_NAME', 'PROCE_NAME', 'PARAMS_DATA', 'RESPONSE_DATA', 'CREATED_BY', 'UPDATED_BY'];
}
