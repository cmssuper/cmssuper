<?php

/*
 * cmsSuper 开发说明
 * 
 */

define("APPNAME", substr(__DIR__, strrpos(__DIR__, DIRECTORY_SEPARATOR) + 1));
require dirname(__DIR__) . '/index.php';

$crawler = DATA . '/session/crawler.lock';
!is_file($crawler) && file_put_contents($crawler, "");

session_start();
loader::init()->run();

