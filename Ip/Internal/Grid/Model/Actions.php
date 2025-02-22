<?php
/**
 * @package   ImpressPages
 */

namespace Ip\Internal\Grid\Model;


/**
 * Table helper class designated to do Grid actions
 * @package Ip\Internal\Grid\Model
 */
class Actions
{
    /**
     * @var $config Config
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }


    public function delete($id)
    {
        $db = new Db($this->config);

        $fields = $this->config->fields();
        $curData = $db->fetchRow($id);
        foreach ($fields as $field) {
            $fieldObject = $this->config->fieldObject($field);
            $fieldObject->beforeDelete($id, $curData);
        }


        $sql = "
        DELETE
            " . $this->config->tableName() . "
        FROM
            " . $this->config->tableName() . "
            " . $this->config->joinQuery() . "
        WHERE
            " . $this->config->tableName() . "." . $this->config->idField() . " = :id
        ";

        $params = array(
            'id' => $id
        );

        ipDb()->execute($sql, $params);

        foreach ($fields as $field) {
            $fieldObject = $this->config->fieldObject($field);
            $fieldObject->afterDelete($id, $curData);
        }

    }

    public function update($id, $data)
    {
        $db = new Db($this->config);
        $oldData = $db->fetchRow($id);

        $fields = $this->config->fields();
        $dbData = array();
        foreach ($fields as $field) {
            if ($field['field'] == $this->config->idField() || isset($field['allowUpdate']) && !$field['allowUpdate']) {
                continue;
            }
            $fieldObject = $this->config->fieldObject($field);

            $fieldObject->beforeUpdate($id, $oldData, $data);
            $fieldData = $fieldObject->updateData($data);
            if (!is_array($fieldData)) {
                throw new \Ip\Exception("updateData method in class " . esc(
                    get_class($fieldObject)
                ) . " has to return array.");
            }
            $dbData = array_merge($dbData, $fieldData);
        }

        if ($this->config->updateFilter()) {
            $dbData = call_user_func($this->config->updateFilter(), $id, $dbData);
        }

        $this->updateDb($this->config->rawTableName(), $dbData, $id);

        foreach ($fields as $field) {
            $this->config->fieldObject($field)->afterUpdate($id, $oldData, $data);
        }

    }

    protected function updateDb($table, $update, $id)
    {
        if (empty($update)) {
            return false;
        }

        $sql = "UPDATE " . ipTable($table) . " " . $this->config->joinQuery() . " SET ";
        $params = array();
        foreach ($update as $column => $value) {
            if ($column == $this->config->idField()) {
                continue; //don't update id field
            }
            $sql .= "`{$column}` = ? , ";
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $params[] = $value;
        }
        $sql = substr($sql, 0, -2);

        $sql .= " WHERE ";


        $sql .= " " . ipTable($table) . ".`" . $this->config->idField() . "` = ? ";
        $params[] = $id;


        return ipDb()->execute($sql, $params);
    }


    public function create($data)
    {
        $fields = $this->config->fields();
        $dbData = array();
        foreach ($fields as $field) {
            $fieldObject = $this->config->fieldObject($field);
            $fieldObject->beforeCreate(null, $data);
            $fieldData = $fieldObject->createData($data);
            if (!is_array($fieldData)) {
                throw new \Ip\Exception("createData method in class " . esc(
                    get_class($fieldObject)
                ) . " has to return array.");
            }
            $dbData = array_merge($dbData, $fieldData);
        }

        $sortField = $this->config->sortField();
        if ($sortField) {
            if ($this->config->createPosition() == 'top') {
                $orderValue = ipDb()->selectValue($this->config->rawTableName(), "MIN(`$sortField`)", array());
                $dbData[$sortField] = is_numeric($orderValue) ? $orderValue - 1 : 1; // 1 if null
            } else {
                $orderValue = ipDb()->selectValue($this->config->rawTableName(), "MAX(`$sortField`)", array());
                $dbData[$sortField] = is_numeric($orderValue) ? $orderValue + 1 : 1; // 1 if null
            }
        }

        $recordId = ipDb()->insert($this->config->rawTableName(), $dbData);

        foreach ($fields as $field) {
            $fieldObject = $this->config->fieldObject($field);
            $fieldObject->afterCreate($recordId, $data);
        }
        return $recordId;
    }

    public function move($id, $targetId, $beforeOrAfter)
    {
        $sortField = $this->config->sortField();

        $priority = ipDb()->selectValue($this->config->rawTableName(), $sortField, array('id' => $targetId));
        if ($priority === false) {
            throw new \Ip\Exception('Target record doesn\'t exist');
        }

        $tableName = $this->config->tableName();

        if ($beforeOrAfter == 'before') {
            $sql = "
            SELECT
                `{$sortField}`
            FROM
                {$tableName}
                " . $this->config->joinQuery() . "
            WHERE
                `{$sortField}` < :rowNumber
            ORDER BY
                `$sortField` DESC";
        } else {
            $sql = "
            SELECT
                `{$sortField}`
            FROM
                {$tableName}
            WHERE
                `{$sortField}` > :rowNumber
            ORDER BY
                `$sortField` ASC";
        }

        $params = array(
            'rowNumber' => $priority
        );

        $priority2 = ipDb()->fetchValue($sql, $params);

        if ($priority2 === null) {
            if ($beforeOrAfter == 'before') {
                $newPriority = $priority - 5;
            } else {
                $newPriority = $priority + 5;
            }
        } else {
            $newPriority = ($priority + $priority2) / 2;
        }

        ipDb()->update(
            $this->config->rawTableName(),
            array($sortField => $newPriority),
            array($this->config->idField() => $id)
        );
    }
}
