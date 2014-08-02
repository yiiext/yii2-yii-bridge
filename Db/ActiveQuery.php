<?php
/**
 * Slavcodev Components
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace Slavcodev\YiiBridge\Db;

use Yii;
use CActiveRecord;
use yii\db\ActiveQuery as BaseActiveQuery;

/**
 * Class ActiveQuery
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @version 0.1
 */
class ActiveQuery extends BaseActiveQuery
{
    /** @var \CActiveRecord */
    private $finder;

    public function __construct($modelClass, $config = [])
    {
        parent::__construct($modelClass, $config);
        $this->finder = CActiveRecord::model($modelClass);
    }

    /**
     * @param null $db
     *
     * @return array|\CActiveRecord[]
     */
    public function all($db = null)
    {
        $cmd = $this->createCommand($db);
        $rows = $this->finder->findAllBySql($cmd->getSql(), $cmd->params);
        return $this->prepareResult($rows);
    }

    /**
     * @param null $db
     *
     * @return array|null|CActiveRecord
     */
    public function one($db = null)
    {
        $cmd = $this->createCommand($db);
        $row = $this->finder->findBySql($cmd->getSql(), $cmd->params);
        $rows = $this->prepareResult($row ? [$row] : []);
        return reset($rows) ?: null;
    }

    /**
     * @param array|CActiveRecord[] $rows
     *
     * @return array|CActiveRecord[]
     */
    public function prepareResult($rows)
    {
        if (empty($rows)) {
            return [];
        }

        if (!empty($this->with)) {
            $this->findWith($this->with, $rows);
        }

        if ($this->asArray || !empty($this->indexBy)) {
            $data = [];

            foreach ($rows as $key => $row) {
                if (is_string($this->indexBy)) {
                    $key = $row[$this->indexBy];
                } elseif (is_callable($this->indexBy)) {
                    $key = call_user_func($this->indexBy, $row);
                }

                if ($this->asArray) {
                    $values = $row->getAttributes();

                    if (!empty($this->with)) {
                        foreach ($this->with as $relatedName) {
                            $values[$relatedName] = $row->getRelated($relatedName);
                        }
                    }
                }

                $data[$key] = isset($values) ? $values : $row;
            }
        }

        return isset($data) ? $data : $rows;
    }

    /**
     * @param CActiveRecord $model
     * @param string $name
     *
     * @return self
     */
    private function getRelatedQuery($model, $name)
    {
        $relation = $this->finder->getActiveRelation($name);
        $table = $this->finder->getMetaData()->tableSchema;

        $query = new self($relation->className);
        $query->primaryModel = $model;
        $query->link = [$table->primaryKey => $relation->foreignKey];
        $query->multiple = $relation instanceof \CHasManyRelation;
        return $query;
    }

    /**
     * @param array $with
     * @param array|CActiveRecord[] $models
     */
    public function findWith($with, &$models)
    {
        $relations = [];

        foreach ($with as $name => $callback) {
            if (is_integer($name)) {
                $name = $callback;
                $callback = null;
            }

            if (($pos = strpos($name, '.')) !== false) {
                // with sub-relations
                $childName = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
            } else {
                $childName = null;
            }

            if (!isset($relations[$name])) {
                $query = $this->getRelatedQuery($this->finder, $name);
                $query->primaryModel = null;
                $relations[$name] = $query;
            } else {
                $query = $relations[$name];
            }

            if (isset($childName)) {
                $query->with[$childName] = $callback;
            } elseif ($callback !== null) {
                call_user_func($callback, $query);
            }
        }

        /* @var $relation ActiveQuery */
        foreach ($relations as $name => $relation) {
            if ($relation->asArray === null) {
                // inherit asArray from primary query
                $relation->asArray($this->asArray);
            }
            $relation->populateRelation($name, $models);
        }
    }

    public function prepareBuild($builder)
    {
        if (empty($this->from)) {
            $this->from = [$this->finder->tableName()];
        }

        parent::prepareBuild($builder);
    }

    public function createCommand($db = null)
    {
        if ($db === null) {
            $db = Yii::$app->getDb();
        }

        if (!empty($this->joinWith)) {
            $this->buildJoinWith();
            $this->joinWith = null;    // clean it up to avoid issue https://github.com/yiisoft/yii2/issues/2687
        }

        return parent::createCommand($db);
    }

    private function buildJoinWith()
    {
        $join = $this->join;
        $this->join = [];

        foreach ($this->joinWith as $config) {
            list ($with, $eagerLoading, $joinType) = $config;

            $this->joinWithRelations(new $this->modelClass, $with, $joinType);

            if (is_array($eagerLoading)) {
                foreach ($with as $name => $callback) {
                    if (is_integer($name)) {
                        if (!in_array($callback, $eagerLoading, true)) {
                            unset($with[$name]);
                        }
                    } elseif (!in_array($name, $eagerLoading, true)) {
                        unset($with[$name]);
                    }
                }
            } elseif (!$eagerLoading) {
                $with = [];
            }

            $this->with($with);
        }

        // remove duplicated joins added by joinWithRelations that may be added
        // e.g. when joining a relation and a via relation at the same time
        $uniqueJoins = [];
        foreach ($this->join as $j) {
            $uniqueJoins[serialize($j)] = $j;
        }
        $this->join = array_values($uniqueJoins);

        if (!empty($join)) {
            // append explicit join to joinWith()
            // https://github.com/yiisoft/yii2/issues/2880
            $this->join = empty($this->join) ? $join : array_merge($this->join, $join);
        }
    }

    /**
     * @param \CActiveRecord $model
     * @param array $with
     * @param array|string $joinType
     */
    private function joinWithRelations($model, $with, $joinType)
    {
        $relations = [];

        foreach ($with as $name => $callback) {
            if (is_integer($name)) {
                $name = $callback;
                $callback = null;
            }

            $primaryModel = $model;
            $parent = $this;
            $prefix = '';
            while (($pos = strpos($name, '.')) !== false) {
                $childName = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
                $fullName = $prefix === '' ? $name : "$prefix.$name";
                if (!isset($relations[$fullName])) {
                    $relations[$fullName] = $relation = $this->getRelatedQuery($primaryModel, $name);
                    $this->joinWithRelation($parent, $relation, $this->getJoinType($joinType, $fullName));
                } else {
                    $relation = $relations[$fullName];
                }
                $primaryModel = new $relation->modelClass;
                $parent = $relation;
                $prefix = $fullName;
                $name = $childName;
            }

            $fullName = $prefix === '' ? $name : "$prefix.$name";

            if (!isset($relations[$fullName])) {
                $relations[$fullName] = $relation = $this->getRelatedQuery($primaryModel, $name);

                if ($callback !== null) {
                    call_user_func($callback, $relation);
                }

                $this->joinWithRelation($parent, $relation, $this->getJoinType($joinType, $fullName));
            }
        }
    }

    private function getJoinType($joinType, $name)
    {
        if (is_array($joinType) && isset($joinType[$name])) {
            return $joinType[$name];
        } else {
            return is_string($joinType) ? $joinType : 'INNER JOIN';
        }
    }

    /**
     * @param self $parent
     * @param self $child
     * @param string $joinType
     */
    private function joinWithRelation($parent, $child, $joinType)
    {
        $via = $child->via;
        $child->via = null;
        if ($via instanceof ActiveQuery) {
            // via table
            $this->joinWithRelation($parent, $via, $joinType);
            $this->joinWithRelation($via, $child, $joinType);

            return;
        } elseif (is_array($via)) {
            // via relation
            $this->joinWithRelation($parent, $via[1], $joinType);
            $this->joinWithRelation($via[1], $child, $joinType);

            return;
        }

        list ($parentTable, $parentAlias) = $this->getQueryTableName($parent);
        list ($childTable, $childAlias) = $this->getQueryTableName($child);

        if (!empty($child->link)) {

            if (strpos($parentAlias, '{{') === false) {
                $parentAlias = '{{' . $parentAlias . '}}';
            }
            if (strpos($childAlias, '{{') === false) {
                $childAlias = '{{' . $childAlias . '}}';
            }

            $on = [];
            foreach ($child->link as $childColumn => $parentColumn) {
                $on[] = "$parentAlias.[[$parentColumn]] = $childAlias.[[$childColumn]]";
            }
            $on = implode(' AND ', $on);
            if (!empty($child->on)) {
                $on = ['and', $on, $child->on];
            }
        } else {
            $on = $child->on;
        }
        $this->join($joinType, empty($child->from) ? $childTable : $child->from, $on);

        if (!empty($child->where)) {
            $this->andWhere($child->where);
        }
        if (!empty($child->having)) {
            $this->andHaving($child->having);
        }
        if (!empty($child->orderBy)) {
            $this->addOrderBy($child->orderBy);
        }
        if (!empty($child->groupBy)) {
            $this->addGroupBy($child->groupBy);
        }
        if (!empty($child->params)) {
            $this->addParams($child->params);
        }
        if (!empty($child->join)) {
            foreach ($child->join as $join) {
                $this->join[] = $join;
            }
        }
        if (!empty($child->union)) {
            foreach ($child->union as $union) {
                $this->union[] = $union;
            }
        }
    }

    /**
     * @param self $query
     * @return array
     */
    private function getQueryTableName($query)
    {
        if (empty($query->from)) {
            $tableName = $query->finder->tableName();
        } else {
            $tableName = '';
            foreach ($query->from as $alias => $tableName) {
                if (is_string($alias)) {
                    return [$tableName, $alias];
                } else {
                    break;
                }
            }
        }

        if (preg_match('/^(.*?)\s+({{\w+}}|\w+)$/', $tableName, $matches)) {
            $alias = $matches[2];
        } else {
            $alias = $tableName;
        }

        return [$tableName, $alias];
    }
}
