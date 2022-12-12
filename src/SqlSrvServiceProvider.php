<?php
namespace Julfiker\SqlSrv;

use App\Connection\SqlServerConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

/**
 * A service provider to integrate with laravel application
 *
 * @author Julfiker <mail.julfiker@gmail.com>
 * Class SqlSrvServiceProvider
 * @package Julfiker\SqlSrv
 */
class SqlSrvServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot Oci8 Provider.
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        Connection::resolverFor('sqlsrv', function ($connection, $database, $prefix, $config) {
            $db = new SqlServerConnection($connection, $database, $prefix, $config);

            if (! empty($config['skip_session_vars'])) {
                return $db;
            }

            return $db;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [];
    }
}
