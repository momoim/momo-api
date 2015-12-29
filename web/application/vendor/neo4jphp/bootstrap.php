<?php

spl_autoload_register(function ($sClass) {
	$sLibPath = __DIR__.'/lib/';
	$sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
	$sClassPath = $sLibPath.$sClassFile;
	if (file_exists($sClassPath)) {
		require($sClassPath);
	}
});
