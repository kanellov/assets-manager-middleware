<?php
/**
 * kanellov/assets-manager-middleware
 * 
 * @link https://github.com/kanellov/assets-manager-middleware for the canonical source repository
 * @copyright Copyright (c) 2016 Vassilis Kanellopoulos (http://kanellov.com)
 * @license GNU GPLv3 http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */

namespace KnlvTest\Middleware;

use Knlv\Middleware\AssetsManager;
use PHPUnit_Framework_TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class AssetsManagerTest extends PHPUnit_Framework_TestCase
{
    protected function request($uri = '', array $headers = [], array $server = [])
    {
        $request = new ServerRequest($server, [], $uri, null, 'php://temp', $headers);

        return $request->withUri(new Uri($uri));
    }

    protected function response(array $headers = [])
    {
        $response = new Response('php://temp', 200, $headers);

        return $response;
    }

    public function testResponseIfFileExists()
    {
        $middleware = new AssetsManager([
            'paths' => __DIR__ . '/assets',
        ]);

        $response = $middleware($this->request('/test.js'), $this->response());
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/javascript', $response->getHeaderLine('Content-Type'));
        $body = $response->getBody();
        $body->rewind();
        $expected = file_get_contents(__DIR__ . '/assets/test.js');
        $this->assertEquals($expected, $body->getContents());
    }

    public function testWriteToWebDir()
    {
        if (file_exists(__DIR__ . '/public/test.js')) {
            unlink(__DIR__ . '/public/test.js');
        }
        $middleware = new AssetsManager([
            'paths'   => __DIR__ . '/assets',
            'web_dir' => __DIR__ . '/public',
        ]);

        $middleware($this->request('/test.js'), $this->response());
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $this->assertFileEquals(__DIR__ . '/public/test.js', __DIR__ . '/assets/test.js');
        if (file_exists(__DIR__ . '/public/test.js')) {
            unlink(__DIR__ . '/public/test.js');
        }
    }
}
