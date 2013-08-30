<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Description: Advanced Community Engine
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 * Based on
 *   LiveStreet Engine Social Networking by Mzhelskiy Maxim
 *   Site: www.livestreet.ru
 *   E-mail: rus.engine@gmail.com
 *----------------------------------------------------------------------------
 */

/**
 * Сущность пользователя
 *
 * @package modules.user
 * @since   1.0
 */
class ModuleUser_EntityUser extends Entity {

    /**
     * Определяем правила валидации
     * Правила валидации нужно определять только здесь!
     *
     * @var array
     */
    public function Init() {

        parent::Init();
        $this->aValidateRules[] = array(
            'login',
            'login',
            'on' => array('registration', ''), // '' - означает дефолтный сценарий
        );
        $this->aValidateRules[] = array(
            'login',
            'login_exists',
            'on' => array('registration'),
        );
        $this->aValidateRules[] = array(
            'mail',
            'email',
            'allowEmpty' => false,
            'on'         => array('registration', ''),
        );
        $this->aValidateRules[] = array(
            'mail',
            'mail_exists',
            'on' => array('registration'),
        );
        $this->aValidateRules[] = array(
            'password',
            'string',
            'allowEmpty' => false,
            'min'        => 5,
            'on'         => array('registration'),
        );
        $this->aValidateRules[] = array(
            'password_confirm',
            'compare',
            'compareField' => 'password',
            'on'           => array('registration'),
        );

        // Определяем дополнительные правила валидации
        if (Config::Get('module.user.captcha_use_registration')) {
            $this->aValidateRules[] = array('captcha', 'captcha', 'on' => array('registration'));
        }
    }

    /**
     * Определяем дополнительные правила валидации
     *
     * @param   array|null $aParam
     */
    public function __construct($aParam = null) {

        parent::__construct($aParam);
    }

    /**
     * Валидация пользователя
     *
     * @param string $sValue     Валидируемое значение
     * @param array  $aParams    Параметры
     *
     * @return bool
     */
    public function ValidateLogin($sValue, $aParams) {

        if ($sValue && $this->User_CheckLogin($sValue)) {
            return true;
        }
        return $this->Lang_Get('registration_login_error');
    }

    /**
     * Проверка логина на существование
     *
     * @param string $sValue     Валидируемое значение
     * @param array  $aParams    Параметры
     *
     * @return bool
     */
    public function ValidateLoginExists($sValue, $aParams) {

        if (!$this->User_GetUserByLogin($sValue)) {
            return true;
        }
        return $this->Lang_Get('registration_login_error_used');
    }

    /**
     * Проверка емайла на существование
     *
     * @param string $sValue     Валидируемое значение
     * @param array  $aParams    Параметры
     *
     * @return bool
     */
    public function ValidateMailExists($sValue, $aParams) {

        if (!$this->User_GetUserByMail($sValue)) {
            return true;
        }
        return $this->Lang_Get('registration_mail_error_used');
    }

    /**
     * Возвращает ID пользователя
     *
     * @return int|null
     */
    public function getId() {

        return $this->getProp('user_id');
    }

    /**
     * Возвращает логин
     *
     * @return string|null
     */
    public function getLogin() {

        return $this->getProp('user_login');
    }

    /**
     * Возвращает пароль (ввиде хеша)
     *
     * @return string|null
     */
    public function getPassword() {

        return $this->getProp('user_password');
    }

    /**
     * Возвращает емайл
     *
     * @return string|null
     */
    public function getMail() {

        return $this->getProp('user_mail');
    }

    /**
     * Возвращает силу
     *
     * @return string
     */
    public function getSkill() {

        return number_format(round($this->getProp('user_skill'), 2), 2, '.', '');
    }

    /**
     * Возвращает дату регистрации
     *
     * @return string|null
     */
    public function getDateRegister() {

        return $this->getProp('user_date_register');
    }

    /**
     * Возвращает дату активации
     *
     * @return string|null
     */
    public function getDateActivate() {

        return $this->getProp('user_date_activate');
    }

    /**
     * Возвращает дату последнего комментирования
     *
     * @return mixed|null
     */
    public function getDateCommentLast() {

        return $this->getProp('user_date_comment_last');
    }

    /**
     * Возвращает IP регистрации
     *
     * @return string|null
     */
    public function getIpRegister() {

        return $this->getProp('user_ip_register');
    }

    /**
     * Возвращает рейтинг
     *
     * @return string
     */
    public function getRating() {

        return number_format(round($this->getProp('user_rating'), 2), 2, '.', '');
    }

    /**
     * Вовзращает количество проголосовавших
     *
     * @return int|null
     */
    public function getCountVote() {

        return $this->getProp('user_count_vote');
    }

    /**
     * Возвращает статус активированности
     *
     * @return int|null
     */
    public function getActivate() {

        return $this->getProp('user_activate');
    }

    /**
     * Возвращает ключ активации
     *
     * @return string|null
     */
    public function getActivateKey() {

        return $this->getProp('user_activate_key');
    }

    /**
     * Возвращает имя
     *
     * @return string|null
     */
    public function getProfileName() {

        return $this->getProp('user_profile_name');
    }

    /**
     * Возвращает пол
     *
     * @return string|null
     */
    public function getProfileSex() {

        return $this->getProp('user_profile_sex');
    }

    /**
     * Возвращает название страны
     *
     * @return string|null
     */
    public function getProfileCountry() {

        return $this->getProp('user_profile_country');
    }

    /**
     * Возвращает название региона
     *
     * @return string|null
     */
    public function getProfileRegion() {

        return $this->getProp('user_profile_region');
    }

    /**
     * Возвращает название города
     *
     * @return string|null
     */
    public function getProfileCity() {

        return $this->getProp('user_profile_city');
    }

    /**
     * Возвращает дату рождения
     *
     * @return string|null
     */
    public function getProfileBirthday() {

        return $this->getProp('user_profile_birthday');
    }

    /**
     * Возвращает информацию о себе
     *
     * @return string|null
     */
    public function getProfileAbout() {

        return $this->getProp('user_profile_about');
    }

    /**
     * Возвращает дату редактирования профиля
     *
     * @return string|null
     */
    public function getProfileDate() {

        return $this->getProp('user_profile_date');
    }

    /**
     * Возвращает полный веб путь до аватра
     *
     * @return string|null
     */
    public function getProfileAvatar() {

        return $this->getProp('user_profile_avatar');
    }

    /**
     * Возвращает расширение автара
     *
     * @return string|null
     */
    public function getProfileAvatarType() {

        return ($sPath = $this->getProfileAvatarPath()) ? pathinfo($sPath, PATHINFO_EXTENSION) : null;
    }

    /**
     * Возвращает полный веб путь до фото
     *
     * @return string|null
     */
    public function getProfileFoto() {

        return $this->getProp('user_profile_foto');
    }

    /**
     * Возвращает статус уведомления о новых топиках
     *
     * @return int|null
     */
    public function getSettingsNoticeNewTopic() {

        return $this->getProp('user_settings_notice_new_topic');
    }

    /**
     * Возвращает статус уведомления о новых комментариях
     *
     * @return int|null
     */
    public function getSettingsNoticeNewComment() {

        return $this->getProp('user_settings_notice_new_comment');
    }

    /**
     * Возвращает статус уведомления о новых письмах
     *
     * @return int|null
     */
    public function getSettingsNoticeNewTalk() {

        return $this->getProp('user_settings_notice_new_talk');
    }

    /**
     * Возвращает статус уведомления о новых ответах в комментариях
     *
     * @return int|null
     */
    public function getSettingsNoticeReplyComment() {

        return $this->getProp('user_settings_notice_reply_comment');
    }

    /**
     * Возвращает статус уведомления о новых друзьях
     *
     * @return int|null
     */
    public function getSettingsNoticeNewFriend() {

        return $this->getProp('user_settings_notice_new_friend');
    }

    public function getLastSession() {

        return $this->getProp('user_last_session');
    }

    /**
     * Возвращает значения пользовательских полей
     *
     * @param bool   $bOnlyNoEmpty    Возвращать или нет только не пустые
     * @param string $sType           Тип полей
     *
     * @return array
     */
    public function getUserFieldValues($bOnlyNoEmpty = true, $sType = '') {

        return $this->User_getUserFieldsValues($this->getId(), $bOnlyNoEmpty, $sType);
    }

    /**
     * Возвращает объект сессии
     *
     * @return ModuleUser_EntitySession|null
     */
    public function getSession() {

        if (!$this->getProp('session')) {
            $this->_aData['session'] = $this->User_GetSessionByUserId($this->getId());
        }
        return $this->getProp('session');
    }

    /**
     * Возвращает статус онлайн пользователь или нет
     *
     * @return bool
     */
    public function isOnline() {

        if ($oSession = $this->getSession()) {
            if ($oSession->GetSessionExit()) {
                // был выход пользователя
                return false;
            }
            if (time() - strtotime($oSession->getDateLast()) < 60 * 10) {
                // не прошло 10 минут
                return true;
            }
        }
        return false;
    }

    /**
     * Возвращает полный веб путь до аватара нужного размера
     *
     * @param   int $iSize    Размер
     *
     * @return  string
     */
    public function getAvatarUrl($iSize = 100) {

        if ($sPath = $this->getProfileAvatar()) {
            return str_replace(
                '_100x100', (($iSize == 0) ? "" : "_{$iSize}x{$iSize}"),
                $sPath . "?" . date('His', strtotime($this->getProfileDate()))
            );
        } else {
            return Config::Get('path.static.assets') . '/images/avatars/avatar_' . ($this->getProfileSex() == 'woman'
                ? 'female' : 'male') . '_' . $iSize . 'x' . $iSize . '.png';
        }
    }

    /**
     * DEPRECATED
     */
    public function getProfileAvatarPath($iSize = 100) {

        return $this->getAvatarUrl($iSize);
    }

    /**
     * Возвращает полный веб путь до фото
     *
     * @return null|string
     */
    public function getProfileFotoPath() {

        if ($this->getProfileFoto()) {
            return $this->getProfileFoto();
        }
        return $this->getProfileFotoDefault();
    }

    /**
     * Возвращает дефолтную фото
     *
     * @return string
     */
    public function getProfileFotoDefault() {

        return Config::Get('path.static.skin') . '/images/user_photo_' . (
        $this->getProfileSex() == 'woman' ? 'female' : 'male') . '.png';
    }

    /**
     * Возвращает объект голосования за пользователя текущего пользователя
     *
     * @return ModuleVote_EntityVote|null
     */
    public function getVote() {

        return $this->getProp('vote');
    }

    /**
     * Возвращает статус дружбы
     *
     * @return bool|null
     */
    public function getUserIsFriend() {

        return $this->getProp('user_is_friend');
    }

    /**
     * Возвращает статус администратора сайта
     *
     * @return bool|null
     */
    public function isAdministrator() {

        return $this->getProp('user_is_administrator');
    }

    /**
     * Возвращает веб путь до профиля пользователя
     *
     * @return string
     */
    public function getUserWebPath() {

        return $this->getUserUrl();
    }

    /**
     * Возвращает URL до профиля пользователя
     *
     * @return string
     */
    public function getUserUrl() {

        return Router::GetPath('profile') . $this->getLogin() . '/';
    }

    /**
     * Возвращает объект дружбы с текущим пользователем
     *
     * @return ModuleUser_EntityFriend|null
     */
    public function getUserFriend() {

        return $this->getProp('user_friend');
    }

    /**
     * Проверяет подписан ли текущий пользователь на этого
     *
     * @return bool
     */
    public function isFollow() {

        if ($oUserCurrent = $this->User_GetUserCurrent()) {
            return $this->Stream_IsSubscribe($oUserCurrent->getId(), $this->getId());
        }
    }

    /**
     * Возвращает объект заметки о подльзователе, которую оставил текущий пользователй
     *
     * @return ModuleUser_EntityNote|null
     */
    public function getUserNote() {

        $oUserCurrent = $this->User_GetUserCurrent();
        if ($this->getProp('user_note') === null && $oUserCurrent) {
            $this->_aData['user_note'] = $this->User_GetUserNote($this->getId(), $oUserCurrent->getId());
        }
        return $this->getProp('user_note');
    }

    /**
     * Возвращает личный блог пользователя
     *
     * @return ModuleBlog_EntityBlog|null
     */
    public function getBlog() {

        if (!$this->getProp('blog')) {
            $this->_aData['blog'] = $this->Blog_GetPersonalBlogByUserId($this->getId());
        }
        return $this->getProp('blog');
    }

    public function GetBanLine() {

        return $this->GetProp('banline');
    }

    public function IsBannedUnlim() {

        return ((bool)$this->GetProp('banunlim'));
    }

    public function GetBanComment() {

        return $this->GetProp('bancomment');
    }

    public function IsBannedByLogin() {
        $dBanline = $this->getBanLine();
        return ($this->IsBannedUnlim()
            || ($dBanline && ($dBanline > date('Y-m-d H:i:s')) && $this->GetProp('banactive')));
    }

    public function IsBannedByIp() {

        return ($this->GetProp('ban_ip'));
    }

    public function IsBanned() {

        return ($this->IsBannedByLogin() || $this->IsBannedByIp());
    }


    /**
     * Устанавливает ID пользователя
     *
     * @param int $data
     */
    public function setId($data) {

        $this->setProp('user_id', $data);
    }

    /**
     * Устанавливает логин
     *
     * @param string $data
     */
    public function setLogin($data) {

        $this->setProp('user_login', $data);
    }

    /**
     * Устанавливает пароль
     *
     * @param   string $sPassword
     * @param   bool   $bEncrypt   - false, если пароль уже захеширован
     */
    public function setPassword($sPassword, $bEncrypt = false) {

        if ($bEncrypt) {
            $this->_aData['user_password'] = $this->Security_Salted($sPassword, 'pass');
        } else {
            $this->setProp('user_password', $sPassword);
        }
    }

    /**
     * Устанавливает емайл
     *
     * @param string $data
     */
    public function setMail($data) {

        $this->setProp('user_mail', $data);
    }

    /**
     * Устанавливает силу
     *
     * @param float $data
     */
    public function setSkill($data) {

        $this->setProp('user_skill', $data);
    }

    /**
     * Устанавливает дату регистрации
     *
     * @param string $data
     */
    public function setDateRegister($data) {

        $this->setProp('user_date_register', $data);
    }

    /**
     * Устанавливает дату активации
     *
     * @param string $data
     */
    public function setDateActivate($data) {

        $this->setProp('user_date_activate', $data);
    }

    /**
     * Устанавливает дату последнего комментирования
     *
     * @param string $data
     */
    public function setDateCommentLast($data) {

        $this->setProp('user_date_comment_last', $data);
    }

    /**
     * Устанавливает IP регистрации
     *
     * @param string $data
     */
    public function setIpRegister($data) {

        $this->setProp('user_ip_register', $data);
    }

    /**
     * Устанавливает рейтинг
     *
     * @param float $data
     */
    public function setRating($data) {

        $this->setProp('user_rating', $data);
    }

    /**
     * Устанавливает количество проголосовавших
     *
     * @param int $data
     */
    public function setCountVote($data) {

        $this->setProp('user_count_vote', $data);
    }

    /**
     * Устанавливает статус активированности
     *
     * @param int $data
     */
    public function setActivate($data) {

        $this->setProp('user_activate', $data);
    }

    /**
     * Устанавливает ключ активации
     *
     * @param string $data
     */
    public function setActivateKey($data) {

        $this->setProp('user_activate_key', $data);
    }

    /**
     * Устанавливает имя
     *
     * @param string $data
     */
    public function setProfileName($data) {

        $this->setProp('user_profile_name', $data);
    }

    /**
     * Устанавливает пол
     *
     * @param string $data
     */
    public function setProfileSex($data) {

        $this->setProp('user_profile_sex', $data);
    }

    /**
     * Устанавливает название страны
     *
     * @param string $data
     */
    public function setProfileCountry($data) {

        $this->setProp('user_profile_country', $data);
    }

    /**
     * Устанавливает название региона
     *
     * @param string $data
     */
    public function setProfileRegion($data) {

        $this->setProp('user_profile_region', $data);
    }

    /**
     * Устанавливает название города
     *
     * @param string $data
     */
    public function setProfileCity($data) {

        $this->setProp('user_profile_city', $data);
    }

    /**
     * Устанавливает дату рождения
     *
     * @param string $data
     */
    public function setProfileBirthday($data) {

        $this->setProp('user_profile_birthday', $data);
    }

    /**
     * Устанавливает информацию о себе
     *
     * @param string $data
     */
    public function setProfileAbout($data) {

        $this->setProp('user_profile_about', $data);
    }

    /**
     * Устанавливает дату редактирования профиля
     *
     * @param string $data
     */
    public function setProfileDate($data) {

        $this->setProp('user_profile_date', $data);
    }

    /**
     * Устанавливает полный веб путь до аватра
     *
     * @param string $data
     */
    public function setProfileAvatar($data) {

        $this->setProp('user_profile_avatar', $data);
    }

    /**
     * Устанавливает полный веб путь до фото
     *
     * @param string $data
     */
    public function setProfileFoto($data) {

        $this->setProp('user_profile_foto', $data);
    }

    /**
     * Устанавливает статус уведомления о новых топиках
     *
     * @param int $data
     */
    public function setSettingsNoticeNewTopic($data) {

        $this->setProp('user_settings_notice_new_topic', $data);
    }

    /**
     * Устанавливает статус уведомления о новых комментариях
     *
     * @param int $data
     */
    public function setSettingsNoticeNewComment($data) {

        $this->setProp('user_settings_notice_new_comment', $data);
    }

    /**
     * Устанавливает статус уведомления о новых письмах
     *
     * @param int $data
     */
    public function setSettingsNoticeNewTalk($data) {

        $this->setProp('user_settings_notice_new_talk', $data);
    }

    /**
     * Устанавливает статус уведомления о новых ответах в комментариях
     *
     * @param int $data
     */
    public function setSettingsNoticeReplyComment($data) {

        $this->setProp('user_settings_notice_reply_comment', $data);
    }

    /**
     * Устанавливает статус уведомления о новых друзьях
     *
     * @param int $data
     */
    public function setSettingsNoticeNewFriend($data) {

        $this->setProp('user_settings_notice_new_friend', $data);
    }

    /**
     * Устанавливает объект сессии
     *
     * @param ModuleUser_EntitySession $data
     */
    public function setSession($data) {

        $this->setProp('session', $data);
    }

    /**
     * Устанавливает статус дружбы
     *
     * @param int $data
     */
    public function setUserIsFriend($data) {

        $this->setProp('user_is_friend', $data);
    }

    /**
     * Устанавливает объект голосования за пользователя текущего пользователя
     *
     * @param ModuleVote_EntityVote $data
     */
    public function setVote($data) {

        $this->setProp('vote', $data);
    }

    /**
     * Устанавливаем статус дружбы с текущим пользователем
     *
     * @param int $data
     */
    public function setUserFriend($data) {

        $this->setProp('user_friend', $data);
    }

    public function setLastSession($data) {

        $this->setProp('user_last_session', $data);
    }

}

// EOF