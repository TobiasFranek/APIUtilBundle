<?php
namespace Tfranek\APIUtilBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Tfranek Controller
 * @author Tobias Franek <tobias.franek@gmail.com>
 * @license MIT
 */
class TfranekController extends FOSRestController 
{
    /**
     * return an Response where content will be rendered in
     * @param object|array $content
     * @param int $status
     * @param array $header
     * @return Response
     */
    protected function generateView($content, int $status = 200, array $header = []): Response
    {
        $header['content-type'] = 'application/json';
        return new Response($this->get('jms_serializer')->serialize($content, 'json', SerializationContext::create()->enableMaxDepthChecks()),
            $status,
            $header
        );
    }
}