<?php
/**
 * 基本設定の用途受けラッパー。主な目的はフラグの隠蔽化
 */
class _360_Setting
{
    public function sessionModeCookie()
    {
        if (defined('SESSION_MODE') && SESSION_MODE == 1 && !isset($_GET['PROXYSESSID']) && !isset($_POST['PROXYSESSID'])) {
            return true;
        }

        return false;
    }

    public function htmlMail()
    {
        if (defined("MAIL_HTMLMAIL") && MAIL_HTMLMAIL == 2) {
            return true;
        }

        return false;
    }

    public function csvTabDelimiter()
    {
        if (defined("OUTPUT_CSV_DELIMITER_") && OUTPUT_CSV_DELIMITER_ == "タブ") {
            return true;
        }

        return false;
    }

    public function csvEncodeUtf16le()
    {
        if (defined("OUTPUT_CSV_ENCODE") && OUTPUT_CSV_ENCODE == "UTF-16LE") {
            return true;
        }

        return false;
    }

    public function csvEncodeUtf8()
    {
        if (defined("OUTPUT_CSV_ENCODE") && OUTPUT_CSV_ENCODE == "UTF-8") {
            return true;
        }

        return false;
    }

    public function hideTest()
    {
        if (defined("TEST_") && ereg("^0", TEST_)) {
            return true;
        }

        return false;
    }

    public function mailEncodeJis()
    {
        if (defined("MAIL_ENCODE") && MAIL_ENCODE == "JIS") {
            return true;
        }

        return false;
    }

    public function limitPwEqual($value)
    {
        if (defined("LIMIT_PW_MISS") && $value == LIMIT_PW_MISS) {
            return true;
        }

        return false;
    }

    public function limitPwLess($value)
    {
        if (defined("LIMIT_PW_MISS") && $value > LIMIT_PW_MISS) {
            return true;
        }

        return false;
    }

    public function limitPwLessOrEqual($value)
    {
        if (defined("LIMIT_PW_MISS") && $value >= LIMIT_PW_MISS) {
            return true;
        }

        return false;
    }

    public function autoSaveValid()
    {
        if (defined("USE_AUTO_SAVE") && USE_AUTO_SAVE == 2) {
            return true;
        }

        return false;
    }

    public function fileSizeLess($value)
    {
        if (defined("MAIL_FILE_SUM_SIZE") && $value > MAIL_FILE_SUM_SIZE * 1024) {
            return true;
        }

        return false;
    }

    public function dirImgNotEmpty()
    {
        if (defined("OUTER_DIR_IMG_USER") && OUTER_DIR_IMG_USER != "") {
            return true;
        }

        return false;
    }

    public function pwLengthGreater($value)
    {
        if (defined("DEFAULT_PW_LENGTH") && strlen($value) < DEFAULT_PW_LENGTH) {
            return true;
        }

        return false;
    }

    public function Mfid2IsNot0()
    {
        if (defined("MFID_2") && MFID_2 != 0) {
            return true;
        }

        return false;
    }

    public function Mfid5IsNot0()
    {
        if (defined("MFID_5") && !is_zero(MFID_5)) {
            return true;
        }

        return false;
    }

    public function Mfid6Is0()
    {
        if (defined("MFID_6") && is_zero(MFID_6)) {
            return true;
        }

        return false;
    }

    public function bossNumWarningGreater()
    {
        if (defined("REPLY_BOSS_NUM_WARNING") && $GLOBALS['RELATION_EDIT_COUNT'][1] < REPLY_BOSS_NUM_WARNING) {
            return true;
        }

        return false;
    }

    public function bossNumAttentionGreater()
    {
        if (defined("REPLY_BOSS_NUM_ATTENTION") && $GLOBALS['RELATION_EDIT_COUNT'][1] < REPLY_BOSS_NUM_ATTENTION) {
            return true;
        }

        return false;
    }

    public function memberNumWarningGreater()
    {
        if (defined("REPLY_MEMBER_NUM_WARNING") && $GLOBALS['RELATION_EDIT_COUNT'][2] < REPLY_MEMBER_NUM_WARNING) {
            return true;
        }

        return false;
    }

    public function memberNumAttentionGreater()
    {
        if (defined("REPLY_MEMBER_NUM_ATTENTION") && $GLOBALS['RELATION_EDIT_COUNT'][2] < REPLY_MEMBER_NUM_ATTENTION) {
            return true;
        }

        return false;
    }

    public function coworkerNumWarningGreater()
    {
        if (defined("REPLY_COWORKER_NUM_WARNING") && $GLOBALS['RELATION_EDIT_COUNT'][3] < REPLY_COWORKER_NUM_WARNING) {
            return true;
        }

        return false;
    }

    public function coworkerNumAttentionGreater()
    {
        if (defined("REPLY_COWORKER_NUM_ATTENTION") && $GLOBALS['RELATION_EDIT_COUNT'][3] < REPLY_COWORKER_NUM_ATTENTION) {
            return true;
        }

        return false;
    }

    public function allNumWarningGreater($value)
    {
        if (defined("REPLY_ALL_NUM_WARNING") && $value < REPLY_ALL_NUM_WARNING) {
            return true;
        }

        return false;
    }

    public function allNumAttentionGreater($value)
    {
        if (defined("REPLY_ALL_NUM_ATTENTION") && $value < REPLY_ALL_NUM_ATTENTION) {
            return true;
        }

        return false;
    }

    public function menuModeIs1()
    {
        if (defined("MYPAGE_MENU_MODE") && MYPAGE_MENU_MODE == 1) {
            return true;
        }

        return false;
    }

    public function menuModeIsNot1()
    {
        if (defined("MYPAGE_MENU_MODE") && MYPAGE_MENU_MODE != 1) {
            return true;
        }

        return false;
    }

    public function reopenTypeEqual($value)
    {
        if (defined("MYPAGE_REOPEN_TYPE") && MYPAGE_REOPEN_TYPE == $value) {
            return true;
        }

        return false;
    }

    public function reopenTypeNotEqual($value)
    {
        if (defined("MYPAGE_REOPEN_TYPE") && MYPAGE_REOPEN_TYPE != $value) {
            return true;
        }

        return false;
    }

    public function sheetModeCollect()
    {
        if (defined("SHEET_MODE") && SHEET_MODE == 1) {
            return true;
        }

        return false;
    }

    public function sheetModeNotCollect()
    {
        if (defined("SHEET_MODE") && SHEET_MODE != 1) {
            return true;
        }

        return false;
    }

    public function adminModeEqual($value)
    {
        if (defined("ADMIN_MODE") && ADMIN_MODE == $value) {
            return true;
        }

        return false;
    }

    public function adminModeNotEqual($value)
    {
        if (defined("ADMIN_MODE") && ADMIN_MODE != $value) {
            return true;
        }

        return false;
    }

    public function multiAnswerModeValid()
    {
        if (defined("MULTI_ANSWER_MODE") && MULTI_ANSWER_MODE == 1) {
            return true;
        }

        return false;
    }

    public function multiAnswerModeInvalid()
    {
        if (defined("MULTI_ANSWER_MODE") && MULTI_ANSWER_MODE != 1) {
            return true;
        }

        return false;
    }

    public function nameModeIs1()
    {
        if (defined("TARGET_NAME_MODE") && TARGET_NAME_MODE == 1) {
            return true;
        }

        return false;
    }

    public function headerModePulldown()
    {
        if (defined("MATRIX_HEADER_MODE") && MATRIX_HEADER_MODE == 1) {
            return true;
        }

        return false;
    }

    public function useProgressDisplay()
    {
        if (defined("USE_PROGRESS") && USE_PROGRESS == 1) {
            return true;
        }

        return false;
    }

    public function buttonPositionUpper()
    {
        if (defined("SAVE_BUTTON_POSITION") && in_array(SAVE_BUTTON_POSITION, array(1, 3))) {
            return true;
        }

        return false;
    }

    public function buttonPositionLower()
    {
        if (defined("SAVE_BUTTON_POSITION") && in_array(SAVE_BUTTON_POSITION, array(2, 3))) {
            return true;
        }

        return false;
    }

    public function useNewsDisplay()
    {
        if (defined("USE_NEWS") && USE_NEWS == 2) {
            return true;
        }

        return false;
    }

    public function mailFlagValid()
    {
        if (defined("COMP_MAIL_FLAG") && COMP_MAIL_FLAG == 1) {
            return true;
        }

        return false;
    }

    public function loginLogInvalid()
    {
        if (defined("WRITE_USER_LOGIN_LOG") && WRITE_USER_LOGIN_LOG != 1) {
            return true;
        }

        return false;
    }

    public function adjustValueLessOrEqual($value)
    {
        if (defined("RESULT_TOTAL_2_BAR_GRAPH_ADJUST_VALUE") && $value >= RESULT_TOTAL_2_BAR_GRAPH_ADJUST_VALUE) {
            return true;
        }

        return false;
    }

    public function commentCountValid()
    {
        if (defined("USE_COMMENT_COUNT") && USE_COMMENT_COUNT == 1) {
            return true;
        }

        return false;
    }

    public function commentMaxLengthNotEmpty()
    {
        if (defined("COMMENT_MAX_LENGTH") && COMMENT_MAX_LENGTH != "") {
            return true;
        }

        return false;
    }

    public function reversiblePw()
    {
        if (defined("PW_ENCRYPTION") && PW_ENCRYPTION==2) {
            return true;
        }

        return false;
    }
    public function limitUserNumberValid()
    {
        if (defined("LIMIT_USER_NUMBER") && LIMIT_USER_NUMBER >= 1) {
            return true;
        }

        return false;
    }

    public function randComments()
    {
        if (defined("RAND_COMMENTS") && RAND_COMMENTS==2) {
            return true;
        }

        return false;
    }

}
