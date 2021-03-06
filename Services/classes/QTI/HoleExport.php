<?php

/**
 * To export question with holes in QTI.
 */
namespace UJM\ExoBundle\Services\classes\QTI;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UJM\ExoBundle\Entity\Question;

class HoleExport extends QtiExport
{
    private $interactionhole;
    private $correctResponse = array();

    /**
     * Implements the abstract method.
     *
     * @access public
     * @param Question $question
     * @param qtiRepository $qtiRepos
     * @return BinaryFileResponse
     */
    public function export(Question $question, qtiRepository $qtiRepos)
    {
        $this->qtiRepos = $qtiRepos;
        $this->question = $question;

        $this->interactionhole = $this->doctrine
                                ->getManager()
                                ->getRepository('UJMExoBundle:InteractionHole')
                                ->findOneByQuestion($question);

        $this->qtiHead('textEntry', $this->question->getTitle());
        foreach ($this->interactionhole->getHoles() as $hole) {
            $numberBlank = $this->nbResponseDeclaration + 1;
            $this->qtiResponseDeclaration('blank_'.$numberBlank, 'string', 'single');
            $this->correctResponseTag();
            $this->mappingTag($hole);
        }
        $this->qtiOutComeDeclaration();

        $this->itemBodyTag();
        $this->promptTag();
        $this->textWithHole();

        if ($this->interactionhole->getQuestion()->getFeedBack() != null
            && $this->interactionhole->getQuestion()->getFeedBack() != '') {
            $this->qtiFeedBack($question->getFeedBack());
        }

        $this->document->save($this->qtiRepos->getUserDir().$this->question->getId().'_qestion_qti.xml');

        return $this->getResponse();
    }

    /**
     * Implements the abstract method
     * add the tag correctResponse in responseDeclaration.
     */
    protected function correctResponseTag()
    {
        $responseDeclaration = $this->responseDeclaration[$this->nbResponseDeclaration - 1];
        $this->correctResponse[$this->nbResponseDeclaration - 1] = $this->document->CreateElement('correctResponse');
        $responseDeclaration->appendChild($this->correctResponse[$this->nbResponseDeclaration - 1]);
    }

    /**
     * add the tag mapping in responseDeclaration.
     *
     *
     * @param \UJM\ExoBundle\Entity\Hole $hole
     */
    private function mappingTag($hole)
    {
        $responseDeclaration = $this->responseDeclaration[$this->nbResponseDeclaration - 1];
        $correctResponse = $this->correctResponse[$this->nbResponseDeclaration - 1];
        $correctWordResponse = '';
        $mapping = $this->document->createElement('mapping');
        $mapping->setAttribute('defaultValue', '0');

        foreach ($hole->getWordResponses() as $resp) {
            $i = 0;
            if ($correctWordResponse == '') {
                $correctWordResponse = $resp;
            } else {
                if ($correctWordResponse->getScore() < $resp->getScore()) {
                    $correctWordResponse = $resp;
                }
            }

            $mapEntry = $this->document->createElement('mapEntry');
            if (!$hole->getSelector()) {
                $mapEntry->setAttribute('mapKey', $resp->getResponse());
            } else {
                $mapEntry->setAttribute('mapKey', 'choice_'.$resp->getId());
            }
            $mapEntry->setAttribute('mappedValue', $resp->getScore());
            $mapEntry->setAttribute('caseSensitive', $resp->getCaseSensitive());
            $mapping->appendChild($mapEntry);
            if(($resp->getFeedback()!=Null) && ($resp->getFeedback()!="")){
                $feedbackInline = $this->document->CreateElement('feedbackInline');
                $feedbackInline->setAttribute("outcomeIdentifier", "FEEDBACK");
                $feedbackInline->setAttribute("identifier","choice_".$resp->getId());
                $feedbackInline->setAttribute("showHide","show");
                $this->getDomEl($feedbackInline, $resp->getFeedback());
                $mapEntry->appendChild($feedbackInline);
            }
            $i++;
        }
        $Tagvalue = $this->document->CreateElement('value');
        if (!$hole->getSelector()) {
            $responsevalue = $this->document->CreateTextNode($correctWordResponse->getResponse());
        } else {
            $responsevalue = $this->document->CreateTextNode('choice_'.$correctWordResponse->getId());
        }
        $Tagvalue->appendChild($responsevalue);
        $correctResponse->appendChild($Tagvalue);
        $responseDeclaration->appendChild($correctResponse);

        $responseDeclaration->appendChild($mapping);
    }

    /**
     * Implements the abstract method
     * add the tag prompt in itemBody.
     */
    protected function promptTag()
    {
        $prompt = $this->document->CreateElement('prompt');
        $invite = $this->interactionhole->getQuestion()->getInvite();
        // Managing the resource export
        $body=$this->qtiExportObject($invite);
        foreach ($body->childNodes as $child) {
            $inviteNew = $this->document->importNode($child, true);
            $prompt->appendChild($inviteNew);
        }
        $this->itemBody->appendChild($prompt);
    }

    /**
     * Text with hole.
     */
    private function textWithHole()
    {
        $textEntryInteraction = '';
        $newId = 1;
        $html = htmlspecialchars_decode($this->interactionhole->getHtmlWithoutValue());
        //delete the line break in $html
        $html = str_replace(CHR(10), '', $html);
        $html = str_replace(CHR(13), '', $html);
        $html = html_entity_decode($html);
        $regex = '(<input.*?class="blank".*?>|<select.*?class="blank".*?>.*?</select>)';
        preg_match_all($regex, $html, $matches);
        foreach ($matches[0] as $matche) {
            $tabMatche = explode('"', $matche);
            $id = $tabMatche[1];
            if (substr($matche, 1, 5) == 'input') { //hole with input element
                $name = $tabMatche[7];
                $size = $tabMatche[9];
                $textEntryInteraction = str_replace('input', 'textEntryInteraction', $matche);
                $textEntryInteraction = str_replace('class="blank" ', '', $textEntryInteraction);
                $textEntryInteraction = str_replace('type="text" ', '', $textEntryInteraction);
                $textEntryInteraction = str_replace('value="" ', '', $textEntryInteraction);
                $textEntryInteraction = str_replace('id="'.$id.'"', 'responseIdentifier="blank_'.$newId.'"', $textEntryInteraction);
                $textEntryInteraction = str_replace('name="'.$name.'"', '', $textEntryInteraction);
                $textEntryInteraction = str_replace('autocomplete="off"', '', $textEntryInteraction);
                $textEntryInteraction = str_replace('size="'.$size.'"', 'expectedLength="'.$size.'"', $textEntryInteraction);
            } else { //hole with select element
                $name = $tabMatche[5];
                $textEntryInteraction = str_replace('</select>', '</inlineChoiceInteraction>', $matche);
                $textEntryInteraction = str_replace('select', 'inlineChoiceInteraction', $textEntryInteraction);
                $textEntryInteraction = str_replace('id="'.$id.'"', 'responseIdentifier="blank_'.$newId.'"', $textEntryInteraction);
                $textEntryInteraction = str_replace('class="blank" ', '', $textEntryInteraction);
                $textEntryInteraction = str_replace('name="'.$name.'"', '', $textEntryInteraction);
                $textEntryInteraction = str_replace('option', 'inlineChoice', $textEntryInteraction);
                //for the option elements
                $regexOpt = '(<inlineChoice value=.*?>)';
                preg_match_all($regexOpt, $textEntryInteraction, $matchesOpt);
                foreach ($matchesOpt[0] as $matcheOpt) {
                    $tabMatcheOpt = explode('"', $matcheOpt);
                    $holeID = $tabMatcheOpt[1];
                    $opt = str_replace('value="'.$holeID.'"', 'identifier="choice_'.$holeID.'"', $matcheOpt);
                    $textEntryInteraction = str_replace($matcheOpt, $opt, $textEntryInteraction);
                }
            }
            $textEntryInteraction = str_replace('&nbsp;', ' ', $textEntryInteraction);
            $html = str_replace($matche, $textEntryInteraction, $html);
            ++$newId;
        }

        //For the question created before the 2014/10/09
        $html = str_replace('</select>', '', $html);

        $fragment = $this->document->createDocumentFragment();
        $fragment->appendXML($html);
        $this->itemBody->appendChild($fragment);
        $this->imgToObject($this->document);
        $this->aToObject($this->document);
    }
}
