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
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/javascript', $response->getHeaderLine('Content-Type'));
        $body = $response->getBody();
        $body->rewind();
        $expected = file_get_contents(__DIR__ . '/assets/test.js');
        $this->assertEquals($expected, $body->getContents());
    }

    /**
     * @dataProvider writeToWebDirDataProvider
     */
    public function testWriteToWebDir($asset)
    {
        $destDir    = __DIR__ . '/public';
        $sourceDir  = __DIR__ . '/assets';
        $sourceFile = $sourceDir . $asset;
        $destFile   = $destDir . $asset;

        if (file_exists($destFile)) {
            unlink($destFile);
        }
        $middleware = new AssetsManager([
            'paths'   => $sourceDir,
            'web_dir' => $destDir,
        ]);

        $middleware($this->request($asset), $this->response());
        $this->assertFileEquals($sourceFile, $destFile);
        if (file_exists($destFile)) {
            unlink($destFile);
        }
    }

    public function writeToWebDirDataProvider()
    {
        return [
            ['/test.js'],
            ['/sub/test.css'],
        ];
    }

    public function testCustomMime()
    {
        $middleware = new AssetsManager([
            'paths'      => __DIR__ . '/assets',
            'mime_types' => [
                'js' => 'mime/type',
            ],
        ]);
        $response = $middleware($this->request('/test.js'), $this->response());
        $this->assertEquals('mime/type', $response->getHeaderLine('Content-Type'));
    }

    public function testCallsNextIfFileNotFound()
    {
        $middleware = new AssetsManager([
            'paths' => __DIR__ . '/assets',
        ]);
        $response = $middleware($this->request('/test.txt'), $this->response(), function ($req, $res) {
            $res->getBody()->write('next middleware');

            return $res;
        });
        $expected = 'next middleware';
        $body     = $response->getBody();
        $body->rewind();
        $this->assertEquals($expected, $body->getContents());
    }

    public function testReturns404IfNoNextAndFileNotFound()
    {
        $middleware = new AssetsManager([
            'paths' => __DIR__ . '/assets',
        ]);
        $response = $middleware($this->request('/test.txt'), $this->response());
        $this->assertEquals(404, $response->getStatusCode());
    }
}
