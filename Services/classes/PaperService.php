<?php

/**
 *
 * Services for the paper
 */

namespace UJM\ExoBundle\Services\classes;

use Doctrine\Bundle\DoctrineBundle\Registry;
use \Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use \UJM\ExoBundle\Entity\Paper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PaperService {

    private $doctrine;
    private $container;


    /**
     * Constructor
     *
     * @access public
     *
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine Dependency Injection;
     * @param \Symfony\Component\DependencyInjection\Container $container
     *
     */
    public function __construct(Registry $doctrine, Container $container)
    {
        $this->doctrine = $doctrine;
        $this->container = $container;
    }

    /**
     * Get IP client
     *
     * @access public
     * @param Request $request
     *
     * @return IP Client
     */
    public function getIP(Request $request)
    {

        return $request->getClientIp();
    }

    /**
     * Get total score for an paper
     *
     * @access public
     *
     * @param integer $paperID id Paper
     *
     * @return float
     */
    public function getPaperTotalScore($paperID)
    {
        $em = $this->doctrine->getManager();
        $exercisePaperTotalScore = 0;
        $paper = $interaction = $em->getRepository('UJMExoBundle:Paper')
                                   ->find($paperID);

        $interQuestions = $paper->getOrdreQuestion();
        $interQuestions = substr($interQuestions, 0, strlen($interQuestions) - 1);
        $interQuestionsTab = explode(";", $interQuestions);

        foreach ($interQuestionsTab as $interQuestion) {
            $interaction = $em->getRepository('UJMExoBundle:Interaction')->find($interQuestion);
            $interSer        = $this->container->get('ujm.exo_' . $interaction->getType());
            $interactionX    = $interSer->getInteractionX($interaction->getId());
            $exercisePaperTotalScore += $interSer->maxScore($interactionX);
        }

        return $exercisePaperTotalScore;
    }

    /**
     * To round up and down a score
     *
     * @access public
     *
     * @param float $toBeAdjusted
     *
     * @return float
     */
    public function roundUpDown($toBeAdjusted)
    {
        return (round($toBeAdjusted / 0.5) * 0.5);
    }

    /**
     * Get informations about a paper response, maxExoScore, scorePaper, scoreTemp (all questions graphiced or no)
     *
     * @access public
     *
     * @param \UJM\ExoBundle\Entity\Paper\paper $paper
     *
     * @return array
     */
    public function getInfosPaper($paper)
    {
        $infosPaper = array();
        $scorePaper = 0;
        $scoreTemp = false;

        $interactions = $this->getInteractions($paper->getOrdreQuestion());
        $interactionsSorted = $this->sortInteractions($interactions, $paper->getOrdreQuestion());
        $infosPaper['interactions'] = $interactionsSorted;

        $responses = $this->getResponses($paper->getId());
        $responsesSorted = $this->sortResponses($responses, $paper->getOrdreQuestion());
        $infosPaper['responses'] = $responsesSorted;

        $infosPaper['maxExoScore'] = $this->getPaperTotalScore($paper->getId());

        foreach ($responses as $response) {
            if ($response->getMark() != -1) {
                $scorePaper += $response->getMark();
            } else {
                $scoreTemp = true;
            }
        }

        $infosPaper['scorePaper'] = $scorePaper;
        $infosPaper['scoreTemp'] = $scoreTemp;

        return $infosPaper;
    }

    /**
     * sort the array of interactions in the order recorded for the paper
     *
     * @access private
     *
     * @param Collection of \UJM\ExoBundle\Entity\Interaction $interactions
     * @param String $order
     *
     * @return UJM\ExoBundle\Entity\Interaction[]
     */
    private function sortInteractions($interactions, $order)
    {
        $inter = array();
        $order = substr($order, 0, strlen($order) - 1);
        $order = explode(';', $order);

        foreach ($order as $interId) {
            foreach ($interactions as $key => $interaction) {
                if ($interaction->getId() == $interId) {
                    $inter[] = $interaction;
                    unset($interactions[$key]);
                    break;
                }
            }
        }

        return $inter;
    }

    /**
     * sort the array of responses to match the order of questions
     *
     * @access private
     *
     * @param Collection of \UJM\ExoBundle\Entity\Response $responses
     * @param String $order
     *
     * @return UJM\ExoBundle\Entity\Response[]
     */
    private function sortResponses($responses, $order)
    {
        $resp = array();
        $order = $this->formatQuestionOrder($order);
        foreach ($order as $interId) {
            $tem = 0;
            foreach ($responses as $key => $response) {
                if ($response->getInteraction()->getId() == $interId) {
                    $tem++;
                    $resp[] = $response;
                    unset($responses[$key]);
                    break;
                }
            }
            //if no response
            if ($tem == 0) {
                $response = new \UJM\ExoBundle\Entity\Response();
                $response->setResponse('');
                $response->setMark(0);

                $resp[] = $response;
            }
        }

        return $resp;
    }

    /**
     *
     * @access private
     *
     * @param String $order
     *
     * Return \UJM\ExoBundle\Interaction[]
     */
    private function getInteractions($orderQuestion)
    {
        $em = $this->doctrine->getManager();

        $interactions = $em->getRepository('UJMExoBundle:Interaction')
                           ->getPaperInteraction($em, str_replace(';', '\',\'', substr($orderQuestion, 0, -1)));

        return $interactions;
    }

    /**
     *
     * @access private
     *
     * @param integer $paperId
     *
     * Return \UJM\ExoBundle\Entity\Interaction[]
     */
    private function getResponses($paperId)
    {
        $em = $this->doctrine->getManager();

        $responses = $em->getRepository('UJMExoBundle:Response')
                        ->getPaperResponses($paperId);

        return $responses;
    }

    /**
     *
     * @access private
     *
     * @param String $order
     *
     * Return integer[];
     */
    private function formatQuestionOrder($orderOrig)
    {
        $order = substr($orderOrig, 0, strlen($orderOrig) - 1);
        $orderFormated = explode(';', $order);

        return $orderFormated;
    }
    
    /**
     * To create new paper
     *
     * @access public
     *
     * @param integer $id id of exercise
     * @Param \UJM\ExoBundle\Entity\Exercise $exercise
     *
     * @return array
     */
    public function prepareInteractionsPaper($id,$exercise) {
        $orderInter = '';
        $tabOrderInter = array();
        $tab = array();

        $interactions = $this->doctrine->getManager()
                ->getRepository('UJMExoBundle:Interaction')
                ->getExerciseInteraction(
                $this->doctrine->getManager(), $id, $exercise->getShuffle(), $exercise->getNbQuestion()
        );

        foreach ($interactions as $interaction) {
            $orderInter = $orderInter . $interaction->getId() . ';';
            $tabOrderInter[] = $interaction->getId();
        }

        $tab['interactions'] = $interactions;
        $tab['orderInter'] = $orderInter;
        $tab['tabOrderInter'] = $tabOrderInter;
        return $tab;
    }
    
        /**
     * For the navigation in a paper
     * Finds and displays the question selectionned by the User in an assesment
     *
     * @access public
     *
     * @param integer $numQuestionToDisplayed position of the question in the paper
     * @param \UJM\ExoBundle\Entity\Interaction $interactionToDisplay interaction (question) to displayed
     * @param String $typeInterToDisplayed
     * @param boolean $dispButtonInterrupt to display or no the button "Interrupt"
     * @param integer $maxAttempsAllowed the number of max attemps allowed for the exercise
     * @param Claroline workspace $workspace
     * @param \UJM\ExoBundle\Entity\Paper $paper current paper
     * @param SessionInterface session
     *
     * @return Array
     */
    public function displayQuestion(
        $numQuestionToDisplayed, $interactionToDisplay,
        $typeInterToDisplayed, $dispButtonInterrupt, $maxAttempsAllowed,
        $workspace,Paper $paper,SessionInterface $session
    )
    {
        $tabOrderInter = $session->get('tabOrderInter');

        $interSer       = $this->container->get('ujm.exo_' .  $interactionToDisplay->getType());
        $interactionToDisplayed = $interSer->getInteractionX($interactionToDisplay->getId());
        $responseGiven  = $interSer->getResponseGiven($interactionToDisplay, $session, $interactionToDisplayed);

        $array['workspace']              = $workspace;
        $array['tabOrderInter']          = $tabOrderInter;
        $array['interactionToDisplayed'] = $interactionToDisplayed;
        $array['interactionType']        = $typeInterToDisplayed;
        $array['numQ']                   = $numQuestionToDisplayed;
        $array['paper']                  = $session->get('paper');
        $array['numAttempt']             = $paper->getNumPaper();
        $array['response']               = $responseGiven;
        $array['dispButtonInterrupt']    = $dispButtonInterrupt;
        $array['maxAttempsAllowed']      = $maxAttempsAllowed;
        $array['_resource']              = $paper->getExercise();

        return $array;
    }
     /**
     * To finish an assessment
     *
     * @access public
     *
     * @param Symfony\Component\HttpFoundation\Session\SessionInterface  $session
     *
     * @return \UJM\ExoBundle\Entity\Paper
     */
     public function finishExercise(SessionInterface $session)
    {
        $em = $this->doctrine->getManager();
        /** @var \UJM\ExoBundle\Entity\Paper $paper */
        $paper = $em->getRepository('UJMExoBundle:Paper')->find($session->get('paper'));
        $paper->setInterupt(0);
        $paper->setEnd(new \Datetime());
        $em->persist($paper);
        $em->flush();

        $this->container->get('ujm.exo_exercise')->manageEndOfExercise($paper);

        $session->remove('penalties');

        return $paper;
    }
    
    /**
     * To force finish an assessment
     *
     * @access public
     *
     * @param \UJM\ExoBundle\Entity\Paper $paperToClose
     *
     * @return \UJM\ExoBundle\Entity\Paper
     */
    public function forceFinishExercise($paperToClose)
    {
        $em = $this->doctrine->getManager();
        /** @var \UJM\ExoBundle\Entity\Paper $paper */
        $paper = $paperToClose;
        $paper->setInterupt(0);
        $paper->setEnd(new \Datetime());
        $em->persist($paper);
        $em->flush();

        $this->container->get('ujm.exo_exercise')->manageEndOfExercise($paper);

        return $paper;
    }

    /**
     * To interupt an assessment
     *
     * @access public
     * 
     * @param SessionInterface session
     *
     * @return \UJM\ExoBundle\Entity\Paper
     */
    public function interuptExercise(SessionInterface $session)
    {   
        $em = $this->doctrine->getManager();
        $paper = $em->getRepository('UJMExoBundle:Paper')->find($session->get('paper'));
        $em->setInterupt(1);
        $em->persist($paper);
        $em->flush();

        return $paper;
    }
}
