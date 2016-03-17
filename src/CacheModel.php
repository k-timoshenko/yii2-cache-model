<?php
/**
 * Created by Konstantin Timoshenko
 * Date: 11/22/15
 * Time: 2:02 AM
 * Email: t.kanstantsin@gmail.com
 */

namespace tkanstantsin\cache;

use Exception;
use yii\base\Component;
use yii\db\Query;
use yii\helpers\ArrayHelper;


/**
 * Class CacheModel
 *
 * @todo: write test.
 *
 * @package common\components\db
 */
class CacheModel extends Component
{

    /**
     * @var string
     */
    public $tableNameMethod = 'tableName';

    /**
     * Cache duration in cache component
     * @var int - default 1 day
     */
    public $duration = 86400;

    /**
     * Component for caching
     * @var string
     */
    public $cache = 'cache';

    /**
     * Component for db queries
     * @var string
     */
    public $db = 'db';

    /**
     * Additional model filters (e.g. is_deleted => false)
     * @var array
     */
    public $filters;
    /**
     * Order parameters
     * @var array
     */
    public $orders;

    /**
     * @var array
     */
    protected $models = [];

    /**
     * @param string $className
     * @param int|bool $id - id of model. `False` for all records of the model.
     * @note: `null` - usual case when record is not defined, so default value for $id was changed to `false`
     * @return array|null|\yii\base\Object|\yii\base\Object[]
     */
    public function get($className, $id = false)
    {
        $this->setModels($className);

        return $this->getCachedModel($className, $id);
    }

    /**
     * Flushes (deletes) cached data for @param $className
     * @param string $className
     */
    public function flush($className)
    {
        ArrayHelper::remove($this->models, $className);
        $this->getCacheComponent()->delete($this->getCacheId($className));
    }

    /**
     * Returns cached model from local storage
     * @param string $className
     * @param int|bool $id
     * @see CacheModel::get()
     * @return array|null|\yii\base\Object|\yii\base\Object[]
     */
    public function getCachedModel($className, $id = false)
    {
        $modelArray = ArrayHelper::getValue($this->models, $className, []);

        if ($id === false) {
            return $modelArray;
        } elseif (is_array($id)) {
            $idArray = $id;
            $values = [];

            foreach ($idArray as $id) {
                $values[$id] = ArrayHelper::getValue($modelArray, $id, null);
            }

            return $values;
        } else {
            return ArrayHelper::getValue($modelArray, $id, null);
        }
    }

    /**
     * Whether model selected from `db` or `cache` component and stored in local {models} variable
     * @param string $className
     * @return bool
     */
    public function isModelSelected($className)
    {
        return ArrayHelper::keyExists($className, $this->models);
    }

    /**
     * Whether model cached in `cache` component
     * @param string $className
     * @return bool
     */
    public function isModelCached($className)
    {
        return $this->getCacheComponent()->exists($this->getCacheId($className));
    }

    /**
     * Return id for cached models by $className
     * @param string $className
     * @return string
     */
    public function getCacheId($className)
    {
        return 'cache-model-component-' . $className;
    }


    /**
     * Sets cached models in `cache` component and local storage
     * @param $className
     */
    protected function setModels($className)
    {
        if (!$this->isModelSelected($className)) {
            if ($this->isModelCached($className)) {
                $this->setModelFromCache($className);
            } else {
                $this->setModelFromDb($className);
            }
        }
    }

    /**
     * Select new data and store in cache
     * Used on first-time call or when cache expired
     * @param string $className
     * @throws \yii\base\Exception
     */
    protected function setModelFromDb($className)
    {
        $objectArray = $this->prepareModel($className, $this->findModel($className));

        // set in cache
        $this->getCacheComponent()->set($this->getCacheId($className), $objectArray, $this->duration);
        // set in local cache
        $this->models[$className] = $objectArray;
    }

    /**
     * Set to local {models} array from `cache` component
     * @param string $className
     * @throws \yii\base\Exception
     */
    protected function setModelFromCache($className)
    {
        // set in local cache
        $this->models[$className] = $this->getCacheComponent()->get($this->getCacheId($className));
    }

    /**
     * Finds model array for $className
     * @param string $className
     * @return array
     * @throws Exception
     */
    protected function findModel($className)
    {
        if (!method_exists($className, $this->tableNameMethod)) {
            throw new Exception("`{$className}` must implement method `{$this->tableNameMethod}`.");
        }

        $tableNameMethod = $this->tableNameMethod;

        $query = (new Query())
            ->from($className::{$tableNameMethod}());

        if (($filter = ArrayHelper::getValue($this->filters, $className)) !== null) {
            $query->where($filter);
        }

        if (($orderBy = ArrayHelper::getValue($this->orders, $className)) !== null) {
            $query->orderBy($orderBy);
        }

        return $query->all($this->getDbComponent());
    }

    /**
     * Created classes by $className and rows from db
     * @param string $className
     * @param array $modelArray
     * @return array
     */
    protected function prepareModel($className, array $modelArray)
    {
        $objectArray = [];
        foreach ($modelArray as $row) {
            $objectArray[$row['id']] = new $className($row);
        }

        return $objectArray;
    }

    /**
     * Gets global `cache` component
     * @return \yii\caching\Cache
     */
    protected function getCacheComponent()
    {
        return \Yii::$app->{$this->cache};
    }

    /**
     * Gets global `db` component
     * @return \yii\db\Connection
     */
    protected function getDbComponent()
    {
        return \Yii::$app->{$this->db};
    }
}