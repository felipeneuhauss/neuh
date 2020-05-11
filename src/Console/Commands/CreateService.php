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
class CreateService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:service {tableName?}';

    protected $modelList = array();

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service base into a table name of MySQL database';

    private $abstractModelName = 'AbstractService';

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

        if (file_exists(__DIR__ . '/../../Services/'.$modelName. 'Service.php')) {
            return;
        }
        $modelName = null;

        try {

            if ($tableName) {
                $this->generateService($tableName);
                $this->info("Service $tableName created");
            }

            $this->saveServicesToFile();

            $this->comment("All complete");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $this->info('Models customized. End.');

    }

    /**
     * Create the service
     * @param $tableName
     */
    public function generateService($tableName) {

        // Get model file
        $modelName  = $this->prepareModelName($tableName);

        $repositoryName = $modelName . 'Repository';

        $serviceNameClass = $modelName . 'Service';

        $classNameScope = "class $serviceNameClass extends $this->abstractModelName";

        $fileContent = <<<EOL
<?php

namespace App\Services;

use App\Repositories\\$repositoryName;
use Neuh\Abstracts\AbstractService;

/**
 * Class $serviceNameClass
 * @package App\Services
 */
$classNameScope
{
    /**
     * $serviceNameClass constructor.
     * @param $repositoryName \$repository
     */     
    public function __construct($repositoryName \$repository)
    {
        \$this->repository = \$repository;
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
    private function saveServicesToFile()
    {
        foreach($this->modelList as $modelName => &$content) {
            $fileName = __DIR__ . '/../../../../../../app/Services/'.$modelName. 'Service.php';
            file_put_contents($fileName, $content);
            $this->info('Created class '. $modelName . 'Service.php');
        }
    }

}
