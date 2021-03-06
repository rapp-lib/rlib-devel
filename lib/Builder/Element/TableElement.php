<?php
namespace R\Lib\Builder\Element;

class TableElement extends Element_Base
{
    protected function init ()
    {
        // Col登録
        $cols = (array)$this->getAttr("cols");
        unset($this->attrs["cols"]);
        $enum_sets = array();
        $enum_name = $this->getName();
        foreach ($cols as $col_name => $col_attrs) {
            // Enum生成
            $enum_set_name = $col_name;
            if ($fkey_for = $col_attrs["def"]["fkey_for"]) {
                $enum_set_name = str_underscore($fkey_for);
            }
            if (in_array($col_attrs["type"],array("select", "radioselect", "checklist"))) {
                $enum_sets[$enum_set_name] = array("table_name"=>$this->getName(), "col_name"=>$col_name);
                $col_attrs["enum_name"] = $enum_name;
                $col_attrs["enum_set_name"] = $enum_set_name;
            }
            $this->children["col"][$col_name] = new ColElement($col_name, $col_attrs, $this);
        }
        // Enum登録
        if ($enum_sets) {
            $enum_attrs = array("enum_sets" => $enum_sets);
            $this->children["enum"][0] = new EnumElement($enum_name, $enum_attrs, $this);
        }
    }
    /**
     * テーブルクラス名
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Table";
    }
    /**
     * 定義上のテーブル名
     */
    public function getDefName ()
    {
        return $this->attrs["def"]["table_name"] ?: $this->getName();
    }
    /**
     * 定義の取得
     */
    public function getExtraDefs ()
    {
        $def = $this->attrs["def"];
        unset($def["table_name"]);
        unset($def["indexes"]);
        return $def;
    }
    /**
     * @getter children.col
     */
    public function getCols ()
    {
        return (array)$this->children["col"];
    }
    /**
     * @getter Col
     */
    public function getColByName ($col_name)
    {
        return $this->children["col"][$col_name];
    }
    public function getColsByAttr ($attr, $attr_value=true)
    {
        $cols = array();
        foreach ($this->getCols() as $col) {
            if ($value = $col->getAttr($attr)) {
                if ($attr_value===true || $value==$attr_value) $cols[] = $col;
            }
        }
        return $cols;
    }
    public function getColByAttr ($attr, $attr_value=true)
    {
        $cols = $this->getColsByAttr($attr, $attr_value);
        return $cols ? $cols[0] : null;
    }
    public function getIdCol ()
    {
        $id_col = $this->getColByAttr("def.id");
        if ( ! $id_col) report_error("TableにIDカラムがありません",array("table"=>$this));
        return $id_col;
    }
    public function getInputCols ()
    {
        return $this->getColsByAttr("type");
    }
    public function getOrdCol ()
    {
        return $this->getColByAttr("def.ord");
    }
    public function getLabelCol ()
    {
        return $this->getColByAttr("type", "text") ?: $this->getIdCol();
    }
    public function getIndexes ()
    {
        $indexes = array();
        foreach ($this->getCols() as $col) {
            $index_names = (array)$col->getAttr("indexes");
            if ($index_name = $col->getAttr("index")) {
                if (is_array($index_name)) $index_names = array_merge($index_names, $index_name);
                else $index_names[] = $index_name;
            }
            foreach ($index_names as $index_name) {
                if ( ! isset($indexes[$index_name])) {
                    $indexes[$index_name] = array("name"=>$index_name, "cols"=>array());
                }
                $indexes[$index_name]["cols"][] = $col->getName();
            }
        }
        return $indexes;
    }
    /**
     * @getter children.enum
     */
    public function getEnum ()
    {
        return $this->children["enum"][0];
    }
    /**
     * Tableクラスの定義があるかどうか
     */
    public function hasDef ()
    {
        return ! $this->getAttr("nodef");
    }
    /**
     * 物理Tableを持つかどうか
     */
    public function hasSchema ()
    {
        return $this->attrs["def"]["table_name"]!==false;
    }
    /**
     * AuthTableとして参照しているRoleがあれば取得
     */
    public function getAuthRole ()
    {
        foreach ($this->getSchema()->getRoles() as $role) {
            if ($role->getAuthTable()==$this) return $role;
        }
        return null;
    }
    /**
     * FkeyFor参照先にAuthTableとして参照されるTableを持つColを取得
     */
    public function getOwnedByCols ()
    {
        $cols = array();
        foreach ($this->getCols() as $col) {
            if ($fkey_for_table = $col->getFkeyForTable()) {
                if ($fkey_for_table->getAuthRole()) $cols[] = $col;
            }
        }
        return $cols;
    }
}
