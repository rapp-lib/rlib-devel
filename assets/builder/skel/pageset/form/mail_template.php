<?php if ($mail->getAttr("type")==="admin"): ?>
<#?php $message->to(app()->config["mail.admin_to"]); ?>
<?php else: ?>
<#?php $message->to($t["<?=$mail->getAttr("mail_col_name")?>"]); ?>
<?php endif; ?>
<#?php $message->subject("<?=$mail->getController()->getlabel()?> 完了通知メール"); ?>
下記の通り受け付けました

<?php foreach ($mail->getController()->getMailCols() as $col): ?>
<?=$col->getLabel()?> : <?=$col->getMailSource(array("mail"=>$mail))?><?="\n"?>
<?php endforeach; ?>
