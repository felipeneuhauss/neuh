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
class CreateController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:controller {tableName?}';

    protected $modelList = array();

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a controller base into a table name of MySQL database';

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

        if (file_exists(__DIR__ . '/../../Controllers/'.$modelName. 'Controller.php')) {
            return;
        }
        $modelName = null;

        try {

            if ($tableName) {
                $this->generateController($tableName);
                $this->info("Controller $tableName created");
            }

            $this->saveControllersToFile();

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
    public function generateController($tableName) {

        // Get model file
        $modelName   = $this->prepareModelName($tableName);

        $entityName = lcfirst($modelName);

        $controllerClassName = $modelName . 'Controller';

        $classNameScope = "class $controllerClassName extends Controller";

        $serviceModel = $modelName.'Service';

        $postModel = $modelName.'Post';

        $collectionClass = $modelName.'Collection';

        $fileContent = <<<EOL
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\\$postModel;
use App\Http\Resources\\$collectionClass;
use App\Models\\$modelName;
use App\Services\\$serviceModel;

/**
 * Class $collectionClass
 * @package App\Http\Controllers
 */
$classNameScope
{

    public function __construct($serviceModel \$service)
    {
        \$this->service = \$service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        \$data = \$this->service->index();

        return new $collectionClass(\$data);
    }

    /**
     * @param $postModel \$request
     * @return \App\Http\Resources\\$modelName
     */
    public function store($postModel \$request)
    {
        return new \App\Http\Resources\\$modelName(\$this->service->save(\$request->all()));
    }

    /**
     * Display the specified resource.
     *
     * @param  $modelName \$$entityName
     * @return \App\Http\Resources\\$modelName
     */
    public function show($modelName \$$entityName)
    {
        return new \App\Http\Resources\\$modelName(\$$entityName);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  $postModel \$request
     * @param  $modelName \$$entityName
     * @return \App\Http\Resources\\$modelName
     * @throws \Exception
     */
    public function update($postModel \$request, $modelName \$$entityName)
    {
        return new \App\Http\Resources\\$modelName(\$this->service->save(\$request->toArray(), \$$entityName));
    }

    /**
     * Destroy an entity
     *
     * @param  \App\Models\\$modelName \$$entityName
     * @return mixed
     * @throws \Exception
     */
    public function destroy($modelName \$$entityName)
    {
        return \$this->service->remove(\$$entityName);
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
    private function saveControllersToFile()
    {
        foreach($this->modelList as $modelName => &$content) {
            $fileName = __DIR__ . '/../../../../../../app/Http/Controllers/'.$modelName. 'Controller.php';
            file_put_contents($fileName, $content);
            $this->info('Created controller class in '.$fileName);
        }
    }

}
