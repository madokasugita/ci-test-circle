<?php
require_once 'Smarty/Smarty.class.php';

class MreSmarty extends Smarty
{
    public function __construct()
    {
        $this->template_dir = DIR_TEMPLATES;
        $this->compile_dir = DIR_TEMPLATES_COMPILE;
        $this->cache_dir = DIR_CACHE;

        $this->assign('pagetitle',MSG_MENU_TITLE);
        $this->assign('dir_img',DIR_IMG);
        $this->assign('dir_user',DIR_IMG_USER);
        $this->assign('languagebar',HTML_languageSwitch(getPHP_SELF() . '?' . getSID()));
        $this->assign('menu_link', '360_menu.php?' . getSID());
        $this->assign('userinfo',$_SESSION['login']);
        $this->assign('root', DOMAIN . DIR_MAIN);
        $this->setText();
        parent::__construct();
    }
    public function setText()
    {
        $l = getMyLanguage();
        $this->assign('text',$GLOBALS['GLOBAL_MESSAGE'][$l]);
    }

}
