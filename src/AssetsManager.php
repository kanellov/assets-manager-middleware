<?php

/**
 * kanellov/assets-manager-middleware
 * 
 * @link https://github.com/kanellov/assets-manager-middleware for the canonical source repository
 * @copyright Copyright (c) 2016 Vassilis Kanellopoulos (http://kanellov.com)
 * @license GNU GPLv3 http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */

namespace Knlv\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * A middleware to serve assets from directories that are not in 
 * web server's  document root. 
 * 
 * Supports:
 * - mime type detection from extension or using finfo_file function
 * - writing files to web directory 
 *
 * @author kanellov
 */
class AssetsManager
{
    /**
     * Default file extension => mime type map
     * @var array
     */
    private static $defaultMimeTypes = [
        'txt'  => 'text/plain',
        'htm'  => 'text/html',
        'html' => 'text/html',
        'php'  => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'swf'  => 'application/x-shockwave-flash',
        'flv'  => 'video/x-flv',

        // images
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'svg'  => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt'  => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai'  => 'application/postscript',
        'eps' => 'application/postscript',
        'ps'  => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

        // fonts
        'eot'   => 'application/vnd.ms-fontobject',
        'woff'  => 'application/font-woff',
        'woff2' => 'application/font-woff2',
        'ttf'   => 'application/x-font-truetype',
        'svg'   => 'image/svg+xml',
        'otf'   => 'application/x-font-opentype',
    ];

    /**
     * The file extension => mime type map
     * @var array
     */
    private $mimeTypes = [];
    /**
     * The paths to lookup for assets
     * @var array
     */
    private $paths = [];

    /**
     * The public directory for storing assets
     * @var string
     */
    private $webDir;

    /**
     * Constructs the middleware.
     * 
     * @param array $options 
     *          - paths: the directories to look for assets
     *          - web_dir: the directory to write assets if found
     *          - mime_types: additional mime types to support
     */
    public function __construct(array $options)
    {
        if (isset($options['paths'])) {
            $this->paths = is_array($options['paths']) ? $options['paths'] : [$options['paths']];
        }

        if (isset($options['web_dir']) && is_dir($options['web_dir'])) {
            $this->webDir = (string) $options['web_dir'];
        }

        $this->mimeTypes = self::$defaultMimeTypes;
        if (isset($options['mime_types']) && is_array($options['mime_types'])) {
            $this->mimeTypes = array_merge($this->mimeTypes, $options['mime_types']);
        }
    }

    /**
     * Middleware. It tries to look for the file from the request path in the 
     * provided paths. If it is found it returns early with a response with the
     * file contents and appropriate mime type in headers.
     * 
     * If web_dir is provided, it store the file so next time web server finds it
     * there.
     * 
     * @param ServerRequestInterface $req the server request
     * @param ResponseInterface $res the response
     * @param callable $next the next callable
     * @return ResponseInterface the response containing asset if found or from next middlewares
     */
    public function __invoke(
        ServerRequestInterface $req,
        ResponseInterface $res,
        callable $next = null
    ) {
        $uriPath = $req->getUri()->getPath();
        $file    = $this->findFile($uriPath);
        if ($file) {
            $contents = file_get_contents($file);
            $this->writeToWebDir($uriPath, $contents);

            try {
                $res->getBody()->rewind();
                $res->getBody()->write($contents);
                $res = $res->withStatus(200);

                return $res->withHeader('Content-Type', $this->detectMimeType($file));
            } catch (RuntimeException $ex) {
                trigger_error(sprintf('Unable to serve %s. %s', $file, $ex->getMessage()));
            }
        }

        if (null !== $next) {
            return $next($req, $res);
        }

        return $res->withStatus(404, sprintf('%s not found', $uriPath));
    }

    /**
     * Finds the file from the request's uri path in the provided paths.
     * 
     * @param string $uriPath the request uri path
     * @return boolean|string false if no file is found; the full file path if file is found
     */
    private function findFile($uriPath)
    {
        return array_reduce($this->paths, function ($file, $path) use ($uriPath) {
            if (false !== $file) {
                return $file;
            }

            $file = $path . $uriPath;
            if (is_readable($file)) {
                return $file;
            }

            return false;
        }, false);
    }

    /**
     * Detects file mime type from extension or using finfo_file if exists.
     * 
     * @param string $file the file to examine
     * @return string the mime type
     */
    private function detectMimeType($file)
    {
        $fileParts = explode('.', $file);
        $extension = array_pop($fileParts);
        $extension = strtolower($extension);

        if (array_key_exists($extension, $this->mimeTypes)) {
            return $this->mimeTypes[$extension];
        }

        if (function_exists('finfo_open')) {
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file);
            finfo_close($finfo);

            return $mimeType;
        }

        return 'application/octet-stream';
    }

    /**
     * Writes the file in the web_dir so next time web server serve it
     * 
     * @param string $file
     * @param string $contents
     * @return null
     */
    private function writeToWebDir($file, $contents)
    {
        if (!$this->webDir) {
            return;
        }

        if (!is_writable($this->webDir)) {
            trigger_error(sprintf('Directory %s is not writeable', $this->webDir));

            return;
        }

        $destFile = $this->webDir . $file;
        $destDir  = dirname($destFile);

        if (!is_dir($destDir)) {
            mkdir($destDir);
        }

        file_put_contents($destFile, $contents);
    }
}
