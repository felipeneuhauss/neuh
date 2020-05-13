<?php

namespace Neuh\Abstracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Service
 *
 * @package App\Services
 */
abstract class AbstractService
{
    /**
     * @var AbstractRepository
     */
    protected $repository;

    /**
     * Create an entity
     *
     * @param array $params
     * @return mixed
     */
    public function create(array $params = [])
    {
        $params = $this->preCreate($params);
        $vo = $this->repository->create($params);
        $this->postCreate($vo, $params);
        return $vo;
    }

    /**
     * Update an entity
     *
     * @param array $params
     * @param Model $vo
     * @return Model
     * @throws \Exception
     */
    public function update(array $params = [], Model $vo)
    {
        $params = $this->preUpdate($params, $vo);
        $vo = $this->repository->update($params, $vo);
        $this->postUpdate($vo, $params);
        return $vo;
    }

    /**
     * Destroy an entity
     *
     * @param Model $model
     * @return mixed
     * @throws \Exception
     */
    public function destroy(Model $model)
    {
        if ($this->repository->destroy($model)) {
            return;
        }
        abort(404);
    }

    /**
     * Get paginated entity
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $params = [])
    {
        if (!empty($params['page'])) {
            unset($params['page']);
        }

        return $this->repository->paginate($params);
    }

    /**
     * Get all entity list
     *
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all(array $params = [])
    {
        return $this->repository->all($params);
    }

    /**
     * Get entity by id
     *
     * @param null $id
     * @return mixed
     * @throws \Exception
     */
    public function find($id = null)
    {

        if (!$this->isValidUuid($id)) {
            throw new \Exception('Trying get by an invalid uuid');
        }

        return $this->repository->find($id);
    }

    /**
     * Check if a given string is a valid UUID
     *
     * @param   string $uuid The string to check
     * @return  boolean
     */
    public function isValidUuid($uuid = null)
    {
        if (is_null($uuid) || !is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
            return false;
        }
        return true;
    }

    /**
     * @param array $params
     * @param Model|null $vo
     * @return array
     */
    protected function prepareParams(array $params = [], Model $vo = null): array
    {
        unset($params['created_at']);
        unset($params['updated_at']);

        return $params;
    }

    /**
     * Prepare data
     *
     * @param array $params
     * @return array
     */
    protected function preCreate($params = []) {
        if (auth()->check()) {
            $params['user_id'] = auth()->user()->id;
        }
        $params = $this->prepareParams($params);
        return $params;
    }

    /**
     * Prepare data to update
     *
     * @param $params
     * @param Model $vo
     * @return array
     */
    protected function preUpdate($params = [], Model $vo) {
        unset($params['user_id']);
        $params = $this->prepareParams($params);
        return $params;
    }


    /**
     * Post create entity. Useful when you want to do some thing like attach relationships, send email or call some event
     *
     * @param Model $vo
     * @param array $params
     */
    protected function postCreate(Model $vo, array $params = [])
    {
    }

    /**
     * Post update entity. Useful when you want to do some thing like attach relationships, send email or call some event
     *
     * @param Model $vo
     * @param array $params
     */
    protected function postUpdate(Model $vo, array $params = [])
    {
    }
}
