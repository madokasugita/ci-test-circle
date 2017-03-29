<?php
/**
 * Sessionに関する処理
 */
class _360_AuthSession
{
    public function setCookieParams()
    {
        if ($GLOBALS['Setting']->sessionModeCookie()) {
            session_set_cookie_params(0, DIR_MAIN, "", (SSL_ON==1), true);
            ini_set('session.use_only_cookies', 1);
        } else {
            ini_set('session.use_only_cookies', 0);
        }

        return;
    }

    public function setProxySessionName()
    {
        if (isset($_GET['PROXYSESSID']) || isset($_POST['PROXYSESSID'])) {
            session_name("PROXYSESSID");
        }

        return;
    }

    public function switchToGetSession()
    {
        if ($GLOBALS['Setting']->sessionModeCookie()) {
            ini_set('session.use_cookies', 0);
            ini_set('session.use_only_cookies', 0);
        }

        return;
    }

    public function sessionRestart()
    {
        session_start();
        $this->sessionReset();
        session_start();
        session_regenerate_id(true);

        return;
    }

    public function sessionReset()
    {
        $_SESSION = array();
        if ($GLOBALS['Setting']->sessionModeCookie()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), "", time()-2592000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_id()) {
            session_destroy();
        }
        return;
    }
}
