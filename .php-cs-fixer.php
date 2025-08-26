<?php
$header = <<<EOF
@author Mygento Team
@copyright 2017-2026 Mygento (https://www.mygento.com)
@package Mygento_Kkm
EOF;

$finder = PhpCsFixer\Finder::create()->in('.')->name('*.phtml');
$config = new \Mygento\CS\Config\Module($header);
$config->setFinder($finder);
return $config;
