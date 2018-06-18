<?php
namespace Tfranek\APIUtilBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * ResourceNotFoundException Exception
 * @author Tobias Franek <tobias.franek@gmail.com>
 * @license MIT
 */
class ResourceNotFoundException extends HttpException {}