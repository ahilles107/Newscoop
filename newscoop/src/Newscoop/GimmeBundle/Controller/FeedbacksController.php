<?php
/**
 * @package Newscoop\Gimme
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\GimmeBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
use Newscoop\Entity\Attachment;
use Newscoop\Entity\Feedback;
use Newscoop\Entity\User;
use Newscoop\GimmeBundle\Form\Type\FeedbackType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Feedbacks controller
 */
class FeedbacksController extends FOSRestController
{
    /**
     * Get all feedbacks
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         404={
     *           "Returned when the feedbacks are not found"
     *         }
     *     }
     * )
     *
     * @Route("/feedbacks.{_format}", defaults={"_format"="json"})
     * @Method("GET")
     * @View(serializerGroups={"list"})
     *
     * @return array
     */
    public function getFeedbacksAction(Request $request)
    {
        $em = $this->container->get('em');

        $attachments = $em->getRepository('Newscoop\Entity\Attachment')
            ->getAttachments();

        $paginator = $this->get('newscoop.paginator.paginator_service');
        $attachments = $paginator->paginate($attachments, array(
            'distinct' => false
        ));

        return $attachments;
    }

    /**
     * Create new feedback
     *
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when feedback created succesfuly"
     *     },
     *     input="\Newscoop\GimmeBundle\Form\Type\FeedbackType"
     * )
     *
     * @Route("/feedbacks.{_format}", defaults={"_format"="json"})
     * @Method("POST")
     * @View()
     *
     * @return Form
     */
    public function createFeedbackAction(Request $request)
    {
        return $this->processForm($request);
    }

    /**
     * Delete feedback
     *
     * @ApiDoc(
     *     statusCodes={
     *         204="Returned when attachment removed succesfuly",
     *         404={
     *           "Returned when the attachment is not found",
     *         }
     *     },
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Attachment id"}
     *     }
     * )
     *
     * @Route("/feedbacks/{number}.{_format}", defaults={"_format"="json"})
     * @Method("DELETE")
     * @View(statusCode=204)
     *
     * @return Form
     */
    public function deleteFeedbackAction(Request $request, $number)
    {
        $attachmentService = $this->container->get('attachment');
        $em = $this->container->get('em');
        $attachment = $em->getRepository('Newscoop\Entity\Attachment')->findOneById($number);

        if (!$attachment) {
            throw new EntityNotFoundException('Result was not found.');
        }

        $attachmentService->remove($attachment);
    }

    /**
     * Process attachment form
     *
     * @param Request $request
     *
     * @return Form
     */
    private function processForm($request)
    {
        $em = $this->container->get('em');
        $feedbackRepository = $em->getRepository('Newscoop\Entity\Feedback');
        $attachmentService = $this->container->get('attachment');
        $publicationService = $this->container->get('newscoop.publication_service');
        $statusCode = 201;

        $form = $this->createForm(new FeedbackType(), array());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $file = $form['attachment']->getData();
            $attributes = $form->getData();
            $user = $this->getUser();

            if ($user) {
                $attributes['user'] = $user;
            }
            $attributes['publication'] = $publicationService->getPublication()->getId();

            $feedback = $feedbackRepository->save(new Feedback(), $attributes);
            $em->flush();
            ladybug_dump_die($file, $attributes, $user, $feedback);

            $response = new Response();
            $response->setStatusCode($statusCode);

            $response->headers->set(
                'X-Location',
                $this->generateUrl('newscoop_gimme_attachments_getattachment', array(
                    'number' => $attachment->getId(),
                ), true)
            );

            return $response;
        }

        return $form;
    }
}
