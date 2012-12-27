<?php
/**
 * @package Newscoop
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../conf/database_conf.php';

use Symfony\Component\Process;

$app = new Silex\Application();
$app['debug'] = true;
$app['migration_conf'] = '--configuration="'.__DIR__.'/../application/configs/migrations.yml"';

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    =>  'pdo_mysql',
        'dbname'    =>  $Campsite['db']['name'],
        'host'      =>  $Campsite['db']['host'],
        'user'      =>  $Campsite['db']['user'],
        'password'  =>  $Campsite['db']['pass']
    ),
));

$app->register(new Nutwerk\Provider\DoctrineORMServiceProvider(), array(
    'db.orm.proxies_dir'           => __DIR__.'/../../library/Proxy',
    'db.orm.proxies_namespace'     => 'Proxy',
    'db.orm.auto_generate_proxies' => false,

    'db.orm.entities'              => array(array(
        'type'      => 'annotation',
        'path'      => __DIR__.'/../../library/Newscoop',
        'namespace' => 'Entity',
    )),
));


$em = $app['db.orm.em'];

// check for new updates

$app->get('/', function() use($app, $em) {
    $process = new Process\Process('php ' . __DIR__.'/../scripts/newscoop.php migrations:status '.$app['migration_conf']);
    $process->setTimeout(3600);
    $process->run();

    print '<pre>' . $process->getOutput() . '</pre>';

    return 'Hello Paweł'; 
});

$app->post('/run-update', function() use($app) {

});

$app->post('/rollback-update', function() use($app) {
  //$version = $app['request']->get('version');
});

$app->get('/check-for-updates', function() use($app) {

});

$app->get('/check-for-releases', function() use($app) {

});

$app->run();