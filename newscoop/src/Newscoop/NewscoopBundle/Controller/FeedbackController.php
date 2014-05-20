<?php
/**
 * @package Newscoop\NewscoopBundle
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\NewscoopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Newscoop\Entity\Feedback;

class FeedbackController extends Controller
{
    /**
     * @Route("/admin/feedbacks")
     * @Template()
     */
    public function indexAction(Request $request)
    {
    }

    /**
     * @Route("/admin/feedbacks/update")
     * @Template()
     */
    public function updateAction(Request $request)
    {
        ladybug_dump_die($request->request);
    }

    /**
     * @Route("/admin/feedbacks/load", options={"expose"=true})
     */
    public function loadFeedbacksAction(Request $request)
    {
        $em = $this->get('em');
        $paginator = $this->get('knp_paginator');

        $allFeedbacks = $em->getRepository('Newscoop\Entity\Feedback')
            ->getAllFeedbacks();

        $allFeedbacks = $paginator->paginate($allFeedbacks, 1, 20);

        $processedData = array();
        foreach ($allFeedbacks as $feedback) {
            $processedData[] = $this->processFeedback($feedback, $request);
        }

        return new Response(json_encode(array(
            'records' => $processedData,
            'queryRecordCount' => 1,
            'totalRecordCount'=> 1
        )));
    }

    private function processFeedback(Feedback $feedback, $request)
    {
        $em = $this->get('em');
        $zendRouter = \Zend_Registry::get('container')->getService('zend_router');
        $translator = \Zend_Registry::get('container')->getService('translator');

        $data = array(
            'id' => $feedback->getId(),
            'date' => $feedback->getTimeCreated(),
            'message' => $feedback->getMessage(),
            'subject' => $feedback->getSubject(),
            'publication' => $feedback->getPublication()->getName(),
            'section' => '',
            'article' => '',
            'url' => $feedback->getUrl(),
            'status' => $feedback->getStatus()
        );

        if ($feedback->getSection()) {
            $data['section'] = $feedback->getSection()->getName();
        } else {
            $data['section'] = $translator->trans("empty");
        }

        if ($feedback->getArticle()) {
            $data['article'] = $feedback->getArticle()->getName();
        } else {
            $data['article'] = $translator->trans("empty");
        }

        if ($feedback->getUser()) {
            $acceptanceRepository = $em->getRepository('Newscoop\Entity\Comment\Acceptance');

            $banned = $acceptanceRepository->checkBanned(array(
                'name' => $feedback->getUser()->getName(),
                'email' => '', 'ip' => ''
            ), $publication);

            if ($banned['name'] == true) {
                $banned = true;
            } else {
                $banned = false;
            }

            $data['user'] = array(
                'name' => $feedback->getUser()->getFirstName(),
                'image' => $feedback->getUser()->getImage(),
                'is_banned' => $banned,
                'user_url' => $this->container->get('request')->getUriForPath($zendRouter->assemble(array_merge(array(
                    'module' => 'default',
                    'controller' => 'user',
                    'action' => 'profile',
                )), 'default', true).'/'.strip_tags($feedback->getUser()->getUsername())),
                'banurl' => $this->container->get('request')->getUriForPath($zendRouter->assemble(array(
                    'controller' => 'user',
                    'action' => 'toggle-ban',
                    'user' => $feedback->getUser()->getId(),
                    'publication' => $feedback->getPublication()->getId()
                ), 'admin', true)),
                'username' => strip_tags($feedback->getUser()->getUsername()),
                'email' => $feedback->getUser()->getEmail(),
            );
        } else {
            $data['user'] = 'Anonymous';
        }

        return $data;
    }
}
