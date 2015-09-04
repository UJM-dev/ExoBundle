<?php

/**
 * To export a QCM question in QTI
 *
 */

namespace UJM\ExoBundle\Services\classes\QTI;

class QcmExport extends QtiExport
{

    private $interactionqcm;
    private $choiceInteraction;
    protected $resources_node;
    private $correctResponse;
    private $responseProcessing;

    /**
     * Implements the abstract method
     *
     * @access public
     * @param \UJM\ExoBundle\Entity\Interaction $interaction
     * @param qtiRepository $qtiRepos
     *
     */
    public function export(\UJM\ExoBundle\Entity\Interaction $interaction, qtiRepository $qtiRepos)
    {
        $this->qtiRepos = $qtiRepos;
        $this->question = $interaction->getQuestion();

        $this->interactionqcm = $this->doctrine
                                ->getManager()
                                ->getRepository('UJMExoBundle:InteractionQCM')
                                ->findOneBy(array('interaction' => $interaction->getId()));

        //if it's Null mean "Global notation for QCM" Else it's Notation for each choice
        $weightresponse = $this->interactionqcm->getWeightResponse();
        if ($this->interactionqcm->getTypeQCM() == 'Multiple response') {
            $choiceType = 'choiceMultiple';
            $cardinality = 'multiple';
        } else {
            $choiceType = 'choice';
            $cardinality = 'single';
        }

        $this->qtiHead($choiceType, $this->question->getTitle());
        $this->qtiResponseDeclaration('RESPONSE','identifier', $cardinality);
        $this->qtiOutComeDeclaration();

        $this->correctResponseTag();
        $this->itemBodyTag();
        $this->choiceInteractionTag();
        $this->promptTag();

        //comment globale for this question
        if(($this->interactionqcm->getInteraction()->getFeedBack()!=Null)
                && ($this->interactionqcm->getInteraction()->getFeedBack()!="") ){
            $this->qtiFeedBack($interaction->getFeedBack());
        }

        if($weightresponse == false){
            $this->node->appendChild($this->responseProcessing);
        }

        $this->document->save($this->qtiRepos->getUserDir().$this->question->getId().'_qestion_qti.xml');

        return $this->getResponse();

    }

    private function qtiChoicesQCM()
    {
        $mapping = $this->document->CreateElement('mapping');
        $i=-1;
        foreach($this->interactionqcm->getChoices() as $ch) {

           $i++;
           if($ch->getRightResponse() ==  true) {
               $this->valueCorrectResponseTag($i);
           }

           if($this->interactionqcm->getWeightResponse()==true) {
               $this->notationByChoice($mapping, $i, $ch->getWeight());
           } else {
               $this->notationGlobal();
           }

           $this->simpleChoiceTag($ch, $i);

        }
    }

    /**
     * Implements the abstract method
     * add the tag correctResponse in responseDeclaration
     *
     * @access protected
     *
     */
    protected function correctResponseTag()
    {
        $this->correctResponse = $this->document->CreateElement('correctResponse');
        $this->responseDeclaration[0]->appendChild($this->correctResponse);
    }

    /**
     * add tag value in correctResponse for each good choice
     *
     * @param Integer  $choiceNumber
     *
     * @access private
     *
     */
    private function valueCorrectResponseTag($choiceNumber)
    {
        $value = $this->document->CreateElement('value');
        $this->correctResponse->appendChild($value);
        $valuetxt =  $this->document->CreateTextNode("Choice".$choiceNumber);
        $value->appendChild($valuetxt);
    }

    /**
     * add the tag choiceInteraction in itemBody
     *
     * @access private
     *
     */
    private function choiceInteractionTag()
    {
        $this->choiceInteraction = $this->document->CreateElement('choiceInteraction');
        $this->choiceInteraction->setAttribute("responseIdentifier", "RESPONSE");
        if($this->interactionqcm->getShuffle()==1){
            $boolval = "true";
        } else {
            $boolval = "false";
        }

        $this->choiceInteraction->setAttribute("shuffle",$boolval);
        $this->choiceInteraction->setAttribute("maxChoices", count($this->interactionqcm->getChoices()));
        $this->itemBody->appendChild($this->choiceInteraction);
    }

    /**
     * Implements the abstract method
     * add the tag prompt in choiceInteraction
     *
     * @access protected
     *
     */
    protected function promptTag()
    {
        $prompt = $this->document->CreateElement('prompt');
        $this->choiceInteraction->appendChild($prompt);

        $prompttxt =  $this->document
                ->CreateTextNode(
                        $this->interactionqcm->getInteraction()->getInvite()
                        );
        $prompt->appendChild($prompttxt);
        $this->qtiChoicesQCM($this->correctResponse);
    }

    /**
     * add the tag simpleChoice in choiceInteraction
     *
     * @access private
     *
     * @param \UJM\ExoBundle\Entity\Choice $choice
     * @param Integer $choiceNumber
     *
     */
    private function simpleChoiceTag($choice, $choiceNumber)
    {
        $simpleChoice = $this->document->CreateElement('simpleChoice');
        $simpleChoice->setAttribute("identifier", "Choice".$choiceNumber);
        $this->choiceInteraction->appendChild($simpleChoice);
        $simpleChoicetxt =  $this->document->CreateTextNode($choice->getLabel());
         if ($choice->getPositionForce() == 1) {
            $positionForced = 'true';
        } else {
            $positionForced = 'false';
        }
        $simpleChoice->setAttribute("fixed", $positionForced);
        $simpleChoice->appendChild($simpleChoicetxt);

        //comment per line for each choice
        if(($choice->getFeedback()!=Null) && ($choice->getFeedback()!="")){
            $feedbackInline = $this->document->CreateElement('feedbackInline');
            $feedbackInline->setAttribute("outcomeIdentifier", "FEEDBACK");
            $feedbackInline->setAttribute("identifier","Choice".$choiceNumber);
            $feedbackInline->setAttribute("showHide","show");
            $feedbackInlinetxt = $this->document->CreateTextNode($choice->getFeedback());
            $feedbackInline->appendChild($feedbackInlinetxt);
            $simpleChoice->appendChild($feedbackInline);
        }
    }

    /**
     * add the tags for notation by choice
     *
     * @access private
     *
     * @param DOM element $mapping
     * @param Integer $i
     * @param Float $weight
     *
     */
    private function notationByChoice($mapping, $i, $weight)
    {
       $mapEntry= $this->document->CreateElement('mapEntry');
       $mapEntry->setAttribute("mapKey", "Choice".$i);
       $mapEntry->setAttribute("mappedValue", $weight);
       $mapping->appendChild($mapEntry);
       $this->responseDeclaration[0]->appendChild($mapping);
    }

    /**
     * add the tags for a global notation
     *
     * @access private
     *
     */
    private function notationGlobal()
    {
       $this->responseProcessing =  $this->document->CreateElement('responseProcessing');
       $responseCondition = $this->document->CreateElement('responseCondition');
       $responseIf = $this->document->CreateElement('responseIf');
       $responseElse = $this->document->CreateElement('responseElse');
       $match = $this->document->CreateElement('match');
       $variable = $this->document->CreateElement('variable');
       $variable->setAttribute("identifier", "RESPONSE");
       $correct = $this->document->CreateElement('correct');
       $correct->setAttribute("identifier", "RESPONSE");

       $match->appendChild($variable);
       $match->appendChild($correct);

       $setOutcomeValue = $this->document->CreateElement('setOutcomeValue');
       $setOutcomeValue->setAttribute("identifier", "SCORE");

       $baseValue= $this->document->CreateElement('baseValue');
       $baseValue->setAttribute("baseType", "float");
       $baseValuetxt = $this->document->CreateTextNode($this->interactionqcm->getScoreRightResponse());
       $baseValue->appendChild($baseValuetxt);

       $responseIf->appendChild($match);
       $setOutcomeValue->appendChild($baseValue);
       $responseIf->appendChild($setOutcomeValue);

       $setOutcomeValue = $this->document->CreateElement('setOutcomeValue');
       $setOutcomeValue->setAttribute("identifier", "SCORE");

       $baseValue= $this->document->CreateElement('baseValue');
       $baseValue->setAttribute("baseType", "float");
       $baseValuetxt = $this->document->CreateTextNode($this->interactionqcm->getScoreFalseResponse());
       $baseValue->appendChild($baseValuetxt);


       $setOutcomeValue->appendChild($baseValue);
       $responseElse->appendChild($setOutcomeValue);


       $responseCondition->appendChild($responseIf);
       $responseCondition->appendChild($responseElse);

       $this->responseProcessing->appendChild($responseCondition);
    }

}
