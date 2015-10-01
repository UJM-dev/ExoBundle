<?php

namespace UJM\ExoBundle\Controller\Sequence;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use UJM\ExoBundle\Entity\Exercise;
use UJM\ExoBundle\Entity\Question;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * CorrectionController.
 */
class CorrectionController extends Controller
{

    /**
     * Get all papers for an exercise
     * @Route("/exercises/{id}", requirements={"id" = "\d+"}, name="ujm_exercice_papers", options={"expose"=true})
     * @Method("GET")
     */
    public function getExercisePapers(Exercise $exercise)
    {
        // get user
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        // FAKE Data for development!!
        $papers = array(    
                
                array(
                    'id' => '2',
                    'exerciceId' => $exercise->getId(),
                    'user' => $user->getUsername(),
                    'number' => 1,
                    'start' => '22/09/2015 - 09h18m01s ',
                    'end' => '22/09/2015 - 09h31m46s',
                    'interrupted' => false,
                    'score' => '0/20'
                ),
                array(
                    'id' => '7',                    
                    'exerciceId' => $exercise->getId(),
                    'user' => $user->getUsername(),
                    'number' => 2,
                    'start' => '28/09/2015 - 13h38m52s',
                    'end' => '',
                    'interrupted' => true,
                    'score' => ''
                )
         
        ); 
        $data = json_encode($papers);

        return $this->render('UJMExoBundle:Sequence:papers.html.twig', array(
                    '_resource' => $exercise,
                    'papers' => $data
            )
        );
    }

   

}
