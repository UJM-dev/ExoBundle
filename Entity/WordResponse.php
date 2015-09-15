<?php

namespace UJM\ExoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UJM\ExoBundle\Entity\WordResponse.
 *
 * @ORM\Entity(repositoryClass="UJM\ExoBundle\Repository\WordResponseRepository")
 * @ORM\Table(name="ujm_word_response")
 */
class WordResponse
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="response", type="string", length=255)
     */
    private $response;

    /**
     * @var bool
     *
     * @ORM\Column(name="caseSensitive", type="boolean", nullable=true)
     */
    private $caseSensitive;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float")
     */
    private $score;

    /**
     * @ORM\ManyToOne(targetEntity="UJM\ExoBundle\Entity\InteractionOpen", inversedBy="wordResponses")
     * @ORM\JoinColumn(name="interaction_open_id", referencedColumnName="id")
     */
    private $interactionopen;

    /**
     * @ORM\ManyToOne(targetEntity="UJM\ExoBundle\Entity\Hole", inversedBy="wordResponses")
     * @ORM\JoinColumn(name="hole_id", referencedColumnName="id")
     */
    private $hole;

    /**
     * @var text
     *
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    private $feedback;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set response.
     *
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * Get caseSensitive.
     *
     * @return bool
     */
    public function getCaseSensitive()
    {
        return $this->caseSensitive;
    }

    /**
     * Set caseSensitive.
     *
     * @param bool $caseSensitive
     */
    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
    }

    /**
     * Get response.
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set score.
     *
     * @param float $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * Get score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    public function getInteractionOpen()
    {
        return $this->interactionopen;
    }

    public function setInteractionOpen(\UJM\ExoBundle\Entity\InteractionOpen $interactionOpen)
    {
        $this->interactionopen = $interactionOpen;
    }

    public function getHole()
    {
        return $this->hole;
    }

    public function setHole(\UJM\ExoBundle\Entity\Hole $hole)
    {
        $this->hole = $hole;
    }

    /**
     * get feedback.
     *
     * @return string
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * set feedback.
     *
     * @param \UJM\ExoBundle\Entity\text $feedback
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;
    }
}
