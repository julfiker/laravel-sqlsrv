<?php
namespace Julfiker\SqlSrv\Connection;

use Illuminate\Database\Query\Processors\SqlServerProcessor;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use PDO;
use PDOStatement;
use Illuminate\Support\Str;
use Illuminate\Database\Grammar;
use Illuminate\Database\Connection;
use Doctrine\DBAL\Connection as DoctrineConnection;

use Doctrine\DBAL\Driver\OCI8\Driver as DoctrineDriver;


class SqlServerConnection extends Connection
{
    const RECONNECT_ERRORS = 'reconnect_errors';

    /**
     * @var string
     */
    protected $schema;


    /**
     * @param PDO|\Closure $pdo
     * @param string $database
     * @param string $tablePrefix
     * @param array $config
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
    }


    /**
     * Get doctrine connection.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDoctrineConnection()
    {
        if (is_null($this->doctrineConnection)) {
            $data = ['pdo' => $this->getPdo(), 'user' => $this->getConfig('username')];
            $this->doctrineConnection = new DoctrineConnection(
                $data,
                $this->getDoctrineDriver()
            );
        }

        return $this->doctrineConnection;
    }


    /**
     * @return DoctrineDriver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver();
    }

    /**
     * Execute a  Function and return its value.
     * Usage: DB::executeFunction('function_name(:binding_1,:binding_n)', [':binding_1' => 'hi', ':binding_n' =>
     * 'bye'], PDO::PARAM_LOB).
     *
     * @param string $functionName
     * @param array $bindings (kvp array)
     * @param int $returnType (PDO::PARAM_*)
     * @param int $length
     * @return mixed $returnType
     */
    public function executeFunction($functionName, array $bindings = [], $returnType = PDO::PARAM_STR, $length = null)
    {
        $stmt = $this->createStatementFromFunction($functionName, $bindings);

        $stmt = $this->addBindingsToStatement($stmt, $bindings);

        $stmt->bindParam(':result', $result, $returnType, $length);
        $stmt->execute();

        return $result;
    }

    /**
     * Execute a Procedure and return its results.
     *
     * Usage: DB::executeProcedure($procedureName, $bindings).
     * $bindings looks like:
     *         $bindings = [
     *                  'p_userid'  => $id
     *         ];
     *
     * @param string $procedureName
     * @param array $bindings
     * @return bool
     */
    public function executeProcedure($procedureName, array $bindings = [])
    {
        //log set
        $logId = date('YmdHi-').Uuid::uuid4();
        Log::channel('auditLog')->debug($logId, [
            'MODEL_NAME'=>'',
            'PROCE_NAME'=>$procedureName,
            'PARAMS_DATA'=>json_encode($bindings),
            'CREATED_AT'=> date("Y-m-d H:i:s")
        ]);

        $stmt = $this->createStatementFromProcedure($procedureName, $bindings);
        $stmt = $this->addBindingsToStatement($stmt, $bindings);
        $stmt->execute();

        //log set
        Log::channel('auditLog')->debug($logId, [
            'PROCE_NAME'=>$procedureName,
            'RESPONSE_DATA'=>json_encode($bindings),
            'UPDATED_AT'=> date("Y-m-d H:i:s")
        ]);

        return $stmt;
    }

    /**
     * Execute a Procedure and return its cursor result.
     * Usage: DB::executeProcedureWithCursor($procedureName, $bindings).
     *
     *
     * @param string $procedureName
     * @param array $bindings
     * @param string $cursorName
     * @return array
     */
    public function executeProcedureWithCursor($procedureName, array $bindings = [], $cursorName = ':cursor')
    {
        $stmt = $this->createStatementFromProcedure($procedureName, $bindings, $cursorName);

        $stmt = $this->addBindingsToStatement($stmt, $bindings);

        $cursor = null;
        $stmt->bindParam($cursorName, $cursor, PDO::PARAM_STMT);
        $stmt->execute();

        $statement = new Statement($cursor, $this->getPdo(), $this->getPdo()->getOptions());
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        $statement->closeCursor();

        return $results;
    }

    /**
     * Creates sql command to run a procedure with bindings.
     *
     * @param string $procedureName
     * @param array $bindings
     * @param string|bool $cursor
     * @return string
     */
    public function createSqlFromProcedure($procedureName, array $bindings, $cursor = false)
    {
        $paramsString = implode(',', array_map(function ($param) {
            return ':' . $param;
        }, array_keys($bindings)));

        $prefix = count($bindings) ? ',' : '';
        $cursor = $cursor ? $prefix . $cursor : null;

        return sprintf('exec %s %s%s;', $procedureName, $paramsString, $cursor);
    }

    /**
     * Creates statement from procedure.
     *
     * @param string $procedureName
     * @param array $bindings
     * @param string|bool $cursorName
     * @return PDOStatement
     */
    public function createStatementFromProcedure($procedureName, array $bindings, $cursorName = false)
    {
        $sql = $this->createSqlFromProcedure($procedureName, $bindings, $cursorName);
        return $this->getPdo()->prepare($sql);
    }

    /**
     * Create statement from function.
     *
     * @param string $functionName
     * @param array $bindings
     * @return PDOStatement
     */
    public function createStatementFromFunction($functionName, array $bindings)
    {
        $bindings = $bindings ? ':' . implode(', :', array_keys($bindings)) : '';

        $sql = sprintf('begin :result := %s(%s); ', $functionName, $bindings);

        return $this->getPdo()->prepare($sql);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new \Illuminate\Database\Query\Grammars\SqlServerGrammar());
    }

    /**
     * Set the table prefix and return the grammar.
     *
     * @param \Illuminate\Database\Grammar $grammar
     * @return \Illuminate\Database\Grammar
     */
    public function withTablePrefix(Grammar $grammar)
    {
        return $this->withSchemaPrefix(parent::withTablePrefix($grammar));
    }

    /**
     * Set the schema prefix and return the grammar.
     *
     * @param \Illuminate\Database\Grammar $grammar
     * @return \Illuminate\Database\Grammar
     */
    public function withSchemaPrefix(Grammar $grammar)
    {
        //$grammar->setSchemaPrefix($this->getConfigSchemaPrefix());

        return $grammar;
    }

    /**
     * Get config schema prefix.
     *
     * @return string
     */
    protected function getConfigSchemaPrefix()
    {
        return isset($this->config['prefix_schema']) ? $this->config['prefix_schema'] : '';
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SqlServerGrammar());
    }

    /**
     * Get the default post processor instance.
     *
     * @return SqlServerProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SqlServerProcessor();
    }

    /**
     * Add bindings to statement.
     *
     * @param array $bindings
     * @param PDOStatement $stmt
     * @return PDOStatement
     */
    public function addBindingsToStatement(PDOStatement $stmt, array $bindings)
    {
        foreach ($bindings as $key => &$binding) {
            $value = &$binding;
            $type = PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT;
            $length = 4000;
            $driver_options = null;

            if (is_array($binding)) {
                $value = &$binding['value'];
                $type = array_key_exists('type', $binding) ? $binding['type']  : PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT;
                $length = array_key_exists('length', $binding) ? $binding['length'] : 4000;
                $driver_options = array_key_exists('encode', $binding) ? $binding['encode'] : null;
            }
            if ($length && $type)
                $stmt->bindParam(':' . $key, $value,$type,$length);
            else
                $stmt->bindParam(':' . $key, $value);
           }
        return $stmt;
    }
}
