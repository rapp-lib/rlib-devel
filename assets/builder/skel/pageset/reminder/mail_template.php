<#?php $message->to($t["<?=$mail->getAttr("mail_col_name")?>"]); ?>
<#?php $message->subject("<?=$mail->getController()->getlabel()?> URL通知メール"); ?>
以下のURLより手続きを完了してください。

<#?=$uri?><#?="\n"?>

<#?php if ($ttl): ?>
※有効期限 <#?=date("Y/m/d H:i", time() + $ttl)?> まで<#?="\n"?>
<#?php endif; ?>
