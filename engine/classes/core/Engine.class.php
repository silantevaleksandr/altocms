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

F::IncludeFile('../abstract/Plugin.class.php');
F::IncludeFile('../abstract/Hook.class.php');
F::IncludeFile('../abstract/Module.class.php');
F::IncludeFile('Router.class.php');

F::IncludeFile('../abstract/Entity.class.php');
F::IncludeFile('../abstract/Mapper.class.php');

F::IncludeFile('../abstract/ModuleORM.class.php');
F::IncludeFile('../abstract/EntityORM.class.php');
F::IncludeFile('../abstract/MapperORM.class.php');

F::IncludeFile('ManyToManyRelation.class.php');


/**
 * Основной класс движка. Ядро.
 *
 * Производит инициализацию плагинов, модулей, хуков.
 * Через этот класс происходит выполнение методов всех модулей, которые вызываются как <pre>$this->Module_Method();</pre>
 * Также отвечает за автозагрузку остальных классов движка.
 *
 * В произвольном месте (не в классах движка у которых нет обработки метода __call() на выполнение модулей) метод модуля можно вызвать так:
 * <pre>
 * Engine::getInstance()->Module_Method();
 * </pre>
 *
 * @package engine
 * @since 1.0
 */
class Engine extends LsObject {

    /**
     * Имя плагина
     * @var int
     */
    const CI_PLUGIN = 1;

    /**
     * Имя экшна
     * @var int
     */
    const CI_ACTION = 2;

    /**
     * Имя модуля
     * @var int
     */
    const CI_MODULE = 4;

    /**
     * Имя сущности
     * @var int
     */
    const CI_ENTITY = 8;

    /**
     * Имя маппера
     * @var int
     */
    const CI_MAPPER = 16;

    /**
     * Имя метода
     * @var int
     */
    const CI_METHOD = 32;

    /**
     * Имя хука
     * @var int
     */
    const CI_HOOK = 64;

    /**
     * Имя класс наследования
     * @var int
     */
    const CI_INHERIT = 128;

    /**
     * Имя блока
     * @var int
     */
    const CI_BLOCK = 256;

    /**
     * Имя виджета
     * @var int
     */
    const CI_WIDGET = 512;

    /**
     * Префикс плагина
     * @var int
     */
    const CI_PPREFIX = 8192;

    /**
     * Разобранный класс наследования
     * @var int
     */
    const CI_INHERITS = 16384;

    /**
     * Путь к файлу класса
     * @var int
     */
    const CI_CLASSPATH = 32768;

    /**
     * Все свойства класса
     * @var int
     */
    const CI_ALL = 65535;

    /**
     * Свойства по-умолчанию
     * CI_ALL ^ (CI_CLASSPATH | CI_INHERITS | CI_PPREFIX)
     * @var int
     */
    const CI_DEFAULT = 8191;

    /**
     * Объекты
     * CI_ACTION | CI_MAPPER | CI_HOOK | CI_PLUGIN | CI_ACTION | CI_MODULE | CI_ENTITY | CI_BLOCK | CI_WIDGET
     * @var int
     */
    const CI_OBJECT = 863;

    const STAGE_INIT = 1;
    const STAGE_RUN = 2;
    const STAGE_SHUDOWN = 3;
    const STAGE_DONE = 4;

    /** @var int - Stage of Engine */
    static protected $nStage = 0;

    /**
     * Текущий экземпляр движка, используется для синглтона.
     * @see getInstance использование синглтона
     *
     * @var Engine
     */
    static protected $oInstance = null;

    /**
     * Internal cache of resolved class names
     *
     * @var array
     */
    static protected $aClasses = array();

    /**
     * Internal cache for info of used classes
     *
     * @var array
     */
    static protected $aClassesInfo = array();

    /**
     * Список загруженных модулей
     *
     * @var array
     */
    protected $aModules = array();

    /**
     * Map of relations Name => Class
     *
     * @var array
     */
    protected $aModulesMap = array();

    /**
     * Список загруженных плагинов
     *
     * @var array
     */
    protected $aPlugins = array();

    /**
     * Содержит конфиг модулей.
     * Используется для получания списка модулей для авто-загрузки. Остальные модули загружаются при первом обращении.
     * В конфиге определен так:
     * <pre>
     * $config['module']['_autoLoad_'] = array('Hook','Cache','Security','Session','Lang','Message','User');
     * </pre>
     *
     * @var array
     */
    protected $aConfigModule;
    /**
     * Время загрузки модулей в микросекундах
     *
     * @var int
     */
    public $nTimeLoadModule = 0;
    /**
     * Текущее время в микросекундах на момент инициализации ядра(движка).
     * Определается так:
     * <pre>
     * $this->iTimeInit=microtime(true);
     * </pre>
     *
     * @var int|null
     */
    protected $nTimeInit = null;


    /**
     * Вызывается при создании объекта ядра.
     * Устанавливает время старта инициализации и обрабатывает входные параметры PHP
     *
     */
    public function __construct() {

        $this->nTimeInit = microtime(true);
    }

    /**
     * Ограничиваем объект только одним экземпляром.
     * Функционал синглтона.
     *
     * Используется так:
     * <pre>
     * Engine::getInstance()->Module_Method();
     * </pre>
     *
     * @return Engine
     */
    static public function getInstance() {

        if (isset(static::$oInstance) && (static::$oInstance instanceof self)) {
            return static::$oInstance;
        } else {
            static::$oInstance = new static();
            return static::$oInstance;
        }
    }

    /**
     * Инициализация ядра движка
     *
     */
    public function Init() {

        if (self::$nStage >= self::STAGE_RUN) return;

        self::$nStage = self::STAGE_INIT;
        /**
         * Загружаем плагины
         */
        $this->LoadPlugins();
        /**
         * Инициализируем хуки
         */
        $this->InitHooks();
        /**
         * Загружаем модули автозагрузки
         */
        $this->LoadModules();
        /**
         * Инициализируем загруженные модули
         */
        $this->InitModules();
        /**
         * Инициализируем загруженные плагины
         */
        $this->InitPlugins();
        /**
         * Запускаем хуки для события завершения инициализации Engine
         */
        //$this->Hook_Run('engine_init_complete');
        $aArgs = array('engine_init_complete');
        $this->_CallModule('Hook_Run', $aArgs);
        self::$nStage = self::STAGE_RUN;
    }

    /**
     * Завершение работы движка
     * Завершает все модули.
     *
     */
    public function Shutdown() {

        if (self::$nStage < self::STAGE_SHUDOWN) {
            self::$nStage = self::STAGE_SHUDOWN;
            $this->ShutdownModules();
            self::$nStage = self::STAGE_DONE;
        }
    }

    static public function GetStage() {

        return self::$nStage;
    }

    /**
     * Производит инициализацию всех модулей
     *
     */
    protected function InitModules() {

        /** @var Module $oModule */
        foreach ($this->aModules as $oModule) {
            if (!$oModule->isInit()) {
                $this->InitModule($oModule);
            }
        }
    }

    /**
     * Инициализирует модуль
     *
     * @param Module $oModule     - Объект модуля
     * @param bool   $bHookParent - Вызывает хук на родительском модуле, от которого наследуется текущий
     *
     * @throws Exception
     */
    protected function InitModule($oModule, $bHookParent = true) {

        $sClassName = get_class($oModule);
        $bRunHooks = false;

        if ($this->isInitModule('ModuleHook')) {
            $bRunHooks = true;
            if ($bHookParent) {
                while (static::GetPluginName($sClassName)) {
                    $sParentClassName = get_parent_class($sClassName);
                    if (!static::GetClassInfo($sParentClassName, self::CI_MODULE, true)) {
                        break;
                    }
                    $sClassName = $sParentClassName;
                }
            }
        }
        if ($bRunHooks || $sClassName == 'ModuleHook') {
            $sHookPrefix = 'module_';
            if ($sPluginName = static::GetPluginName($sClassName)) {
                $sHookPrefix .= "plugin{$sPluginName}_";
            }
            $sHookPrefix .= static::GetModuleName($sClassName) . '_init_';
        }
        if ($bRunHooks) {
            //$this->Hook_Run($sHookPrefix . 'before');
            $aArgs = array($sHookPrefix . 'before');
            $this->_CallModule('Hook_Run', $aArgs);
        }
        if ($oModule->InInitProgress()) {
            // Нельзя запускать инициализацию модуля в процессе его инициализации
            throw new Exception('Recursive initialization of module "' . get_class($oModule) . '"');
        }
        $oModule->SetInit(true);
        $oModule->Init();
        $oModule->SetInit();
        if ($bRunHooks || $sClassName == 'ModuleHook') {
            //$this->Hook_Run($sHookPrefix . 'after');
            $aArgs = array($sHookPrefix . 'after');
            $this->_CallModule('Hook_Run', $aArgs);
        }
    }

    /**
     * Проверяет модуль на инициализацию
     *
     * @param string $sModuleClass    Класс модуля
     * @return bool
     */
    public function isInitModule($sModuleClass) {

        if ($sModuleClass !== 'ModulePlugin' && $sModuleClass !== 'ModuleHook') {
            //$sModuleClass = $this->Plugin_GetDelegate('module', $sModuleClass);
            $aArgs = array('module', $sModuleClass);
            $sModuleClass = $this->_CallModule('Plugin_GetDelegate', $aArgs);
        }
        if (isset($this->aModules[$sModuleClass]) && $this->aModules[$sModuleClass]->isInit()) {
            return true;
        }
        return false;
    }

    /**
     * Завершаем работу всех модулей
     *
     */
    protected function ShutdownModules() {

        $aModules = $this->aModules;
        array_reverse($aModules);
        // Сначала shutdown модулей, загруженных в процессе работы
        /** @var Module $oModule */
        foreach ($aModules as $oModule) {
            if (!$oModule->GetPreloaded()) {
                $this->ShutdownModule($oModule);
            }
        }
        // Затем предзагруженные модули
        foreach ($aModules as $oModule) {
            if ($oModule->GetPreloaded()) {
                $this->ShutdownModule($oModule);
            }
        }
    }

    /**
     * @param Module $oModule
     *
     * @throws Exception
     */
    protected function ShutdownModule($oModule) {

        if ($oModule->InShudownProgress()) {
            // Нельзя запускать shutdown модуля в процессе его shutdown`a
            throw new Exception('Recursive shutdown of module "' . get_class($oModule) . '"');
        }
        $oModule->SetDone(false);
        $oModule->Shutdown();
        $oModule->SetDone();
    }

    /**
     * Выполняет загрузку модуля по его названию
     *
     * @param  string $sModuleClass    Класс модуля
     * @param  bool $bInit Инициализировать модуль или нет
     *
     * @throws RuntimeException если класс $sModuleClass не существует
     *
     * @return Module
     */
    public function LoadModule($sModuleClass, $bInit = false) {

        $tm1 = microtime(true);

        if (!class_exists($sModuleClass)) {
            throw new RuntimeException(sprintf('Class "%s" not found!', $sModuleClass));
        }

        // * Создаем объект модуля
        $oModule = new $sModuleClass($this);
        $this->aModules[$sModuleClass] = $oModule;
        if ($bInit || $sModuleClass == 'ModuleCache') {
            $this->InitModule($oModule);
        }
        $tm2 = microtime(true);
        $this->nTimeLoadModule += $tm2 - $tm1;

        return $oModule;
    }

    /**
     * Загружает модули из авто-загрузки
     *
     */
    protected function LoadModules() {

        $this->LoadConfig();
        foreach ($this->aConfigModule['_autoLoad_'] as $sModuleName) {
            $sModuleClass = 'Module' . $sModuleName;
            if ($sModuleName !== 'Plugin' && $sModuleName !== 'Hook') {
                //$sModuleClass = $this->Plugin_GetDelegate('module', $sModuleClass);
                $aArgs = array('module', $sModuleClass);
                $sModuleClass = $this->_CallModule('Plugin_GetDelegate', $aArgs);
            }

            if (!isset($this->aModules[$sModuleClass])) {
                $this->LoadModule($sModuleClass);
                if (isset($this->aModules[$sModuleClass])) {
                    // Устанавливаем для модуля признак предзагрузки
                    $this->aModules[$sModuleClass]->SetPreloaded(true);
                }
            }
        }
    }

    public function GetLoadedModules() {

        return $this->aModules;
    }

    /**
     * Выполняет загрузку конфигов
     *
     */
    protected function LoadConfig() {

        $this->aConfigModule = Config::Get('module');
    }

    /**
     * Регистрирует хуки из /classes/hooks/
     *
     */
    protected function InitHooks() {

        $aPathSeek = array_reverse(Config::Get('path.root.seek'));
        $aHookFiles = array();
        foreach ($aPathSeek as $sDirHooks) {
            $aFiles = glob($sDirHooks . '/classes/hooks/Hook*.class.php');
            if ($aFiles) {
                foreach ($aFiles as $sFile) {
                    $aHookFiles[basename($sFile)] = $sFile;
                }
            }
        }

        if ($aHookFiles) {
            foreach ($aHookFiles as $sFile) {
                if (preg_match('/Hook([^_]+)\.class\.php$/i', basename($sFile), $aMatch)) {
                    $sClassName = 'Hook' . $aMatch[1];
                    /** @var Hook $oHook */
                    $oHook = new $sClassName;
                    $oHook->RegisterHook();
                }
            }
        }

        // * Подгружаем хуки активных плагинов
        $this->InitPluginHooks();
    }

    /**
     * Инициализация хуков активированных плагинов
     *
     */
    protected function InitPluginHooks() {

        if ($aPluginList = F::GetPluginsList()) {
            $sPluginsDir = F::GetPluginsDir();

            foreach ($aPluginList as $sPluginName) {
                $aFiles = glob($sPluginsDir . $sPluginName . '/classes/hooks/Hook*.class.php');
                if ($aFiles && count($aFiles)) {
                    foreach ($aFiles as $sFile) {
                        if (preg_match('/Hook([^_]+)\.class\.php$/i', basename($sFile), $aMatch)) {
                            //require_once($sFile);
                            $sPluginName = F::StrCamelize($sPluginName);
                            $sClassName = "Plugin{$sPluginName}_Hook{$aMatch[1]}";
                            /** @var Hook $oHook */
                            $oHook = new $sClassName;
                            $oHook->RegisterHook();
                        }
                    }
                }
            }
        }
    }

    /**
     * Загрузка плагинов и делегирование
     *
     */
    protected function LoadPlugins() {

        if ($aPluginList = F::GetPluginsList()) {
            foreach ($aPluginList as $sPluginName) {
                $sClassName = 'Plugin' . F::StrCamelize($sPluginName);
                /** @var Plugin $oPlugin */
                $oPlugin = new $sClassName;
                $oPlugin->Delegate();
                $this->aPlugins[$sPluginName] = $oPlugin;
            }
        }
    }

    /**
     * Инициализация активированных(загруженных) плагинов
     *
     */
    protected function InitPlugins() {

        /** @var Plugin $oPlugin */
        foreach ($this->aPlugins as $oPlugin) {
            $oPlugin->Init();
        }
    }

    /**
     * Возвращает список активных плагинов
     *
     * @return array
     */
    public function GetPlugins() {

        return $this->aPlugins;
    }

    /**
     * Вызывает метод нужного модуля
     *
     * @param string $sName    Название метода в полном виде.
     * Например <pre>Module_Method</pre>
     * @param array $aArgs    Список аргументов
     * @return mixed
     */
    public function _CallModule($sName, &$aArgs) {

        list($oModule, $sModuleName, $sMethod) = $this->GetModuleMethod($sName);

        if ($sModuleName !== 'Plugin' && $sModuleName !== 'Hook') {
            $sHookName = 'module_' . strtolower($sModuleName . '_' . $sMethod);
        } else {
            $sHookName = '';
        }

        $aResultHook = array();
        if ($sHookName) {
            $aHookArgs = array($sHookName . '_before', &$aArgs);
            list($oHookModule, $sHookModuleName, $sHookMethod) = $this->GetModuleMethod('Hook_Run');
            $aResultHook = call_user_func_array(array($oHookModule, $sHookMethod), $aHookArgs);
        }

        /**
         * Хук может делегировать результат выполнения метода модуля,
         * сам метод при этом не выполняется, происходит только подмена результата
         */
        if ($aResultHook && array_key_exists('delegate_result', $aResultHook)) {
            $xResult = $aResultHook['delegate_result'];
        } else {
            $aArgsRef = array();
            foreach ($aArgs as $iKey => $xVal) {
                $aArgsRef[] =& $aArgs[$iKey];
            }
            $xResult = call_user_func_array(array($oModule, $sMethod), $aArgsRef);
        }

        if ($sHookName) {
            $aHookParams = array('result' => &$xResult, 'params' => &$aArgs);
            $aHookArgs = array($sHookName . '_after', &$aHookParams);
            call_user_func_array(array($oHookModule, $sHookMethod), $aHookArgs);
        }

        return $xResult;
    }

    /**
     * Возвращает объект модуля, имя модуля и имя вызванного метода
     *
     * @param $sCallName - Имя метода модуля в полном виде
     * Например <pre>Module_Method</pre>
     *
     * @return array
     * @throws Exception
     */
    public function GetModuleMethod($sCallName) {

        if (isset($this->aModulesMap[$sCallName])) {
            list($sModuleClass, $sModuleName, $sMethod) = $this->aModulesMap[$sCallName];
        } else {
            $sName = $sCallName;
            if (strpos($sCallName, 'Module') !== false || substr_count($sCallName, '_') > 1) {
                // * Поддержка полного синтаксиса при вызове метода модуля
                $aInfo = static::GetClassInfo($sName, self::CI_MODULE | self::CI_PPREFIX | self::CI_METHOD);
                if ($aInfo[self::CI_MODULE] && $aInfo[self::CI_METHOD]) {
                    $sName = $aInfo[self::CI_MODULE] . '_' . $aInfo[self::CI_METHOD];
                    if ($aInfo[self::CI_PPREFIX]) {
                        $sName = $aInfo[self::CI_PPREFIX] . $sName;
                    }
                }
            }

            $aName = explode('_', $sName);

            switch (count($aName)) {
                case 1:
                    $sModuleName = $sName;
                    $sModuleClass = 'Module' . $sName;
                    $sMethod = null;
                    break;
                case 2:
                    $sModuleName = $aName[0];
                    $sModuleClass = 'Module' . $aName[0];
                    $sMethod = $aName[1];
                    break;
                case 3:
                    $sModuleName = $aName[0] . '_' . $aName[1];
                    $sModuleClass = $aName[0] . '_Module' . $aName[1];
                    $sMethod = $aName[2];
                    break;
                default:
                    throw new Exception('Undefined method module: ' . $sName);
            }

            // * Получаем делегат модуля (в случае наличия такового)
            if ($sModuleName !== 'Plugin' && $sModuleName !== 'Hook') {
                //$sModuleClass = $this->Plugin_GetDelegate('module', $sModuleClass);
                $aArgs = array('module', $sModuleClass);
                $sModuleClass = $this->_CallModule('Plugin_GetDelegate', $aArgs);
            }
            $this->aModulesMap[$sCallName] = array($sModuleClass, $sModuleName, $sMethod);
        }

        if (isset($this->aModules[$sModuleClass])) {
            $oModule = $this->aModules[$sModuleClass];
        } else {
            $oModule = $this->LoadModule($sModuleClass, true);
        }

        return array($oModule, $sModuleName, $sMethod);
    }

    /**
     * Возвращает объект модуля
     *
     * @param string $sModuleName Имя модуля
     *
     * @return object|null
     * @throws Exception
     */
    public function GetModule($sModuleName) {

        // $sCallName === 'User' or $sCallName === 'ModuleUser'
        if (substr($sModuleName, 0, 6) == 'Module' && preg_match('/^(Module)?([A-Z].*)$/', $sModuleName, $aMatches)) {
            $sModuleName = $aMatches[2];
        }
        if ($sModuleName) {
            $aData = $this->GetModuleMethod($sModuleName);

            return $aData[0];
        }
        return null;
    }

    /**
     * Возвращает статистику выполнения
     *
     * @return array
     */
    public function getStats() {
        /**
         * Подсчитываем время выполнения
         */
        $nTimeInit = $this->GetTimeInit();
        $nTimeFull = microtime(true) - $nTimeInit;
        if (isset($_SERVER['REQUEST_TIME'])) {
            $nExecTime = round(microtime(true) - $_SERVER['REQUEST_TIME'], 3);
        } else {
            $nExecTime = 0;
        }
        return array(
            'sql' => $this->Database_GetStats(),
            'cache' => $this->Cache_GetStats(),
            'engine' => array(
                'time_load_module' => number_format(round($this->nTimeLoadModule, 3), 3),
                'full_time' => number_format(round($nTimeFull, 3), 3),
                'exec_time' => number_format(round($nExecTime, 3), 3),
                'files_count' => F::File_GetIncludedCount(),
                'files_time' => number_format(round(F::File_GetIncludedTime(), 3), 3),
            ),
        );
    }

    /**
     * Возвращает время старта выполнения движка в микросекундах
     *
     * @return int
     */
    public function GetTimeInit() {

        return $this->nTimeInit;
    }

    /**
     * Ставим хук на вызов неизвестного метода и считаем что хотели вызвать метод какого либо модуля
     *
     * @param string $sName    Имя метода
     * @param array $aArgs    Аргументы
     * @return mixed
     */
    public function __call($sName, $aArgs) {

        return $this->_CallModule($sName, $aArgs);
    }

    /**
     * Блокируем копирование/клонирование объекта ядра
     *
     */
    protected function __clone() {

    }

    /**
     * Получает объект маппера
     * <pre>
     * Engine::getInstance()->Database_GetConnect($aConfig);
     * </pre>
     *
     * @param string                      $sClassName Класс модуля маппера
     * @param string|null                 $sName      Имя маппера
     * @param DbSimple_Driver_Mysqli|null $oConnect   Объект коннекта к БД
     *                                                Можно получить так:
     *
     * @return mixed
     */
    public static function GetMapper($sClassName, $sName = null, $oConnect = null) {

        $sModuleName = static::GetClassInfo($sClassName, self::CI_MODULE, true);
        if ($sModuleName) {
            if (!$sName) {
                $sName = $sModuleName;
            }
            $sClass = $sClassName . '_Mapper' . $sName;
            if (!$oConnect) {
                $oConnect = static::getInstance()->Database_GetConnect();
            }
            $sClass = static::getInstance()->Plugin_GetDelegate('mapper', $sClass);
            return new $sClass($oConnect);
        }
        return null;
    }

    /**
     * Возвращает класс сущности, контролируя варианты кастомизации
     *
     * @param  string $sName    Имя сущности, возможны сокращенные варианты.
     * Например <pre>ModuleUser_EntityUser</pre> эквивалентно <pre>User_User</pre> и эквивалентно <pre>User</pre> т.к. имя сущности совпадает с именем модуля
     * @return string
     * @throws Exception
     */
    public static function GetEntityClass($sName) {

        if (!isset(self::$aClasses[$sName])) {
            /*
             * Сущности, имеющие такое же название как модуль,
             * можно вызывать сокращенно. Например, вместо User_User -> User
             */
            switch (substr_count($sName, '_')) {
                case 0:
                    $sEntity = $sModule = $sName;
                    break;

                case 1:
                    // * Поддержка полного синтаксиса при вызове сущности
                    $aInfo = static::GetClassInfo($sName, self::CI_ENTITY | self::CI_MODULE | self::CI_PLUGIN);
                    if ($aInfo[self::CI_MODULE] && $aInfo[self::CI_ENTITY]) {
                        $sName = $aInfo[self::CI_MODULE] . '_' . $aInfo[self::CI_ENTITY];
                    }

                    list($sModule, $sEntity) = explode('_', $sName, 2);
                    /*
                     * Обслуживание короткой записи сущностей плагинов
                     * PluginTest_Test -> PluginTest_ModuleTest_EntityTest
                     */
                    if ($aInfo[self::CI_PLUGIN]) {
                        $sPlugin = $aInfo[self::CI_PLUGIN];
                        $sModule = $sEntity;
                    }
                    break;

                case 2:
                    // * Поддержка полного синтаксиса при вызове сущности плагина
                    $aInfo = static::GetClassInfo($sName, self::CI_ENTITY | self::CI_MODULE | self::CI_PLUGIN);
                    if ($aInfo[self::CI_PLUGIN] && $aInfo[self::CI_MODULE] && $aInfo[self::CI_ENTITY]) {
                        $sName = 'Plugin' . $aInfo[self::CI_PLUGIN]
                            . '_' . $aInfo[self::CI_MODULE]
                            . '_' . $aInfo[self::CI_ENTITY];
                    }
                    // * Entity плагина
                    if ($aInfo[self::CI_PLUGIN]) {
                        list(, $sModule, $sEntity) = explode('_', $sName);
                        $sPlugin = $aInfo[self::CI_PLUGIN];
                    } else {
                        throw new Exception("Unknown entity '{$sName}' given.");
                    }
                    break;

                default:
                    throw new Exception("Unknown entity '{$sName}' given.");
            }

            $sClass = isset($sPlugin)
                ? 'Plugin' . $sPlugin . '_Module' . $sModule . '_Entity' . $sEntity
                : 'Module' . $sModule . '_Entity' . $sEntity;

            // * If Plugin Entity doesn't exist, search among it's Module delegates
            if (isset($sPlugin) && !static::GetClassPath($sClass)) {
                $aModulesChain = static::GetInstance()->Plugin_GetDelegationChain(
                    'module', 'Plugin' . $sPlugin . '_Module' . $sModule
                );
                foreach ($aModulesChain as $sModuleName) {
                    $sClassTest = $sModuleName . '_Entity' . $sEntity;
                    if (static::GetClassPath($sClassTest)) {
                        $sClass = $sClassTest;
                        break;
                    }
                }
                if (!static::GetClassPath($sClass)) {
                    $sClass = 'Module' . $sModule . '_Entity' . $sEntity;
                }
            }

            /**
             * Определяем наличие делегата сущности
             * Делегирование указывается только в полной форме!
             */
            $sClass = static::getInstance()->Plugin_GetDelegate('entity', $sClass);

            self::$aClasses[$sName] = $sClass;
        } else {
            $sClass = self::$aClasses[$sName];
        }
        return $sClass;
    }

    /**
     * Создает объект сущности, контролируя варианты кастомизации
     *
     * @param  string $sName    Имя сущности, возможны сокращенные варианты.
     * Например <pre>ModuleUser_EntityUser</pre> эквивалентно <pre>User_User</pre> и эквивалентно <pre>User</pre> т.к. имя сущности совпадает с именем модуля
     * @param  array  $aParams
     * @return Entity
     * @throws Exception
     */
    public static function GetEntity($sName, $aParams = array()) {

        $sClass = static::GetEntityClass($sName);
        /** @var Entity $oEntity */
        $oEntity = new $sClass($aParams);
        $oEntity->Init();

        return $oEntity;
    }

    /**
     * Returns array of entity objects
     *
     * @param string     $sName - Entity name
     * @param array      $aRows
     * @param array|null $aOrderIdx
     *
     * @return array
     */
    public static function GetEntityRows($sName, $aRows = array(), $aOrderIdx = null) {

        $aResult = array();
        if ($aRows) {
            $sClass = static::GetEntityClass($sName);
            if (is_array($aOrderIdx) && sizeof($aOrderIdx)) {
                foreach ($aOrderIdx as $iIndex) {
                    if (isset($aRows[$iIndex])) {
                        /** @var Entity $oEntity */
                        $oEntity = new $sClass($aRows[$iIndex]);
                        $oEntity->Init();
                        $aResult[$iIndex] = $oEntity;
                    }
                }
            } else {
                foreach ($aRows as $nI => $aRow) {
                    /** @var Entity $oEntity */
                    $oEntity = new $sClass($aRow);
                    $oEntity->Init();
                    $aResult[$nI] = $oEntity;
                }
            }
        }
        return $aResult;
    }

    /**
     * Возвращает имя плагина модуля, если модуль принадлежит плагину
     * Например <pre>Openid</pre>
     *
     * @static
     *
     * @param Module|string $xModule - Объект модуля
     *
     * @return string|null
     */
    public static function GetPluginName($xModule) {

        return static::GetClassInfo($xModule, self::CI_PLUGIN, true);
    }

    /**
     * Возвращает префикс плагина
     * Например <pre>PluginOpenid_</pre>
     *
     * @static
     *
     * @param Module|string $xModule Объект модуля
     *
     * @return string    Если плагина нет, возвращает пустую строку
     */
    public static function GetPluginPrefix($xModule) {

        return static::GetClassInfo($xModule, self::CI_PPREFIX, true);
    }

    /**
     * Возвращает имя модуля
     *
     * @static
     *
     * @param Module|string $xModule Объект модуля
     *
     * @return string|null
     */
    public static function GetModuleName($xModule) {

        return static::GetClassInfo($xModule, self::CI_MODULE, true);
    }

    /**
     * Возвращает имя сущности
     *
     * @static
     *
     * @param Entity|string $xEntity Объект сущности
     *
     * @return string|null
     */
    public static function GetEntityName($xEntity) {

        return static::GetClassInfo($xEntity, self::CI_ENTITY, true);
    }

    /**
     * Возвращает имя экшена
     *
     * @static
     *
     * @param Action|string $xAction    Объект экшена
     *
     * @return string|null
     */
    public static function GetActionName($xAction) {

        return static::GetClassInfo($xAction, self::CI_ACTION, true);
    }

    /**
     * Возвращает информацию об объекта или классе
     *
     * @static
     * @param LsObject|string $oObject    Объект или имя класса
     * @param int $iFlag    Маска по которой нужно вернуть рузультат. Доступные маски определены в константах CI_*
     * Например, получить информацию о плагине и модуле:
     * <pre>
     * Engine::GetClassInfo($oObject,Engine::CI_PLUGIN | Engine::CI_MODULE);
     * </pre>
     * @param bool $bSingle    Возвращать полный результат или только первый элемент
     * @return array|string|null
     */
    public static function GetClassInfo($oObject, $iFlag = self::CI_DEFAULT, $bSingle = false) {

        $aResult = array();
        $sClassName = is_string($oObject) ? $oObject : get_class($oObject);
        $aInfo = (!empty(self::$aClassesInfo[$sClassName]) ? self::$aClassesInfo[$sClassName] : array());

        if ($iFlag & self::CI_PLUGIN) {
            if (!isset($aInfo[self::CI_PLUGIN])) {
                $aInfo[self::CI_PLUGIN] = preg_match('/^Plugin([^_]+)/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_PLUGIN] = $aInfo[self::CI_PLUGIN];
            }
            $aResult[self::CI_PLUGIN] = $aInfo[self::CI_PLUGIN];
        }
        if ($iFlag & self::CI_ACTION) {
            if (!isset($aInfo[self::CI_ACTION])) {
                $aInfo[self::CI_ACTION] = preg_match('/^(?:Plugin[^_]+_|)Action([^_]+)/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_ACTION] = $aInfo[self::CI_ACTION];
            }
            $aResult[self::CI_ACTION] = $aInfo[self::CI_ACTION];
        }
        if ($iFlag & self::CI_MODULE) {
            if (!isset($aInfo[self::CI_MODULE])) {
                $aInfo[self::CI_MODULE] = preg_match('/^(?:Plugin[^_]+_|)Module(?:ORM|)([^_]+)/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_MODULE] = $aInfo[self::CI_MODULE];
            }
            $aResult[self::CI_MODULE] = $aInfo[self::CI_MODULE];
        }
        if ($iFlag & self::CI_ENTITY) {
            if (!isset($aInfo[self::CI_ENTITY])) {
                $aInfo[self::CI_ENTITY] = preg_match('/_Entity(?:ORM|)([^_]+)/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_ENTITY] = $aInfo[self::CI_ENTITY];
            }
            $aResult[self::CI_ENTITY] = $aInfo[self::CI_ENTITY];
        }
        if ($iFlag & self::CI_MAPPER) {
            if (!isset($aInfo[self::CI_MAPPER])) {
                $aInfo[self::CI_MAPPER] = preg_match('/_Mapper(?:ORM|)([^_]+)/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_MAPPER] = $aInfo[self::CI_MAPPER];
            }
            $aResult[self::CI_MAPPER] = $aInfo[self::CI_MAPPER];
        }
        if ($iFlag & self::CI_HOOK) {
            if (!isset($aInfo[self::CI_HOOK])) {
                $aInfo[self::CI_HOOK] = preg_match('/^(?:Plugin[^_]+_|)Hook([^_]+)$/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_HOOK] = $aInfo[self::CI_HOOK];
            }
            $aResult[self::CI_HOOK] = $aInfo[self::CI_HOOK];
        }
        if ($iFlag & self::CI_BLOCK) {
            if (!isset($aInfo[self::CI_BLOCK])) {
                $aInfo[self::CI_BLOCK] = preg_match('/^(?:Plugin[^_]+_|)Block([^_]+)$/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_BLOCK] = $aInfo[self::CI_BLOCK];
            }
            $aResult[self::CI_BLOCK] = $aInfo[self::CI_BLOCK];
        }
        if ($iFlag & self::CI_WIDGET) {
            if (!isset($aInfo[self::CI_WIDGET])) {
                $aInfo[self::CI_WIDGET] = preg_match('/^(?:Plugin[^_]+_|)Widget([^_]+)$/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_WIDGET] = $aInfo[self::CI_WIDGET];
            }
            $aResult[self::CI_WIDGET] = $aInfo[self::CI_WIDGET];
        }
        if ($iFlag & self::CI_METHOD) {
            if (!isset($aInfo[self::CI_METHOD])) {
                $sModuleName = isset($aInfo[self::CI_MODULE])
                    ? $aInfo[self::CI_MODULE]
                    : static::GetClassInfo($sClassName, self::CI_MODULE, true);
                $aInfo[self::CI_METHOD] = preg_match('/_([^_]+)$/', $sClassName, $aMatches)
                    ? ($sModuleName && strtolower($aMatches[1]) == strtolower('module' . $sModuleName) ? null : $aMatches[1])
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_METHOD] = $aInfo[self::CI_METHOD];
            }
            $aResult[self::CI_METHOD] = $aInfo[self::CI_METHOD];
        }
        if ($iFlag & self::CI_PPREFIX) {
            if (!isset($aInfo[self::CI_PPREFIX])) {
                $sPluginName = isset($aInfo[self::CI_PLUGIN])
                    ? $aInfo[self::CI_PLUGIN]
                    : static::GetClassInfo($sClassName, self::CI_PLUGIN, true);
                $aInfo[self::CI_PPREFIX] = $sPluginName
                    ? "Plugin{$sPluginName}_"
                    : '';
                self::$aClassesInfo[$sClassName][self::CI_PPREFIX] = $aInfo[self::CI_PPREFIX];
            }
            $aResult[self::CI_PPREFIX] = $aInfo[self::CI_PPREFIX];
        }
        if ($iFlag & self::CI_INHERIT) {
            if (!isset($aInfo[self::CI_INHERIT])) {
                $aInfo[self::CI_INHERIT] = preg_match('/_Inherits?_(\w+)$/', $sClassName, $aMatches)
                    ? $aMatches[1]
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_INHERIT] = $aInfo[self::CI_INHERIT];
            }
            $aResult[self::CI_INHERIT] = $aInfo[self::CI_INHERIT];
        }
        if ($iFlag & self::CI_INHERITS) {
            if (!isset($aInfo[self::CI_INHERITS])) {
                $sInherit = isset($aInfo[self::CI_INHERIT])
                    ? $aInfo[self::CI_INHERIT]
                    : static::GetClassInfo($sClassName, self::CI_INHERIT, true);
                $aInfo[self::CI_INHERITS] = $sInherit
                    ? static::GetClassInfo($sInherit, self::CI_OBJECT, false)
                    : false;
                self::$aClassesInfo[$sClassName][self::CI_INHERITS] = $aInfo[self::CI_INHERITS];
            }
            $aResult[self::CI_INHERITS] = $aInfo[self::CI_INHERITS];
        }
        if ($iFlag & self::CI_CLASSPATH) {
            if (!isset($aInfo[self::CI_CLASSPATH])) {
                $aInfo[self::CI_CLASSPATH] = static::GetClassPath($sClassName);
                self::$aClassesInfo[$sClassName][self::CI_CLASSPATH] = $aInfo[self::CI_CLASSPATH];
            }
            $aResult[self::CI_CLASSPATH] = $aInfo[self::CI_CLASSPATH];
        }

        return $bSingle ? end($aResult) : $aResult;
    }

    /**
     * Возвращает информацию о пути до файла класса.
     * Используется в {@link autoload автозагрузке}
     *
     * @static
     *
     * @param LsObject|string $oObject Объект - модуль, экшен, плагин, хук, сущность
     *
     * @return null|string
     */
    public static function GetClassPath($oObject) {

        $aInfo = static::GetClassInfo($oObject, self::CI_OBJECT);
        $aPathSeek = Config::Get('path.root.seek');
        if ($aInfo[self::CI_ENTITY]) {
            // Сущность
            if ($aInfo[self::CI_PLUGIN]) {
                // Сущность модуля плагина
                $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                    . '/classes/modules/' . strtolower($aInfo[self::CI_MODULE])
                    . '/entity/' . $aInfo[self::CI_ENTITY] . '.entity.class.php';
            } else {
                // Сущность модуля ядра
                $sFile = 'classes/modules/' . strtolower($aInfo[self::CI_MODULE])
                    . '/entity/' . $aInfo[self::CI_ENTITY] . '.entity.class.php';
            }
        } elseif ($aInfo[self::CI_MAPPER]) {
            // Маппер
            if ($aInfo[self::CI_PLUGIN]) {
                // Маппер модуля плагина
                $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                    . '/classes/modules/' . strtolower($aInfo[self::CI_MODULE])
                    . '/mapper/' . $aInfo[self::CI_MAPPER] . '.mapper.class.php';
            } else {
                // Маппер модуля ядра
                $sFile = 'classes/modules/' . strtolower($aInfo[self::CI_MODULE])
                    . '/mapper/' . $aInfo[self::CI_MAPPER] . '.mapper.class.php';
            }
        } elseif ($aInfo[self::CI_ACTION]) {
            // Экшн
            if ($aInfo[self::CI_PLUGIN]) {
                // Экшн плагина
                $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                    . '/classes/actions/Action' . $aInfo[self::CI_ACTION] . '.class.php';
            } else {
                // Экшн ядра
                $sFile = 'classes/actions/Action' . $aInfo[self::CI_ACTION] . '.class.php';
            }
        } elseif ($aInfo[self::CI_MODULE]) {
            // Модуль
            if ($aInfo[self::CI_PLUGIN]) {
                // Модуль плагина
                $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                    . '/classes/modules/' . strtolower($aInfo[self::CI_MODULE])
                    . '/' . $aInfo[self::CI_MODULE] . '.class.php';
                ;
            } else {
                // Модуль ядра
                $sFile = 'classes/modules/' . strtolower($aInfo[self::CI_MODULE])
                    . '/' . $aInfo[self::CI_MODULE] . '.class.php';
            }
        } elseif ($aInfo[self::CI_HOOK]) {
            // Хук
            if ($aInfo[self::CI_PLUGIN]) {
                // Хук плагина
                $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                    . '/classes/hooks/Hook' . $aInfo[self::CI_HOOK]
                    . '.class.php';
            } else {
                // Хук ядра
                $sFile = 'classes/hooks/Hook' . $aInfo[self::CI_HOOK] . '.class.php';
            }
        } elseif ($aInfo[self::CI_BLOCK]) {
            // LS-compatible
            if ($aInfo[self::CI_PLUGIN]) {
                // Блок плагина
                $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                    . '/classes/blocks/Block' . $aInfo[self::CI_BLOCK]
                    . '.class.php';
            } else {
                // Блок ядра
                $sFile = 'classes/blocks/Block' . $aInfo[self::CI_BLOCK] . '.class.php';
            }
        } elseif ($aInfo[self::CI_WIDGET]) {
            // Виджет
            if ($aInfo[self::CI_PLUGIN]) {
                // Виджет плагина
                $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                    . '/classes/widgets/Widget' . $aInfo[self::CI_WIDGET]
                    . '.class.php';
            } else {
                // Блок ядра
                $sFile = 'classes/widgets/Widget' . $aInfo[self::CI_WIDGET] . '.class.php';
            }
        } elseif ($aInfo[self::CI_PLUGIN]) {
            // Плагин
            $sFile = 'plugins/' . F::StrUnderscore($aInfo[self::CI_PLUGIN])
                . '/Plugin' . $aInfo[self::CI_PLUGIN]
                . '.class.php';
        } else {
            $sClassName = is_string($oObject) ? $oObject : get_class($oObject);
            $sFile = $sClassName . '.class.php';
            $aPathSeek = array(
                Config::Get('path.dir.engine') . '/classes/core/',
                Config::Get('path.dir.engine') . '/classes/abstract/',
            );
        }
        $sPath = F::File_Exists($sFile, $aPathSeek);

        return $sPath ? $sPath : null;
    }

    /**
     * @param string $sName
     * @param array  $aArgs
     *
     * @return mixed
     */
    public static function __callStatic($sName, $aArgs = array()) {

        if (substr($sName, 0, 6) == 'Module') {
            $oModule = static::Module($sName);
            if ($oModule) {
                return $oModule;
            }
        }
        return call_user_func_array(array(static::getInstance(), $sName), $aArgs);
    }

    /**
     * @param $sModuleName
     *
     * @return null|object
     */
    public static function Module($sModuleName) {

        return static::getInstance()->GetModule($sModuleName);
    }

    /**
     * Returns current user
     *
     * @return ModuleUser_EntityUser
     */
    public static function User() {

        return static::ModuleUser()->GetUserCurrent();
    }

    /**
     * If user is authorized
     *
     * @return bool
     */
    public static function IsUser() {

        $oUser = static::User();
        return $oUser;
    }

    /**
     * If user is authorized && admin
     *
     * @return bool
     */
    public static function IsAdmin() {

        $oUser = static::User();
        return ($oUser && $oUser->isAdministrator());
    }

    /**
     * If user is authorized && not admin
     *
     * @return bool
     */
    public static function IsNotAdmin() {

        $oUser = static::User();
        return ($oUser && !$oUser->isAdministrator());
    }

    /**
     * Returns UserId if user is authorized
     *
     * @return int|null
     */
    public static function UserId() {

        if ($oUser = static::User()) {
            return $oUser->GetId();
        }
        return null;
    }

    /**
     * If plugin is activated
     *
     * @param   string  $sPlugin
     * @return  bool
     */
    public static function ActivePlugin($sPlugin) {

        return static::Plugin_IsActivePlugin($sPlugin);
    }

}

// EOF