<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        try {
            $db = db_connect();

            if (($db->DBDriver ?? '') === 'MySQLi') {
                $db->simpleQuery('SET SESSION MAX_EXECUTION_TIME=' . max(100, (int) env('database.maxExecutionTimeMs', 1500)));
                $db->simpleQuery('SET SESSION innodb_lock_wait_timeout=' . max(1, (int) env('database.lockWaitTimeoutSeconds', 3)));
            }
        } catch (Throwable $exception) {
            log_message('warning', 'Failed to apply DB hardening settings: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
