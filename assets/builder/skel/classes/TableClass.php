<#?php
namespace R\App\Table;

/**
 * @table
 */
class <?=$table->getClassName()?> extends Table_App
{
<?php if ($table->hasSchema()): ?>
    protected static $table_name = "<?=$table->getDefName()?>";
<?php else: ?>
    protected static $table_name = false;
<?php endif; ?>
    protected static $cols = array(
<?php foreach ($table->getCols() as $col): ?>
<?=$col->getColDefSource()?>
<?php endforeach; ?>
    );
    protected static $rules = array(
<?php foreach ($table->getCols() as $col): ?>
<?=$col->getTableRuleDefSource()?>
<?php endforeach; ?>
    );
    protected static $aliases = array(
<?php foreach ($table->getCols() as $col): ?>
<?=$col->getAliasDefSource()?>
<?php endforeach; ?>
    );
    protected static $def = array(
        "comment" => "<?=$table->getAttr("label")?>",
        "indexes" => array(
<?php foreach ($table->getIndexes() as $index): ?>
            array("name"=>"<?=$index["name"]?>", "cols"=>array("<?=implode($index["cols"],'", "')?>")),
<?php endforeach; ?>
        ),
<?=R\Lib\Builder\CodeRenderer::elementLines(2, (array)$table->getExtraDefs())?>
    );
}
