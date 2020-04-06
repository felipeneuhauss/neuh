<?php

namespace Neuh\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;


/**
 * Class responsible to generate a model or a set of models based in database tables
 * and configure your structure with interfaces and inheritance
 *
 * Class CustomizeModel
 * @package App\Console\Commands
 */
class CreateRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:repository {tableName?}';

    protected $modelList = array();

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service base into a table name of MySQL database';

    private $abstractModelName = 'AbstractRepository';

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

        if (file_exists(__DIR__ . '/../../Repositories/'.$modelName. 'Repository.php')) {
            return;
        }
        $modelName = null;

        try {

            if ($tableName) {
                $this->generateRepository($tableName);
                $this->info("Repository $tableName created");
            }

            $this->saveRepositorysToFile();

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
    public function generateRepository($tableName) {

        // Get model file
        $modelName   = $this->prepareModelName($tableName);

        $serviceNameClass = $modelName . 'Repository';

        $classNameScope = "class $serviceNameClass extends $this->abstractModelName";

        $fileContent = <<<EOL
<?php

namespace App\Repositories;

use App\Models\\$modelName;
use Neuh\Abstracts\AbstractRepository;

/**
 * Class $serviceNameClass
 * @package App\Repositories
 */
$classNameScope
{
    /**
     * $serviceNameClass constructor.
     * @param $modelName $modelName
     */     
    public function __construct($modelName \$model)
    {
        \$this->model = \$model;
    }   

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
     * Salva todas as models carregadas
     */
    private function saveRepositorysToFile()
    {
        foreach($this->modelList as $modelName => &$content) {
            $fileName = __DIR__ . '/../../../../../../app/Repositories/' . $modelName . 'Repository.php';
            file_put_contents($fileName, $content);
            $this->info('Created class in '.$fileName);
        }
    }

}
