<?php

namespace Neuh\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:entities {tableName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create automatically controller, resources, tests, form request and etc.';

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
        $modalName = $this->prepareModelName($tableName);
        $this->call('create:model', ['tableName' => $tableName]);
        $this->call('create:service', ['tableName' => $tableName]);
        $this->call('create:controller', ['tableName' => $tableName]);
        $this->call('make:factory', ['name' => $modalName . 'Factory']);
        $this->call('make:seeder', ['name' => $modalName . 'TableSeeder']);
        $this->call('make:test', ['name' => $modalName . 'Test']);
        $this->call('make:resource', ['name' => $modalName]);
        $this->call('make:resource', ['name' => $modalName . 'Collection']);
        $this->call('make:request', ['name' => $modalName . 'Post']);
        $this->call('make:repository', ['name' => $modalName . 'Repository']);
    }

    private function prepareModelName($tableName)
    {
        $modelName = trim(ucfirst(Str::singular($tableName)));

        if (strpos($tableName, '_') !== 0) {
            $modelNameExplodeList = explode('_', strtolower($tableName));

            $newModelName = '';
            foreach ($modelNameExplodeList as $kName => $name) {
                $newModelName .= ucfirst(trim(Str::singular($name)));
            }

            $modelName = $newModelName;
        }

        return $modelName;
    }
}
