<?php

namespace Neuh\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class responsible to generate a model or a set of models based in database tables
 * and configure your structure with interfaces and inheritance
 *
 * Class CustomizeModel
 * @package App\Console\Commands
 */
class CreateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:model {tableName?}';

    protected $modelList = array();

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a model base into a table name of MySQL database';

    protected $timestamp = true;

    private $softDelete = true;

    private $abstractModelName = 'AbstractModel';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tableName = $this->argument('tableName');
        $modelName = $this->prepareModelName($tableName);

        if (file_exists(__DIR__ . '/../../../../../../app/Models/'.$modelName. '.php')) {
            return;
        }
        $modelName = null;

        # Obtem as tabelas do banco
        if (!$tableName) {
            $tables = DB::select("SHOW TABLES");
        }

        $confirmTimeStamp = $this->confirm('Deseja ativar o timestamp nas models para update_at e created_at automaticamente?', true);

        $this->timestamp = $confirmTimeStamp;

        $confirmSoftDelete = $this->confirm('Deseja ativar o SoftDelete automaticamente?', true);

        $this->softDelete = $confirmSoftDelete;

        try {

            if ($tableName) {
                $this->generateModel($tableName);
                $this->info("Model $tableName customized");
            }

            # Caso nao tenha sido passado o nome de uma tabela especifica
            if (!$tableName) {
                $paramName = "Tables_in_".env('DB_DATABASE');

                $bar = $this->output->createProgressBar(count($tables));

                foreach ($tables as $table) {
                    if ($table->$paramName != 'DOCTYPES' && strpos($table->$paramName , 'MDRT_') !== 0) {
                        $this->info("Creating " . $table->$paramName . " model");
                        $this->generateModel($table->$paramName);
                        $bar->advance();
                    }
                }
                $bar->finish();
            }

            $this->saveModelsToFile();

            $this->comment("All complete");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $this->info('Models customized. End.');

    }

    /**
     * Funcao que cria a model referente a uma tabela e tambem cria seus relacionamentos
     * @param $tableName
     */
    public function generateModel($tableName) {

        // Get model file
        $modelName   = $this->prepareModelName($tableName);

        $columns = $this->getColumns($tableName);

        $fkColumns = $this->getFkColumns($tableName);

        // Obtem o nome das colunas das tabelas ferenciais
        $fkColumnsName = [];
        foreach ($fkColumns as $fkColumn) {
            $fkColumnsName[] = lcfirst($this->prepareModelName($fkColumn->TABLE_NAME));
        }

        $fkColumnsName = implode("','", $fkColumnsName);

        $fkColumnsName = ($fkColumnsName != "") ? "'".$fkColumnsName."'" : "";

        $dependentColumns = $this->getDependentFkColumns($tableName);

        $primaryKeyName = 'id';

        $fillableColumnsName = array();
        if (count($columns)) {
            foreach($columns as $column) {
                $fillableColumnsName[] = $column->column_name;
            }
        }

        # Gera o nome das colunas do campo fillable
        $columnsName = strtolower(implode("','", $fillableColumnsName));

        # Configura o nome da classe, extend e implementation
//        $classNameScope = "class $modelName ".($this->abstractModelName != "" ? "extends " . $this->abstractModelName : "" )."".($this->interfaceModelName != "" ? " implements " . $this->interfaceModelName : "" );
        $classNameScope = "class $modelName extends $this->abstractModelName";

        $fileContent = <<<EOL
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Neuh\Abstracts\AbstractModel;

$classNameScope
{
EOL;
        if ($this->softDelete) {
            $fileContent .= <<<EOL

    use SoftDeletes;

EOL;
        }
        $fileContent .= <<<EOL
         
    protected \$table = '$tableName';

    protected \$primaryKey = '$primaryKeyName';

    protected \$with = [];

    protected \$fillable = ['$columnsName'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
EOL;
        if ($this->softDelete) {
            $fileContent .= <<<EOL

    protected \$dates = ['deleted_at'];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected \$casts = [
        'created_at' => 'datetime:d/m/Y',
    ];

EOL;
        }

        if (!$this->timestamp) {
            $fileContent .= <<<EOL

    public \$timestamps  = false;

EOL;
        }
        $fileContent .= $this->generateBelongsToFunctions($fkColumns, $modelName);

        $fileContent .= $this->generateHasManyModelFunctions($dependentColumns, $modelName);

        $fileContent .= <<<EOL

}
EOL;
        $this->modelList[$modelName] = $fileContent;
    }

    /**
     * Prepare a model name based in table name
     *
     * @param $tableName
     * @return string
     */
    public function prepareModelName($tableName)
    {
        $modelName = trim(ucfirst(Str::singular($tableName)));

        if (strpos($tableName, '_') !== 0) {
            $modelNameExplodeList = explode('_', strtolower($tableName));

            $newModelName = '';
            foreach($modelNameExplodeList as $kName => $name) {
                $newModelName .= ucfirst(trim(Str::singular($name)));
            }

            $modelName = $newModelName;
        }

        return $modelName;
    }

    /**
     * Retorna as chves extrangeiras de uma tabela
     * @param $tableName
     * @return mixed
     */
    private function getFkColumns($tableName)
    {
        $fkColumns = DB::select("SELECT DISTINCT
              TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
            FROM
              INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
              TABLE_NAME = '$tableName' AND CONSTRAINT_NAME != 'PRIMARY' and REFERENCED_TABLE_NAME is not null");

        return $fkColumns;
    }

    /**
     * Retorna as tabelas dependentes do
     * @param $tableName
     * @return mixed
     */
    private function getDependentFkColumns($tableName)
    {

        $dbName = env('DB_DATABASE');
        $fkColumns = DB::select("SELECT *
FROM  INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
  REFERENCED_TABLE_NAME = '$tableName' 
  AND TABLE_SCHEMA = '$dbName' 
  AND CONSTRAINT_NAME != 'PRIMARY'");

        return $fkColumns;
    }

    /**
     * Retorna a chave primaria da tabela
     * @param $tableName
     * @return mixed
     */
    private function getPkColumnName($tableName)
    {
        $column = DB::select("SELECT DISTINCT column_name FROM all_cons_columns WHERE constraint_name = (
          SELECT constraint_name FROM all_constraints
          WHERE UPPER(table_name) = UPPER('$tableName') AND CONSTRAINT_TYPE = 'P'
            )");

        if (count($column)) {
            return $column[0]->column_name;
        }
    }

    /**
     * Salva todas as models carregadas
     */
    private function saveModelsToFile()
    {
        foreach($this->modelList as $modelName => &$content) {
            $fileName = __DIR__ . '/../../../../../../app/Models/'.$modelName. '.php';
            file_put_contents($fileName, $content);
            $this->info('Created class '. $modelName . '.php');
        }
    }

    private function generateBelongsToFunctions($fkColumns, $modelName)
    {
        $fileContent = '';

        if (count($fkColumns)) {

            foreach ($fkColumns as &$fkColumn) {
                # BelongTo relationship
                $relationalTableName = $fkColumn->REFERENCED_TABLE_NAME;
                $relationalModelName = $this->prepareModelName($relationalTableName);
                $relationalPkName = strtolower($fkColumn->REFERENCED_COLUMN_NAME);
                $localFkName      = strtolower($fkColumn->COLUMN_NAME);

                // Configura  o nome da funcao e nome da model
                $belongToFunctionName = lcfirst($relationalModelName);
                $modelRelationName = "App\\Models\\" . $relationalModelName;
                $this->info($modelName . ' belongsTo ' . $relationalModelName);

                $fileContent .= <<<EOL

    public function $belongToFunctionName() 
    {
        return \$this->belongsTo('$modelRelationName', '$localFkName', '$relationalPkName');
    }

EOL;
            }
        }
        return $fileContent;
    }


    /**
     * Gera as funcoes relacionadas de um para muitos 'hasMany'
     *
     * @param $dependentColumns
     * @param $tableName
     * @return string
     */
    public function generateHasManyModelFunctions($dependentColumns, $tableName)
    {
        // Get model file
        $fileContent = '';
        if (count($dependentColumns)) {
            foreach ($dependentColumns as $column) {

                $modelRelationName = $this->prepareModelName($column->TABLE_NAME);
                $modelRelationClassName = "App\\Models\\" . $modelRelationName;

                $hasManyFunctionName = Str::plural(lcfirst($modelRelationName));
                $fkName = strtolower($column->COLUMN_NAME);
                $pkRelationalName = strtolower($column->REFERENCED_COLUMN_NAME);

                /**
                 * Obtem o arquivo referente a coluna para add o hasMany() na classe pai
                 */
                if ($this->confirm('A tabela '.$column->TABLE_NAME. " é um relacionamento N x N?")) {

                    $this->comment($tableName . ' belongsToMany ' . $modelRelationName);
                    $fileContent .= <<<EOL

    public function $hasManyFunctionName()
    {
        return \$this->belongsToMany('$modelRelationClassName', '$column->TABLE_NAME', 'id');
    }

EOL;
                } else {
                    $this->comment($tableName . ' hasMany ' . $modelRelationName);
                    $fileContent .= <<<EOL

    public function $hasManyFunctionName()
    {
        return \$this->hasMany('$modelRelationClassName', '$fkName', '$pkRelationalName');
    }

EOL;
                }
            }
        }
        return $fileContent;

    }


    public function getColumns($tableName) {
        return DB::select("SELECT column_name AS column_name
                FROM information_schema.columns
                WHERE table_schema = '".env('DB_DATABASE', 'forge')."' AND table_name = '$tableName'");
    }
}
