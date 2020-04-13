<?php
//模块中间件配置
return [
    \wstmart\middleware\AllowCrossDomain::class,
    \wstmart\middleware\AccessCheck::class,
];