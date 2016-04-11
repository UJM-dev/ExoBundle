<?php

namespace UJM\ExoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use UJM\ExoBundle\Entity\InteractionQCM;
use UJM\ExoBundle\Entity\Response;
use UJM\ExoBundle\Form\InteractionQCMType;
use UJM\ExoBundle\Form\InteractionQCMHandler;
use UJM\ExoBundle\Form\ResponseType;

/**
 * InteractionQCM controller.
 */
class InteractionQCMController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction()
    {
        $attr = $this->get('request')->attributes;
        $em = $this->get('doctrine')->getEntityManager();
        $vars = $attr->get('vars');

        $response = new Response();
        $interactionQCM = $em->getRepository('UJMExoBundle:InteractionQCM')
            ->findOneByQuestion($attr->get('interaction')->getId());

        if ($interactionQCM->getShuffle()) {
            $interactionQCM->shuffleChoices();
        } else {
            $interactionQCM->sortChoices();
        }

        $form = $this->createForm(new ResponseType(), $response);

        $vars['interactionToDisplayed'] = $interactionQCM;
        $vars['form'] = $form->createView();
        $vars['exoID'] = $attr->get('exoID');

        return $this->render('UJMExoBundle:InteractionQCM:paper.html.twig', $vars);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        $attr = $this->get('request')->attributes;
        $entity = new InteractionQCM();
        $form = $this->createForm(
           new InteractionQCMType(
               $this->container->get('security.token_storage')
                   ->getToken()->getUser()
           ), $entity
       );
        $serviceQcm = $this->container->get('ujm.exo_InteractionQCM');
        $typeQCM = $serviceQcm->getTypeQCM();

        return $this->container->get('templating')->renderResponse(
           'UJMExoBundle:InteractionQCM:new.html.twig', array(
           'exoID' => $attr->get('exoID'),
           'stepID' => $attr->get('stepID'),
           'entity' => $entity,
           'typeQCM' => json_encode($typeQCM),
           'form' => $form->createView(),
           )
       );
    }

    /**
     * Creates a new InteractionQCM entity.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction()
    {
        $services = $this->container->get('ujm.exo_InteractionQCM');
        $interQCM = new InteractionQCM();
        $form = $this->createForm(
            new InteractionQCMType(
                $this->container->get('security.token_storage')->getToken()->getUser()
            ), $interQCM
        );

        $exoID = $this->container->get('request')->request->get('exercise');
        $stepID = $this->container->get('request')->request->get('step');

        //Get the lock category
        $catSer = $this->container->get('ujm.exo_category');

        $exercise = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Exercise')->find($exoID);
        $step = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Step')->find($stepID);
        $formHandler = new InteractionQCMHandler(
            $form, $this->get('request'), $this->getDoctrine()->getManager(),
            $this->container->get('ujm.exo_exercise'), $catSer,
            $this->container->get('security.token_storage')->getToken()->getUser(), $exercise, $step,
            $this->get('translator')
        );

        $qcmHandler = $formHandler->processAdd();
        if ($qcmHandler === true) {
            $categoryToFind = $interQCM->getQuestion()->getCategory();
            $titleToFind = $interQCM->getQuestion()->getTitle();

            if ($exoID == -1) {
                return $this->redirect(
                    $this->generateUrl('ujm_question_index', array(
                        'categoryToFind' => base64_encode($categoryToFind), 'titleToFind' => base64_encode($titleToFind), )
                    )
                );
            } else {
                return $this->redirect(
                    $this->generateUrl('ujm_exercise_open', [ 'id' => $exoID ]) . '#/steps'
                );
            }
        }

        if ($qcmHandler == 'infoDuplicateQuestion') {
            $form->addError(new FormError(
                    $this->get('translator')->trans('info_duplicate_question', array(), 'ujm_exo')
                    ));
        }

        $typeQCM = $services->getTypeQCM();
        $formWithError = $this->render(
            'UJMExoBundle:InteractionQCM:new.html.twig', array(
                'entity' => $interQCM,
                'form' => $form->createView(),
                'error' => true,
                'exoID' => $exoID,
                'stepID' => $stepID,
                'typeQCM' => json_encode($typeQCM),
            )
        );
        $interactionType = $this->container->get('ujm.exo_question')->getTypes();
        $formWithError = substr($formWithError, strrpos($formWithError, 'GMT') + 3);

        return $this->render(
                'UJMExoBundle:Question:new.html.twig', array(
                'formWithError' => $formWithError,
                'exoID' => $exoID,
                'stepID' => $stepID,
                'linkedCategory' => $catSer->getLinkedCategories(),
                'locker' => $catSer->getLockCategory(),
                'interactionType' => $interactionType,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction()
    {
        $attr = $this->get('request')->attributes;
        $qcmSer = $this->container->get('ujm.exo_InteractionQCM');
        $catSer = $this->container->get('ujm.exo_category');
        $em = $this->get('doctrine')->getEntityManager();

        $interactionQCM = $em->getRepository('UJMExoBundle:InteractionQCM')
            ->findOneByQuestion($attr->get('interaction')->getId());

        //fired a sort function
        $interactionQCM->sortChoices();

        $catSer->ctrlCategory($interactionQCM->getQuestion());

        $editForm = $this->createForm(
            new InteractionQCMType(
        $attr->get('user'), $attr->get('catID')), $interactionQCM
        );

        $typeQCM = $qcmSer->getTypeQCM();
        $linkedCategory = $catSer->getLinkedCategories();

        $vars['entity'] = $interactionQCM;
        $vars['edit_form'] = $editForm->createView();
        $vars['nbResponses'] = $qcmSer->getNbReponses($attr->get('interaction'));
        $vars['linkedCategory'] = $linkedCategory;
        $vars['typeQCM'       ] = json_encode($typeQCM);
        $vars['exoID'] = $attr->get('exoID');
        $vars['locker'] = $catSer->getLockCategory();

        if ($attr->get('exoID') != -1) {
            $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($attr->get('exoID'));
            $vars['_resource'] = $exercise;
        }

        return $this->render('UJMExoBundle:InteractionQCM:edit.html.twig', $vars);
    }

    /**
     * Edits an existing InteractionQCM entity.
     *
     *
     * @param int $id id of InteractionQCM
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction($id)
    {
        $exoID = $this->container->get('request')->request->get('exercise');
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $catID = -1;

        $em = $this->getDoctrine()->getManager();

        $interQCM = $em->getRepository('UJMExoBundle:InteractionQCM')->find($id);

        if (!$interQCM) {
            throw $this->createNotFoundException('Unable to find InteractionQCM entity.');
        }

        if ($user->getId() != $interQCM->getQuestion()->getUser()->getId()) {
            $catID = $interQCM->getQuestion()->getCategory()->getId();
        }

        $editForm = $this->createForm(
            new InteractionQCMType(
                $this->container->get('security.token_storage')->getToken()->getUser(),
                $catID
            ), $interQCM
        );
        $formHandler = new InteractionQCMHandler(
            $editForm, $this->get('request'), $this->getDoctrine()->getManager(),
            $this->container->get('ujm.exo_exercise'), $this->container->get('ujm.exo_category'),
            $this->container->get('security.token_storage')->getToken()->getUser(), -1,
            $this->get('translator')
        );

        if ($formHandler->processUpdate($interQCM)) {
            if ($exoID == -1) {
                return $this->redirect($this->generateUrl('ujm_question_index'));
            } else {
                return $this->redirect(
                    $this->generateUrl('ujm_exercise_open', [ 'id' => $exoID ]) . '#/steps'
                );
            }
        }

        return $this->forward(
            'UJMExoBundle:Question:edit', array(
                'exoID' => $exoID,
                'id' => $interQCM->getQuestion()->getId(),
                'form' => $editForm,
            )
        );
    }

    /**
     * Deletes a InteractionQCM entity.
     *
     *
     * @param int    $id      id of InteractionQCM
     * @param intger $pageNow for pagination, actual page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, $pageNow)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('UJMExoBundle:InteractionQCM')->find($id);
        //Deleting of relations, if there the question is shared
        $sharesQuestion = $em->getRepository('UJMExoBundle:Share')->findBy(array('question' => $entity->getQuestion()->getId()));
        foreach ($sharesQuestion as $share){
            $em->remove($share);
        }
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InteractionQCM entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('ujm_question_index', array('pageNow' => $pageNow)));
    }

    /**
     * To test the QCM by the teacher.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function responseQcmAction()
    {
        $vars = array();
        $request = $this->get('request');
        $postVal = $req = $request->request->all();

        if ($postVal['exoID'] != -1) {
            $exercise = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Exercise')->find($postVal['exoID']);
            $vars['_resource'] = $exercise;
        }

        $interQcmSer = $this->container->get('ujm.exo_InteractionQCM');
        $res = $interQcmSer->response($request);

        $vars['score'] = $res['score'];
        $vars['penalty'] = $res['penalty'];
        $vars['interQCM'] = $res['interQCM'];
        $vars['response'] = $res['response'];
        $vars['exoID'] = $postVal['exoID'];

        return $this->render('UJMExoBundle:InteractionQCM:qcmOverview.html.twig', $vars);
    }
}
