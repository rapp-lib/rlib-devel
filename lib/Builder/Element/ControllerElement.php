<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class ControllerElement extends Element_Base
{
    protected function init ()
    {
        $pagesets = array();
        // Pagesetの補完
        if ($this->getAttr("type") == "index") {
            $pagesets[] = array("type"=>"index");
        } elseif ($this->getAttr("type") == "blank") {
            $pagesets[] = array("type"=>"blank");
        } elseif ($this->getAttr("type") == "login") {
            $pagesets[] = array("type"=>"login");
            if ($this->getFlagAttr("use_reminder", false)) {
                $pagesets[] = array("type"=>"reminder");
            }
        } elseif ($this->getAttr("type") == "reminder") {
            $pagesets[] = array("type"=>"reminder");
        } elseif ($this->getAttr("type") == "form") {
            $pagesets[] = array("type"=>"form",
                "param_fields.depend"=>$this->getFlagAttr("param_fields.depend",array()),
                "use_mail"=>$this->getFlagAttr("use_mail",false),
                "is_master"=>$this->getFlagAttr("is_master",false),
                "skip_confirm"=>$this->getFlagAttr("skip_confirm", false),
                "skip_complete"=>$this->getFlagAttr("skip_complete", false));
        } elseif ($this->getAttr("type") == "detail") {
            $pagesets[] = array("type"=>"detail");
        } elseif ($this->getAttr("type") == "delete") {
            $pagesets[] = array("type"=>"delete");
        } elseif ($this->getAttr("type") == "apply") {
            $pagesets[] = array("type"=>"apply",
                "param_fields.append"=>$this->getFlagAttr("param_fields.append",array()));
        } elseif ($this->getAttr("type") == "cart") {
            $pagesets[] = array("type"=>"cart",
                "param_fields.append"=>$this->getFlagAttr("param_fields.append",array()));
        } elseif ($this->getAttr("type") == "list"
                || $this->getAttr("type") == "master"
                || $this->getAttr("type") == "fav") {
            $is_master = $this->getAttr("type") == "master";
            $is_fav = $this->getAttr("type") == "fav";
            $pagesets[] = array("type"=>"list",
                "param_fields.depend"=>$this->getFlagAttr("param_fields.depend",array()));
            if ($this->getFlagAttr("use_detail", false)) {
                $pagesets[] = array("type"=>"detail");
            }
            if ($this->getFlagAttr("use_form", $is_master ? true : false)) {
                $pagesets[] = array("type"=>"form",
                    "param_fields.depend"=>$this->getFlagAttr("param_fields.depend",array()),
                    "use_mail"=>$this->getFlagAttr("use_mail",false),
                    "is_master"=>$this->getFlagAttr("is_master",true),
                    "skip_confirm"=>$this->getFlagAttr("skip_confirm", true),
                    "skip_complete"=>$this->getFlagAttr("skip_complete", true));
            }
            if ($this->getFlagAttr("use_delete", $is_master||$is_fav ? true : false)) {
                $pagesets[] = array("type"=>"delete");
            }
            if ($this->getFlagAttr("use_apply", $is_fav ? true : false)) {
                $pagesets[] = array("type"=>"apply",
                    "param_fields.append"=>$this->getFlagAttr("param_fields.append",array()));
            }
            if ($this->getFlagAttr("use_csv", false)) {
                $pagesets[] = array("type"=>"csv");
            }
            if ($this->getFlagAttr("use_import", false)) {
                $pagesets[] = array("type"=>"import",
                    "param_fields.depend"=>$this->getFlagAttr("param_fields.depend",array()));
            }
        } else {
            report_error("Controller typeの指定が不正です",array(
                "name" => $this->getName(),
                "type" => $this->getAttr("type"),
            ));
        }
        // sub_pageの追加
        foreach ((array)$this->getAttr("sub_page") as $sub_page) {
            if ( ! $sub_page["type"]) $sub_page["type"] = "blank";
            $pagesets[] = $sub_page;
        }
        // Pagesetの登録
        foreach ($pagesets as $pageset_attrs) {
            $pageset_name = $pageset_attrs["name"] ?: $pageset_attrs["type"];
            $this->children["pageset"][$pageset_name]
                = new PagesetElement($pageset_name, $pageset_attrs, $this);
        }
    }
    /**
     * ラベルの取得
     */
    public function getLabel ()
    {
        return $this->getAttr("label");
    }
    /**
     * クラス名の取得
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Controller";
    }
    /**
     * 生成パターンを切り替えるフラグの取得
     */
    public function getFlagAttr ($name, $default=false)
    {
        $flag = $this->getAttr($name);
        return isset($flag) ? $flag : $default;
    }
    /**
     * 認証必須設定
     */
    public function getPrivRequired ()
    {
        return $this->getAttr("priv_required");
    }
    /**
     * type
     */
    public function getType ()
    {
        return $this->getAttr("type");
    }
    /**
     * 関係するRoleの取得
     */
    public function getRole ()
    {
        $role_name = $this->getAttr("access_as");
        return $this->getSchema()->getRoleByName($role_name);
    }
    /**
     * 関係するTableの取得
     */
    public function getTable ()
    {
        $table_name = $this->getAttr("table");
        return $this->getSchema()->getTableByName($table_name);
    }
    /**
     * 入力画面に表示するColの取得
     */
    public function getInputCols ($table=false)
    {
        if ($table===false) $table = $this->getTable();
        $cols = $table->getInputCols();
        // use_atによるフィルタリング
        $filtered_cols = array();
        foreach ($cols as $i => $col) {
            if (is_array($use_at = $col->getAttr("use_at"))) {
                foreach ($use_at as $controller_name) {
                    if ($controller_name == $this->getName()) $filtered_cols[] = $col;
                }
            }
        }
        return $filtered_cols ?: $cols;
    }
    /**
     * 入力画面に表示するColの取得
     */
    public function getAssocInputCols ($parent_col)
    {
        $cols = $this->getInputCols($parent_col->getAssocTable());
        $cols = array_filter($cols, function($col) use ($parent_col){
            return $col->getAttr("def.fkey_for")!==$parent_col->getTable()->getName()
                && $col->getAttr("type")!=="assoc";
        });
        return $cols;
    }
    /**
     * ソート対象にするColであるか判定
     */
    public function isSortCol ($col)
    {
        if ( ! $this->getAttr("sort_fields")) return false;
        return in_array($col->getName(), $this->getSortatbleFieldNames());
    }
    /**
     * デフォルトのソート対象Colを取得
     */
    public function getDefaultSortFieldName ()
    {
        $table = $this->getTable();
        return $table->getOrdCol() ? $table->getOrdCol()->getName() : $table->getIdCol()->getName();
    }
    /**
     * ソート対象にできるColを取得
     */
    public function getSortatbleFieldNames ()
    {
        $names = (array)$this->getAttr("sort_fields");
        $default = $this->getDefaultSortFieldName();
        if ( ! in_array($default, $names)) array_unshift($names, $default);
        return $names;
    }
    /**
     * 一覧画面に表示するColの取得
     */
    public function getListCols ()
    {
        $cols = array();
        if ($col_names = $this->getAttr("list_fields")) {
            foreach ($col_names as $col_name) $cols[] = $this->getTable()->getColByName($col_name);
        } else {
            $cols = $this->getInputCols();
            $cols = array_filter($cols, function($col){
                return ! in_array($col->getAttr("type"),array(
                    "assoc", "password", "textarea", "checklist", "checkbox", "file"));
            });
            $cols = array_slice($cols,0,5);
            array_unshift($cols, $this->getTable()->getIdCol());
        }
        return $cols;
    }
    /**
     * メールに表示するColの取得
     */
    public function getMailCols ()
    {
        $cols = $this->getInputCols();
        $cols = array_filter($cols, function($col){
            return ! in_array($col->getAttr("type"),array("password"));
        });
        return $cols;
    }
    /**
     * CSVに表示するColの取得
     */
    public function getCsvCols ()
    {
        $cols = $this->getInputCols();
        $cols = array_filter($cols, function($col){
            return $col->getAttr("type")!=="assoc" && $col->hasColDef();
        });
        return $cols;
    }
    /**
     * @getter Pageset
     */
    public function getPagesets ()
    {
        return (array)$this->children["pageset"];
    }
    public function getPagesetsByType ($type)
    {
        $pagesets = array();
        foreach ($this->getPagesets() as $pageset) {
            if ($pageset->getAttr("type")==$type) {
                $pagesets[] = $pageset;
            }
        }
        return $pagesets;
    }
    public function getPagesetByType ($type)
    {
        return array_shift($pagesets = $this->getPagesetsByType($type));
    }
    /**
     * @getter Pageset
     */
    public function getIndexPageset ()
    {
        // 一番はじめに追加されたPagesetを返す
        foreach ($this->getPagesets() as $pageset) return $pageset;
        return null;
    }
    /**
     * @getter Page
     */
    public function getIndexPage ()
    {
        return $this->getIndexPageset()->getIndexPage();
    }
}
