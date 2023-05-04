<?php
$targetFolder = '/www/sourcewww/oficinas/uraa.unitru.edu.pe/htdocs/storage/app/public';
$linkFolder = '/www/sourcewww/oficinas/uraa.unitru.edu.pe/htdocs/public';
symlink($targetFolder,$linkFolder);
echo 'Symlink completed';
?>