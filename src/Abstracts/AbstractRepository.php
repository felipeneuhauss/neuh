<?php

namespace Neuh\Abstracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Repositories
 * @package App\Repositories
 */
abstract class AbstractRepository
{
    const PER_PAGE = 15;

    const CACHE_MINUTES = 1440;

    /**
     * @var Model
     */
    protected $model;

    /**
     * AbstractRepository constructor.
     * @param Model|null $model
     * @throws \Exception
     */
    public function __construct(Model $model = null)
    {
        if (is_null($model)) {
            throw new \Exception('Model is required');
        }

        $this->model = $model;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function existsOnTrash(array $params = []): bool
    {
        $query = $this->model->query()->onlyTrashed();

        if (count($params)) {
            foreach ($params as $param => $value) {
                $query->where($param, $value);
            }
        }

        return $query->exists();
    }

    /**
     * Filter data
     *
     * @param $params
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filter($params)
    {
        $query = $this->model->query();

        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $query->where($param, $value);
            }
        }

        return $query;
    }

    /**
     * Get all paginated
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $params = [])
    {
        return $this->filter($params)->paginate(self::PER_PAGE);
    }

    /**
     * Get all without pagination
     *
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all(array $params = [])
    {
        return $this->filter($params)->get();
    }

    /**
     * Create new entity
     *
     * @param array $params
     * @return mixed
     */
    public function create(array $params = [])
    {
        $this->model->fill($params);
        $vo = $this->model->create($params);
        return $vo;
    }

    /**
     * @param array $params
     * @param Model $vo
     * @return Model
     * @throws \Exception
     */
    public function update(Model $vo, array $params = [])
    {
        if (is_null($vo)) {
            throw new \Exception('No entity found');
        }

        $vo->fill($params);
        $vo->save();
        return $vo;
    }

    /**
     * Destroy an entity
     *
     * @param Model $vo
     * @return bool|null
     * @throws \Exception
     */
    public function destroy(Model $vo)
    {
        if (is_null($vo)) {
            throw new \Exception('Id field is required to delete');
        }

        return $vo->delete();
    }

    /**
     * Find an entity
     *
     * @param string|null $id
     * @return mixed
     */
    public function find(string $id = null)
    {
        return $this->model->find($id);
    }
}
