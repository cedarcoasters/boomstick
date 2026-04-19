sed -i "
/^\/\/ COMPOSER/c\\
if(file_exists(dirname(__DIR__).'/composer-<?=O::$entryPointModule;?>/vendor/autoload.php')) { \
require_once( dirname(__DIR__).'/composer-<?=O::$entryPointModule;?>/vendor/autoload.php' ); }
" ../entry-<?=O::$entryPointModule;?>/public/index.php