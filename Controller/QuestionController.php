<?php

namespace UJM\ExoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\Request;
use UJM\ExoBundle\Entity\Share;

/**
 * Question controller.
 */
class QuestionController extends Controller {

    /**
     * Lists the User's Question entities.
     *
     * @EXT\Route("/index/{pageNow}/{pageNowShared}/{categoryToFind}/{titleToFind}/{resourceId}/{displayAll}",
     *              name="ujm_question_index",
     *              defaults={"pageNow" = 0,"pageNowShared" = 0,"categoryToFind"= "z", "titleToFind"= "z","resourceId"= -1, "displayAll"= 0 })
     *
     * @param int    $pageNow        for the pagination : actual page of my questions list
     * @param int    $pageNowShared  for the pagination : actual page of my shared questions list
     * @param string $categoryToFind used for pagination (for example after creating a question, go back to page contaning this question)
     * @param string $titleToFind    used for pagination (for example after creating a question, go back to page contaning this question)
     * @param int    $id             resource id if the bank has acceded by an exercise
     * @param bool   $displayAll     to use pagination or not
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($pageNow = 0, $pageNowShared = 0, $categoryToFind = '', $titleToFind = '', $resourceId = -1, $displayAll = 0) {
        if (base64_decode($categoryToFind)) {
            $categoryToFind = base64_decode($categoryToFind);
            $titleToFind = base64_decode($titleToFind);
        }
        $vars = array();
        $sharedWithMe = array();
        $shareRight = array();
        $questionWithResponse = array();
        $alreadyShared = array();

        $em = $this->getDoctrine()->getManager();

        $services = $this->container->get('ujm.exo_question');
        $paginationSer = $this->container->get('ujm.exo_pagination');

        if ($resourceId != -1) {
            $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($resourceId);
            $vars['_resource'] = $exercise;
        }

        // To paginate the result :
        $request = $this->get('request'); // Get the request which contains the following parameters :
        $page = $request->query->get('page', 1); // Get the choosen page (default 1)
        $click = $request->query->get('click', 'my'); // Get which array to fchange page (default 'my question')
        $pagerMy = $request->query->get('pagerMy', 1); // Get the page of the array my question (default 1)
        $pagerShared = $request->query->get('pagerShared', 1); // Get the pager of the array my shared question (default 1)
        $max = 10; // Max of questions per page
        // If change page of my questions array
        if ($click == 'my') {
            // The choosen new page is for my questions array
            $pagerMy = $page;
            // Else if change page of my shared questions array
        } elseif ($click == 'shared') {
            // The choosen new page is for my shared questions array
            $pagerShared = $page;
        }

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $uid = $user->getId();

        $questions = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Question')
                ->findByUser($user);

        foreach ($questions as $question) {
            $actions = $services->getActionQuestion($question);
            $questionWithResponse += $actions[0];
            $alreadyShared += $actions[1];
        }

        $shared = $em->getRepository('UJMExoBundle:Share')
                ->findBy(array('user' => $uid));

        foreach ($shared as $s) {
            $actionsS = $services->getActionShared($s);
            $sharedWithMe += $actionsS[0];
            $shareRight += $actionsS[1];
            $questionWithResponse += $actionsS[2];
        }

        if ($categoryToFind != '' && $titleToFind != '' && $categoryToFind != 'z' && $titleToFind != 'z') {
            $i = 1;
            $pos = 0;
            $temp = 0;
            foreach ($questions as $question) {
                if ($question->getCategory() == $categoryToFind) {
                    $temp = $i;
                }
                if ($question->getTitle() == $titleToFind && $temp == $i) {
                    $pos = $i;
                    break;
                }
                ++$i;
            }

            if ($pos % $max == 0) {
                $pageNow = $pos / $max;
            } else {
                $pageNow = ceil($pos / $max);
            }
        }

        if ($displayAll == 1) {
            if (count($questions) > count($shared)) {
                $max = count($questions);
            } else {
                $max = count($shared);
            }
        }

        $doublePagination = $paginationSer->doublePaginationWithIf($questions, $sharedWithMe, $max, $pagerMy, $pagerShared, $pageNow, $pageNowShared);

        $interactionsPager = $doublePagination[0];
        $pagerfantaMy = $doublePagination[1];

        $sharedWithMePager = $doublePagination[2];
        $pagerfantaShared = $doublePagination[3];

        $listExo = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Exercise')
                ->getExerciseAdmin($uid);

        $interactionType = $this->container->get('ujm.exo_question')->getTypes();

        $vars['pagerMy'] = $pagerfantaMy;
        $vars['pagerShared'] = $pagerfantaShared;
        $vars['interactions'] = $interactionsPager;
        $vars['sharedWithMe'] = $sharedWithMePager;
        $vars['questionWithResponse'] = $questionWithResponse;
        $vars['alreadyShared'] = $alreadyShared;
        $vars['shareRight'] = $shareRight;
        $vars['displayAll'] = $displayAll;
        $vars['listExo'] = $listExo;
        $vars['idExo'] = -1;
        $vars['QuestionsExo'] = 'false';
        $vars['interactionType'] = $interactionType;

        if ($request->get('qtiError')) {
            $vars['qtiError'] = $request->get('qtiError');
        }

        return $this->render('UJMExoBundle:Question:index.html.twig', $vars);
    }

    /**
     * To filter question by exercise.
     *
     * @EXT\Route("/bank/filter/{idExo}", name="ujm_question_bank_filter",defaults={"idExo"= -1})
     *
     * @param int $idExo id of exercise selected in the list to filter questions
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bankFilterAction($idExo = -1) {
        $vars = array();
        $shareRight = array();
        $questionWithResponse = array();
        $alreadyShared = array();

        $services = $this->container->get('ujm.exo_question');

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $uid = $user->getId();

        $actionQ = array();

        if ($idExo == -2) {
            $listQExo = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Question')
                    ->findByUser($user, true);
        } else {
            $exercise = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Exercise')
                    ->find($idExo);
            $listQExo = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Question')
                    ->findByExercise($exercise);
        }

        $allActions = $services->getActionsAllQuestions($listQExo, $uid);

        $actionQ = $allActions[0];
        $questionWithResponse = $allActions[1];
        $alreadyShared = $allActions[2];
        $shareRight = $allActions[4];
        $interactionType = $this->container->get('ujm.exo_question')->getTypes();

        $listExo = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Exercise')
                ->getExerciseAdmin($uid);

        $vars['interactions'] = $listQExo;
        $vars['actionQ'] = $actionQ;
        $vars['questionWithResponse'] = $questionWithResponse;
        $vars['alreadyShared'] = $alreadyShared;
        $vars['shareRight'] = $shareRight;
        $vars['displayAll'] = 0;
        $vars['listExo'] = $listExo;
        $vars['idExo'] = $idExo;
        $vars['QuestionsExo'] = 'true';
        $vars['interactionType'] = $interactionType;

        return $this->render('UJMExoBundle:Question:index.html.twig', $vars);
    }

    /**
     * Finds and displays a Question entity.
     *
     * @EXT\Route("/show/{id}/{exoID}", name="ujm_question_show", defaults={"exoID"= -1}, options={"expose"=true})
     *
     * @param int $id    id Question
     * @param int $exoID id Exercise if the user is in an exercise, -1 if the user is in the question bank
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id, $exoID) {
        $vars = array();
        $allowToAccess = 0;
        $questionSer = $this->container->get('ujm.exo_question');
        $em = $this->getDoctrine()->getManager();

        if ($exoID != -1) {
            $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exoID);
            $vars['_resource'] = $exercise;

            if ($this->container->get('ujm.exo_exercise')
                            ->isExerciseAdmin($exercise)) {
                $allowToAccess = 1;
            }
        }

        $question = $questionSer->controlUserQuestion($id);
        $sharedQuestion = $questionSer->controlUserSharedQuestion($id);

        if ($question || count($sharedQuestion) > 0 || $allowToAccess == 1) {
            $interaction = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Question')
                    ->find($id);

            return $this->forward(
                            'UJMExoBundle:' . $interaction->getType() . ':show', array('interaction' => $interaction, 'exoID' => $exoID, 'vars' => $vars)
            );
        } else {
            return $this->redirect($this->generateUrl('ujm_question_index'));
        }
    }

    /**
     * Displays a form to create a new Question entity with interaction.
     *
     * @EXT\Route(
     *     "/new/{exoID}/{stepID}/{pageToGo}/{maxPage}/{nbItem}",
     *     name="ujm_question_new",
     *     defaults={"exoID"= -1, "stepID"= -1, "pageToGo"= 1, "maxPage"= 10, "nbItem"= 1},
     *     options={"expose"=true}
     * )
     *
     * @param int $exoID id Exercise if the user is in an exercise, -1 if the user is in the question bank
     * @param int $stepID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction($exoID, $stepID) {
        $catSer = $this->container->get('ujm.exo_category');
        $variables = array(
            'exoID' => $exoID,
            'stepID' => $stepID,
            'linkedCategory' => $catSer->getLinkedCategories(),
            'locker' => $catSer->getLockCategory(),
        );

        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exoID);

        $interactionType = $this->container->get('ujm.exo_question')->getTypes();
        $variables['interactionType'] = $interactionType;

        if ($exercise) {
            $variables['_resource'] = $exercise;
        }

        return $this->render('UJMExoBundle:Question:new.html.twig', $variables);
    }

    /**
     * Creates a new Question entity.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
//    public function createAction()
//    {
//        $entity  = new Question();
//        $request = $this->getRequest();
//        $form    = $this->createForm(new QuestionType(), $entity);
//        $form->handleRequest($request);
//
//        if ($form->isValid()) {
//            $em = $this->getDoctrine()->getManager();
//            $em->persist($entity);
//            $em->flush();
//
//            return $this->redirect($this->generateUrl('question_show', array('id' => $entity->getId())));
//        }
//
//        return $this->render(
//            'UJMExoBundle:Question:new.html.twig', array(
//            'entity' => $entity,
//            'form'   => $form->createView(),
//            'linkedCategory' =>  $this->container->get('ujm.exo_question')->getLinkedCategories(),
//            'locker' => $this->getLockCategory(),
//            )
//        );
//    }

    /**
     * Displays a form to edit an existing Question entity.
     *
     * @EXT\Route(
     *     "/{id}/edit/{exoID}",
     *     name="ujm_question_edit",
     *     defaults={"exoID"= -1},
     *     options={"expose"=true}
     * )
     *
     * @param int $id    id Question
     * @param int $exoID id Exercise if the user is in an exercise, -1 if the user is in the question bank
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction($id, $exoID) {
        $services = $this->container->get('ujm.exo_question');
        $question = $services->controlUserQuestion($id);
        $share = $services->controlUserSharedQuestion($id);
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $catID = -1;

        if (count($share) > 0) {
            $shareAllowEdit = $share[0]->getAllowToModify();
            $question=$this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Question')->find($id);
        }

        if ($question || $shareAllowEdit) {

            if ($user->getId() != $question->getUser()->getId()) {
                $catID = $question->getCategory()->getId();
            }

            return $this->forward(
                            'UJMExoBundle:' . $question->getType() . ':edit', array('interaction' => $question, 'exoID' => $exoID, 'catID' => $catID, 'user' => $user)
            );
        } else {
            return $this->redirect($this->generateUrl('ujm_question_index'));
        }
    }

    /**
     * Deletes a Question entity.
     *
     * @EXT\Route(
     *     "/{id}/delete/{pageNow}/{maxPage}/{nbItem}/{lastPage}",
     *     name="ujm_question_delete",
     *     defaults={"pageNow"= 1, "maxPage"= 10, "nbItem"= 1, "lastPage"= 1},
     *     options={"expose"=true}
     * )
     *
     * @param int $id       id Question
     * @param int $pageNow  actual page for the pagination
     * @param int $maxpage  number max questions per page
     * @param int $nbItem   number of question
     * @param int $lastPage number of last page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, $pageNow, $maxPage, $nbItem, $lastPage) {
        $em = $this->getDoctrine()->getManager();
        $question = $this->container->get('ujm.exo_question')->controlUserQuestion($id);

        if ($question) {
            $eq = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:StepQuestion')
                    ->getExercises($question);

            foreach ($eq as $e) {
                $em->remove($e);
            }

            $em->flush();

            // If delete last item of page, display the previous one
            $rest = $nbItem % $maxPage;

            if ($rest == 1 && $pageNow == $lastPage) {
                $pageNow -= 1;
            }

            $interSer = $this->container->get('ujm.exo_' . $question->getType());
            $interX = $interSer->getInteractionX($question->getId());

            return $this->forward(
                            'UJMExoBundle:' . $question->getType() . ':delete', array('id' => $interX->getId(), 'pageNow' => $pageNow)
            );
        }
    }

    /**
     * Displays the rigth form when a teatcher wants to create a new Question (JS).
     *
     * @EXT\Route("/formNew", name="ujm_question_display_form_type")
     * @EXT\Method("POST")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function formNewAction() {
        $request = $this->container->get('request');

        if ($request->isXmlHttpRequest()) {
            $valType = $request->request->get('indice_type');
            $exoID = $request->request->get('exercise');
            $stepID = $request->request->get('step');

            return $this->forward(
                'UJMExoBundle:' . $valType . ':new', array(
                    'exoID' => $exoID,
                    'stepID' => $stepID,
                )
            );
        }
    }

    /**
     * To share Question.
     *
     * @EXT\Route(
     *     "/share/{questionID}",
     *     name="ujm_question_share",
     *     options={"expose"=true}
     * )
     *
     * @param int $questionID id of question
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function shareAction($questionID) {
        return $this->render(
                        'UJMExoBundle:Question:share.html.twig', array(
                    'questionID' => $questionID,
                        )
        );
    }

    /**
     * To search Question.
     *
     * @EXT\Route("/share/search/user", name="ujm_question_share_search_user")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction() {
        $request = $this->get('request');
        $paginationSer = $this->container->get('ujm.exo_pagination');

        $max = 10; // Max per page

        $search = $request->query->get('search');
        $page = $request->query->get('page');
        $questionID = $request->query->get('qId');

        if ($search != '') {
            $em = $this->getDoctrine()->getManager();
            $userList = $em->getRepository('ClarolineCoreBundle:User')->findByName($search);

            $pagination = $paginationSer->pagination($userList, $max, $page);

            $userListPager = $pagination[0];
            $pagerUserSearch = $pagination[1];

            // Put the result in a twig
            $divResultSearch = $this->render(
                    'UJMExoBundle:Question:search.html.twig', array(
                'userList' => $userListPager,
                'pagerUserSearch' => $pagerUserSearch,
                'search' => $search,
                'questionID' => $questionID,
                    )
            );

            // If request is ajax (first display of the first search result (page = 1))
            if ($request->isXmlHttpRequest()) {
                return $divResultSearch; // Send the twig with the result
            } else {
                // Cut the header of the request to only have the twig with the result
                $divResultSearch = substr($divResultSearch, strrpos($divResultSearch, '<link'));

                // Send the form to search and the result
                return $this->render(
                                'UJMExoBundle:Question:share.html.twig', array(
                            'userList' => $userList,
                            'divResultSearch' => $divResultSearch,
                            'questionID' => $questionID,
                                )
                );
            }
        } else {
            return $this->render(
                            'UJMExoBundle:Question:search.html.twig', array(
                        'userList' => '',
                            )
            );
        }
    }

    /**
     * To manage the User's documents.
     *
     * @EXT\Route("/managePic", name="ujm_question_manage_pic")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function managePicAction() {
        $allowToDel = array();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $request = $this->get('request');
        $paginationSer = $this->container->get('ujm.exo_pagination');

        $repository = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Picture');

        $listPic = $repository->findBy(array('user' => $user->getId()));

        foreach ($listPic as $pic) {
            $interGraph = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:InteractionGraphic')
                    ->findOneBy(array('id' => $pic->getId()));
            if ($interGraph) {
                $allowToDel[$pic->getId()] = false;
            } else {
                $allowToDel[$pic->getId()] = true;
            }
        }

        // Pagination of the Pictures
        $max = 10; // Max questions displayed per page

        $page = $request->query->get('page', 1); // Which page

        $pagination = $paginationSer->pagination($listPic, $max, $page);

        $listPicPager = $pagination[0];
        $pagerPic = $pagination[1];

        return $this->render(
                        'UJMExoBundle:Picture:managePic.html.twig', array(
                    'listPic' => $listPicPager,
                    'pagerPic' => $pagerPic,
                    'allowToDel' => $allowToDel,
                        )
        );
    }

    /**
     * To delete a User's Picture.
     *
     * @EXT\Route("/deleteImg/{idPic}", name="ujm_Picture_del")
     *
     * @param int $idPic id Picture
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deletePicAction($idPic) {
        $repositoryPic = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Picture');

        $pic = $repositoryPic->find($idPic);

        $em = $this->getDoctrine()->getManager();

        $interGraph = $em->getRepository('UJMExoBundle:InteractionGraphic')->findBy(array('picture' => $pic));

        if (count($interGraph) == 0) {
            $em->remove($pic);
            $em->flush();
        }

        return new \Symfony\Component\HttpFoundation\Response('Picture delete');
    }

    /**
     * To delete a User's picture linked to questions but not to paper.
     *
     * @EXT\Route("/deletelinkedPic/{label}", name="ujm_picture_del_linked")
     *
     * @param string $label label of picture
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deletelinkedPicAction($label) {
        $userId = $this->container->get('security.token_storage')->getToken()->getUser()->getId();

        $repositoryPic = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Picture');

        $listPic = $repositoryPic->findByLabel($label, $userId, 0);

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('UJMExoBundle:InteractionGraphic')->findBy(array('picture' => $listPic));

        $end = count($entity);

        for ($i = 0; $i < $end; ++$i) {
            $coords = $em->getRepository('UJMExoBundle:Coords')->findBy(array('interactionGraphic' => $entity[$i]->getId()));

            if (!$coords) {
                throw $this->createNotFoundException('Unable to find Coords link to interactiongraphic.');
            }

            $stop = count($coords);
            for ($x = 0; $x < $stop; ++$x) {
                $em->remove($coords[$x]);
            }

            $em->remove($entity[$i]);
        }

        $em->remove($listPic[0]);
        $em->flush();

        return $this->redirect($this->generateUrl('ujm_question_manage_pic'));
    }

    /**
     * To display the modal which allow to change the label of a picture.
     *
     * @EXT\Route("/change/picture/name", name="ujm_picture_change_name")
     *
     * @param int $id id of exercise
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function changePictureNameAction() {
        $request = $this->get('request'); // Get the request which contains the following parameters :
        $oldPicLabel = $request->request->get('oldPicLabel');
        $i = $request->request->get('i');

        return $this->render('UJMExoBundle:Picture:changeName.html.twig', array('oldPicLabel' => $oldPicLabel, 'i' => $i));
    }

    /**
     * To change the label of a picture.
     *
     * @EXT\Route("/update/name", name="ujm_question_name_update")
     * @EXT\Method("POST")
     *
     * @param int $id id of exercise
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateNameAction() {
        $request = $this->container->get('request');
        $newlabel = $request->get('newlabel');
        $oldlabel = $request->get('oldName');
        $em = $this->getDoctrine()->getManager();

        $alterPic = $em->getRepository('UJMExoBundle:Picture')->findOneBy(array('label' => $oldlabel));

        $alterPic->setLabel($newlabel);

        $em->persist($alterPic);
        $em->flush();

        return new \Symfony\Component\HttpFoundation\Response($newlabel);
    }

    /**
     * To sort picture by type.
     *
     * @EXT\Route("/sort", name="ujm_question_sortPictures")
     *
     * @param int $id id of exercise
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sortPicturesAction() {
        $request = $this->container->get('request');
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $paginationSer = $this->container->get('ujm.exo_pagination');

        $max = 10; // Max per page

        $type = $request->query->get('picType');
        $searchLabel = $request->query->get('searchLabel');
        $page = $request->query->get('page');

        if ($type && isset($searchLabel)) {
            $repository = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Picture');

            $listPicSort = $repository->findByType($type, $user->getId(), $searchLabel);

            $pagination = $paginationSer->pagination($listPicSort, $max, $page);

            $listPicSortPager = $pagination[0];
            $pagerSortPic = $pagination[1];

            // Put the result in a twig
            $divResultSearch = $this->render(
                    'UJMExoBundle:Picture:sortPic.html.twig', array(
                'listFindPic' => $listPicSortPager,
                'pagerFindPic' => $pagerSortPic,
                'labelToFind' => $searchLabel,
                'whichAction' => 'sort',
                'picType' => $type,
                    )
            );

            // If request is ajax (first display of the first search result (page = 1))
            if ($request->isXmlHttpRequest()) {
                return $divResultSearch; // Send the twig with the result
            } else {
                // Cut the header of the request to only have the twig with the result
                $divResultSearch = substr($divResultSearch, strrpos($divResultSearch, '<table'));

                // Send the form to search and the result
                return $this->render(
                                'UJMExoBundle:Picture:managePic.html.twig', array(
                            'divResultSearch' => $divResultSearch,
                                )
                );
            }
        } else {
            return $this->render(
                            'UJMExoBundle:Picture:sortPic.html.twig', array(
                        'listFindPic' => '',
                        'whichAction' => 'sort',
                            )
            );
        }
    }

    /**
     * To search picture with a defined label.
     *
     * @EXT\Route("/search", name="ujm_question_searchPictures")
     *
     * @param int $id id of exercise
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchPicAction() {
        $userId = $this->container->get('security.token_storage')->getToken()->getUser()->getId();
        $request = $this->get('request');
        $paginationSer = $this->container->get('ujm.exo_pagination');

        $max = 10; // Max per page

        $labelToFind = $request->query->get('labelToFind');
        $page = $request->query->get('page');

        if ($labelToFind) {
            $em = $this->getDoctrine()->getManager();
            $listFindPic = $em->getRepository('UJMExoBundle:Picture')->findByLabel($labelToFind, $userId, 1);

            $pagination = $paginationSer->pagination($listFindPic, $max, $page);

            $listFindPicPager = $pagination[0];
            $pagerFindPic = $pagination[1];

            // Put the result in a twig
            $divResultSearch = $this->render(
                    'UJMExoBundle:Picture:sortPic.html.twig', array(
                'listFindPic' => $listFindPicPager,
                'pagerFindPic' => $pagerFindPic,
                'labelToFind' => $labelToFind,
                'whichAction' => 'search',
                    )
            );

            // If request is ajax (first display of the first search result (page = 1))
            if ($request->isXmlHttpRequest()) {
                return $divResultSearch; // Send the twig with the result
            } else {
                // Cut the header of the request to only have the twig with the result
                $divResultSearch = substr($divResultSearch, strrpos($divResultSearch, '<table'));

                // Send the form to search and the result
                return $this->render(
                                'UJMExoBundle:Picture:managePic.html.twig', array(
                            'divResultSearch' => $divResultSearch,
                                )
                );
            }
        } else {
            return $this->render(
                            'UJMExoBundle:Picture:sortPic.html.twig', array(
                        'listFindPic' => '',
                        'whichAction' => 'search',
                            )
            );
        }
    }

    /**
     * To share question with other users.
     *
     * @EXT\Route("/shareQuestionUser", name="ujm_question_shareQuestionUser")
     * @EXT\Method("POST")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function shareQuestionUserAction() {
        $request = $this->container->get('request');

        if ($request->isXmlHttpRequest()) {
            $questionID = $request->request->get('questionID'); // Which question is shared

            $uid = $request->request->get('uid');
            $allowToModify = $request->request->get('allowToModify');

            $em = $this->getDoctrine()->getManager();

            $question = $em->getRepository('UJMExoBundle:Question')->findOneBy(array('id' => $questionID));
            $user = $em->getRepository('ClarolineCoreBundle:User')->find($uid);

            $share = $em->getRepository('UJMExoBundle:Share')->findOneBy(array('user' => $user, 'question' => $question));

            if (!$share) {
                $share = new Share($user, $question);
            }

            $share->setAllowToModify($allowToModify);

            $em->persist($share);
            $em->flush();

            return new \Symfony\Component\HttpFoundation\Response('no;' . $this->generateUrl('ujm_question_index'));
        }
    }

    /**
     * If question already shared with a given user.
     *
     *
     * @param \UJM\ExoBundle\Entity\Share $toShare
     * @param Doctrine Entity Manager     $em
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
//    public function alreadySharedAction($toShare, $em)
//    {
//        $alreadyShared = $em->getRepository('UJMExoBundle:Share')->findAll();
//        $already = false;
//
//        $end = count($alreadyShared);
//
//        for ($i = 0; $i < $end; ++$i) {
//            if ($alreadyShared[$i]->getUser() == $toShare->getUser() &&
//                $alreadyShared[$i]->getQuestion() == $toShare->getQuestion()
//            ) {
//                $already = true;
//                break;
//            }
//        }
//
//        if ($already == true) {
//            return true;
//        } else {
//            return false;
//        }
//    }

    /**
     * Display form to search questions.
     *
     * @EXT\Route("/SearchQuestion/{exoID}", name="ujm_question_search_question", defaults={"exoID"= -1})
     *
     * @param int $exoID id of exercise
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchQuestionAction($exoID) {
        return $this->render('UJMExoBundle:Question:searchQuestion.html.twig', array(
                    'exoID' => $exoID,
                        )
        );
    }

    /**
     * Display the questions matching to the research.
     *
     * @EXT\Route("/SearchQuestionType", name="ujm_question_searchQuestion")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchQuestionTypeAction(Request $request) {
        $paginationSer = $this->container->get('ujm.exo_pagination');
        $searchSer = $this->container->get('ujm.exo_search_question');
        $type = $request->query->get('type'); // In which column
        $whatToFind = $request->query->get('whatToFind'); // Which text to find
        $where = $request->query->get('where'); // In which database
        $exoID = $request->query->get('exoID'); // If we import or see the questions
        $displayAll = $request->query->get('displayAll', 0); // If we want to have all the questions in one page
        $questionWithResponse='';
        $alreadyShared='';
        // If what and where to search is defined
        if ($type && $whatToFind && $where) {
            // Get the matching questions depending on :
            //  * in which database search,
            //  * in witch column
            //  * and what text to search
            // User's database
            if ($where == 'my') {
                $listQuestions = $searchSer->choiceTypeQuestion('Question');
                // For all the matching questions search if ...
                $questionWithResponse = $searchSer->searchEntityResponse($listQuestions, 'Response'); //question With Response
                $alreadyShared = $searchSer->searchEntityResponse($listQuestions, 'Share'); //already Shared
                // Shared with user's database
            } elseif ($where == 'shared') {
                $listeSharedQuestion = $searchSer->choiceTypeQuestion('Share');
                $listQuestions = $searchSer->listQuestion($listeSharedQuestion);
                $pagination = $paginationSer->paginationSearchQuestion($listQuestions);

            } elseif ($where == 'all') {
                $listeSharedQuestion = $searchSer->choiceTypeQuestion('Share');
                $listQuestionsShare= $searchSer->listQuestion($listeSharedQuestion);
                $listQuestionsMy = $searchSer->choiceTypeQuestion('Question');
                $listQuestions = array_merge($listQuestionsShare, $listQuestionsMy);
                // For all the matching interactions search if ...
                $questionWithResponse = $searchSer->searchEntityResponse($listQuestions, 'Response'); //question With Response
                $alreadyShared = $searchSer->searchEntityResponse($listQuestions, 'Share'); //already Shared
            }
             $pagination = $paginationSer->paginationSearchQuestion($listQuestions);

                $vars['listQuestions'] = $pagination[0];
                $vars['pagerSearch'] = $pagination[1];
                $vars['exoID'] = $exoID;
                $vars['canDisplay'] = $where;
                $vars['type'] = $type;
                $vars['whatToFind'] = $whatToFind;
                $vars['displayAll'] = $displayAll;
                if ($exoID == -1) {
                    $vars['questionWithResponse'] = $questionWithResponse;
                    $vars['alreadyShared'] = $alreadyShared;
                    $divResultSearch = $this->render('UJMExoBundle:Question:SearchQuestionType.html.twig', $vars);
                } else {
                    $divResultSearch = $this->render('UJMExoBundle:Question:searchQuestionImport.html.twig', $vars);
                }
            if ($request->isXmlHttpRequest()) {
                    return $divResultSearch; // Send the twig with the result
                } else {
                    // Cut the header of the request to only have the twig with the result
                    $divResultSearch = substr($divResultSearch, strrpos($divResultSearch, '<link'));

                    // Send the form to search and the result
                    return $this->render(
                                    'UJMExoBundle:Question:searchQuestion.html.twig', array(
                                'divResultSearch' => $divResultSearch,
                                'exoID' => $exoID,
                                    )
                    );
                }
        } else {
            return $this->render(
                            'UJMExoBundle:Question:SearchQuestionType.html.twig', array(
                        'listQuestions' => '',
                        'canDisplay' => $where,
                        'whatToFind' => $whatToFind,
                        'type' => $type,
                            )
            );
        }
    }

    /**
     * To delete the shared question of user's questions bank.
     *
     * @EXT\Route("/{qid}/{uid}/delete/{pageNow}/{maxPage}/{nbItem}/{lastPage}", name="ujm_delete_shared_question",
     *              defaults={"pageNow"= 1, "maxPage"= 10, "nbItem"= 1, "lastPage"= 1})
     *
     * @param int $qid      id Question
     * @param int $uid      id User, user connected
     * @param int $pageNow  actual page for the pagination
     * @param int $maxPage  number max questions per page
     * @param int $nbItem   number of question
     * @param int $lastPage number of last page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteSharedQuestionAction($qid, $uid, $pageNow, $maxPage, $nbItem, $lastPage) {
        $em = $this->getDoctrine()->getManager();
        $sharedToDel = $em->getRepository('UJMExoBundle:Share')->findOneBy(array('question' => $qid, 'user' => $uid));

        if (!$sharedToDel) {
            throw $this->createNotFoundException('Unable to find Share entity.');
        }

        $em->remove($sharedToDel);
        $em->flush();

        // If delete last item of page, display the previous one
        $rest = $nbItem % $maxPage;

        if ($rest == 1 && $pageNow == $lastPage) {
            $pageNow -= 1;
        }

        return $this->redirect($this->generateUrl('ujm_question_index', array('pageNowShared' => $pageNow)));
    }

    /**
     * To see with which person the user has shared his question.
     *
     * @EXT\Route("/seeShared/{id}", name="ujm_see_shared")
     *
     * @param int $id id of question
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function seeSharedWithAction($id) {
        $em = $this->getDoctrine()->getManager();
        $questionsharedWith = $em->getRepository('UJMExoBundle:Share')->findBy(array('question' => $id));

        $sharedWith = array();
        $stop = count($questionsharedWith);

        for ($i = 0; $i < $stop; ++$i) {
            $sharedWith[] = $em->getRepository('ClarolineCoreBundle:User')->find($questionsharedWith[$i]->getUser()->getId());
        }

        return $this->render(
                        'UJMExoBundle:Question:seeSharedWith.html.twig', array(
                    'sharedWith' => $sharedWith,
                        )
        );
    }

    /**
     * To search questions brief in the question bank.
     *
     * @EXT\Route("/search/brief", name="ujm_question_briefSearch")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function briefSearchAction() {
        $em = $this->getDoctrine()->getManager();
        $questionRepository = $em->getRepository('UJMExoBundle:Question');
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $request = $this->get('request');
        $services = $this->container->get('ujm.exo_question');

        $userSearch = $request->request->get('userSearch');
        $exoID = $request->request->get('exoID');
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exoID);
        $where = $request->request->get('where');

        $listQuestions = $questionRepository->findByUserAndContent($user, $userSearch, $exercise);
        $sharedQuestion = $em->getRepository('UJMExoBundle:Share')
                ->findByUserAndContent($user, $userSearch);

        $end = count($sharedQuestion);

        for ($i = 0; $i < $end; $i++) {
            $listQuestions[] = $sharedQuestion[$i]->getQuestion();
        }

        $allActions = $services->getActionsAllQuestions($listQuestions, $user->getId());

        $actionQ = $allActions[0];
        $questionWithResponse = $allActions[1];
        $alreadyShared = $allActions[2];
        $shareRight = $allActions[4];
        $interactionType = $this->container->get('ujm.exo_question')->getTypes();

        $listExo = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Exercise')
                ->getExerciseAdmin($user->getId());

        $vars['interactions'] = $listQuestions;
        $vars['actionQ'] = $actionQ;
        $vars['questionWithResponse'] = $questionWithResponse;
        $vars['alreadyShared'] = $alreadyShared;
        $vars['shareRight'] = $shareRight;
        $vars['listExo'] = $listExo;
        $vars['idExo'] = -1;
        $vars['displayAll'] = 0;
        $vars['QuestionsExo'] = 'true';
        $vars['interactionType'] = $interactionType;

        if ($where == 'index') {
            return $this->render('UJMExoBundle:Question:index.html.twig', $vars);
        } else {
            $em = $this->getDoctrine()->getManager();
            $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exoID);

            $workspace = $exercise->getResourceNode()->getWorkspace();

            $vars['workspace'] = $workspace;
            $vars['_resource'] = $exercise;
            $vars['exoID'] = $exoID;
            $vars['pageToGo'] = 1;

            return $this->render('UJMExoBundle:Question:import.html.twig', $vars);
        }
    }

    /**
     * To duplicate a question.
     *
     * @EXT\Route(
     *     "/duplicate/{questionId}/{stepID}",
     *     name="ujm_question_duplicate",
     *     defaults={"stepID"= -1},
     *     options={"expose"= true}
     * )
     *
     * @param int $questionId   id Question
     * @param int $stepID        id step if the user is in an exercise, -1 if the user is in the question bank
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function duplicateAction($questionId, $stepID) {
        $exercise = null;
        $service = $this->container->get('ujm.exo_question');

        $question = $service->controlUserQuestion($questionId);
        $sharedQuestions = $service->controlUserSharedQuestion($questionId);
        $allowToAccess = false;
        $step = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Step')->find($stepID);
        $exercise = $step->getExercise();

        if ($stepID != -1) {
            if ($this->container->get('ujm.exo_exercise')
                            ->isExerciseAdmin($exercise) === true) {
                $allowToAccess = true;
            }
        }
        if (count($question) > 0 || $allowToAccess === TRUE || count($sharedQuestions) > 0) {
            if (count($sharedQuestions) > 0) {
                $question = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Question')->find($questionId);
            }
            $type = $question->getType();

            $handlerType = '\UJM\ExoBundle\Form\\' . $type . 'Handler';

            $interactionX = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:' . $type)
                    ->findOneByQuestion($question);

            $interXHandler = new $handlerType(
                    null, null, $this->getDoctrine()->getManager(),
                    $this->container->get('ujm.exo_exercise'), $this->container->get('ujm.exo_category'),
                    $this->container->get('security.token_storage')->getToken()->getUser(),
                    $exercise,$step,$this->get('translator')
            );

            $interXHandler->singleDuplicateInter($interactionX);

            $categoryToFind = $interactionX->getQuestion()->getCategory();
            $titleToFind = $interactionX->getQuestion()->getTitle();

            if ($stepID == -1) {
                return $this->redirect(
                    $this->generateUrl('ujm_question_index', array(
                        'categoryToFind' => base64_encode($categoryToFind), 'titleToFind' => base64_encode($titleToFind),)
                    )
                );
            } else {
                return $this->redirect(
                    $this->generateUrl('ujm_exercise_open', [ 'id' => $exercise->getId() ]) . '#/steps'
                );
            }
        } else {
            return $this->redirect($this->generateUrl('ujm_question_index'));
        }
    }

}
