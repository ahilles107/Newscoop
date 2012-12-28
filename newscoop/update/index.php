<?php
/**
 * @package Newscoop
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../conf/database_conf.php';

use Newscoop\Update;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

$app = new Silex\Application();

$app['debug'] = true;
$app['migration_conf'] = __DIR__ . '/../application/configs/migrations.yml';

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

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/Resources/views/',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['security.firewalls'] = array(
    'login' => array(
        'anonymous' => true,
        'pattern' => '^/login',
    ),
    'update' => array(
        'pattern' => '^/',
        'form' => array('login_path' => '/login', 'check_path' => '/check'),
        'users' => array(
            $Campsite['db']['user'] => array('ROLE_ADMIN', sha1($Campsite['db']['pass'])),
        ),
        'logout' => array('logout_path' => '/logout')
    ),
);

$app['security.access_rules'] = array(
    array('/update/login', 'IS_AUTHENTICATED_ANONYMOUSLY '),
);

$app->register(new Silex\Provider\SecurityServiceProvider());
$app['security.encoder.digest'] = $app->share(function ($app) {
    return new MessageDigestPasswordEncoder('sha1', false, 1);
});

$app->register(new Silex\Provider\SessionServiceProvider());

$app['newscoop_update'] = new Update($app['db'], $app['migration_conf']);
$em = $app['db.orm.em'];

// check for new updates

$app->get('/', function() use($app, $em) {
    $process = new Process\Process('php ' . __DIR__.'/../scripts/newscoop.php migrations:status --configuration="'.$app['migration_conf'].'"');
    $process->setTimeout(3600);
    $process->run();
    $migrationsStatus = $app['newscoop_update']->getStatus();


    return $app['twig']->render('index.html.twig', array(
        'migrationsStatus' => $migrationsStatus,
    ));
})
->bind('status');

$app->post('/run-update', function() use($app) {

});

$app->post('/rollback-update', function() use($app) {
  //$version = $app['request']->get('version');
});


$app->get('/check-for-releases', function() use($app) {

});

$app->get('/login', function(Request $request) use ($app) {
    return $app['twig']->render('login.html.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->run();