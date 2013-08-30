<?php

$config = array();

$config['view']['theme'] = 'default';
$config['module']['user']['profile_photo_width'] = 300;

$config['path']['smarty']['template'] = array(
    'themes'  => '___path.skins.dir___/___view.skin___/themes/',
    'layouts' => '___path.skins.dir___/___view.skin___/themes/default/layouts/',
    'tpls'    => '___path.skins.dir___/___view.skin___/tpls/',
);

/**
 * Grid type:
 *
 * fluid - резина
 * fixed - фиксированная ширина
 */
$config['view']['grid']['type'] = 'fixed';

/* Fluid settings */
$config['view']['grid']['fluid_min_width'] = 900;
$config['view']['grid']['fluid_max_width'] = 1200;

/* Fixed settings */
$config['view']['grid']['fixed_width'] = 1000;

$config['head']['default']['js'] = Config::Get('head.default.js');
$config['head']['default']['js'][] = '___path.static.assets___/js/init.js';

$config['head']['default']['css'] = array_merge(
    Config::Get('head.default.css'),
    array(
         // Template styles
         '___path.skin.dir___/assets/css/base.css',
         '___path.framework.dir___/js/vendor/jquery-ui/css/smoothness/jquery-ui-1.10.2.custom.css',
         '___path.framework.dir___/js/vendor/markitup/skins/synio/style.css',
         '___path.framework.dir___/js/vendor/markitup/sets/synio/style.css',
         '___path.framework.dir___/js/vendor/jcrop/jquery.Jcrop.css',
         '___path.framework.dir___/js/vendor/prettify/prettify.css',
         '___path.framework.dir___/js/vendor/prettyphoto/css/prettyphoto.css',
         '___path.framework.dir___/js/vendor/notifier/jquery.notifier.css',
         '___path.skin.dir___/assets/css/grid.css',
         '___path.skin.dir___/assets/css/forms.css',
         '___path.skin.dir___/assets/css/common.css',
         '___path.skin.dir___/assets/css/icons.css',
         '___path.skin.dir___/assets/css/navs.css',
         '___path.skin.dir___/assets/css/tables.css',
         '___path.skin.dir___/assets/css/topic.css',
         '___path.skin.dir___/assets/css/photoset.css',
         '___path.skin.dir___/assets/css/comments.css',
         '___path.skin.dir___/assets/css/widgets.css',
         '___path.skin.dir___/assets/css/blog.css',
         '___path.skin.dir___/assets/css/modals.css',
         '___path.skin.dir___/assets/css/profile.css',
         '___path.skin.dir___/assets/css/wall.css',
         '___path.skin.dir___/assets/css/activity.css',
         '___path.skin.dir___/assets/css/admin.css',
         '___path.skin.dir___/assets/css/toolbar.css',
         '___path.skin.dir___/assets/css/poll.css',
         '___path.static.skin___/themes/___view.theme___/style.css',
         '___path.skin.dir___/assets/css/print.css',
    )
);


return $config;

// EOF