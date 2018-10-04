    /**
     * リマインダーフォーム
     */
    protected static $form_entry = array(
        "form_page" => "<?=$pageset->getPageByType("reminder")->getFullPage()?>",
        "fields" => array(
            "mail"=>array("label"=>"メール"),
        ),
        "rules" => array(
            "mail",
            array("mail", "format", "format"=>"mail"),
            array("mail", "registered", "table"=>"<?=$table->getName()?>", "col_name"=>"<?=$table->getColByAttr("def.mail")->getName()?>"),
        ),
    );
<?=$pageset->getPageByType("reminder")->getMethodDecSource()?>
    {
        if ($this->forms["entry"]->receive($this->input)) {
            if ($this->forms["entry"]->isValid()) {
                $this->forms["entry"]->save();
                return $this->redirect("id://<?=$pageset->getPageByType("send")->getLocalPage()?>");
            }
        } else {
            $this->forms["entry"]->clear();
        }
    }
<?=$pageset->getPageByType("send")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
        if ( ! $this->forms["entry"]->isEmpty()) {
            // Credの発行
            $t = (array)$this->forms["entry"];
            $cred = app()->cache("cred")->createCred($t);
            $ttl = app()->cache("cred")->getTTL();
            // URL通知メールの送信
            $uri = $this->uri("id://<?=$pageset->getPageByType("reset")->getLocalPage()?>", array("cred"=>$cred));
<?php if ($mail = $pageset->getMailByType("mailcheck")): ?>
            app("mailer")->send("mail://<?=$mail->getTemplateFile()?>", array("t"=>$t, "uri"=>$uri, "ttl"=>$ttl), function($message){});
<?php endif; ?>
            $this->forms["entry"]->clear();
        }
    }
    /**
     * PW入力フォーム
     */
    protected static $form_reset = array(
        "form_page" => "<?=$pageset->getPageByType("reset")->getFullPage()?>",
        "fields" => array(
            "cred"=>array("col"=>false),
            "login_pw"=>array("label"=>"パスワード"),
            "login_pw_confirm"=>array("label"=>"パスワード確認", "col"=>false),
        ),
        "rules" => array(
            "cred",
            "login_pw",
            array("login_pw_confirm", "required", "if"=>array("login_pw"=>true)),
            array("login_pw_confirm", "confirm", "target_field"=>"login_pw"),
        ),
    );
<?=$pageset->getPageByType("reset")->getMethodDecSource()?>
    {
        if ($this->forms["reset"]->receive($this->input)) {
            if ($this->forms["reset"]->isValid()) {
                $this->forms["reset"]->save();
                return $this->redirect("id://<?=$pageset->getPageByType("complete")->getLocalPage()?>");
            }
        } else {
            $this->forms["reset"]->clear();
            $this->forms["reset"]["cred"] = $this->input["cred"];
        }
        $this->vars["cred_data"] = app()->cache("cred")->readCred($this->forms["reset"]["cred"]);
    }
<?=$pageset->getPageByType("complete")->getMethodDecSource()?>
    {
        $this->forms["reset"]->restore();
        if ( ! $this->forms["reset"]->isEmpty()) {
            // Credの解決
            $cred = $this->forms["reset"]["cred"];
            $cred_data = app()->cache("cred")->readCred($cred);
            // パスワードの更新
            $t = table("<?=$table->getName()?>")->findBy("<?=$table->getColByAttr("def.mail")->getName()?>", $cred_data["mail"])->selectOne();
            table("<?=$table->getName()?>")->updateById($t["id"], array(
                "<?=$table->getColByAttr("def.login_pw")->getName()?>" => $this->forms["reset"]["login_pw"]
            ));
            app()->cache("cred")->dropCred($cred);
            $this->forms["reset"]->clear();
        }
    }
