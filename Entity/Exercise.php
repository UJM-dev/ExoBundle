<?php

namespace UJM\ExoBundle\Entity;

use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="UJM\ExoBundle\Repository\ExerciseRepository")
 * @ORM\Table(name="ujm_exercise")
 */
class Exercise extends AbstractResource
{
    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(name="shuffle", type="boolean", nullable=true)
     */
    private $shuffle = false;

    /**
     * @ORM\Column(name="nb_question", type="integer")
     */
    private $nbQuestion = 0;

    /**
     * @ORM\Column(name="keepSameQuestion", type="boolean", nullable=true)
     */
    private $keepSameQuestion;

    /**
     * @ORM\Column(name="duration", type="integer")
     */
    private $duration = 0;

    /**
     * @ORM\Column(name="doprint", type="boolean", nullable=true)
     */
    private $doprint = false;

    /**
     * @ORM\Column(name="max_attempts", type="integer")
     */
    private $maxAttempts = 0;

    /**
     * @todo mode should be at least a class constant
     *
     * @ORM\Column(name="correction_mode", type="string", length=255)
     */
    private $correctionMode = '1';

    /**
     * @ORM\Column(name="date_correction", type="datetime", nullable=true)
     */
    private $dateCorrection;

    /**
     * @todo mode should be at least a class constant
     *
     * @ORM\Column(name="mark_mode", type="string", length=255)
     */
    private $markMode = '1';

    /**
     * @ORM\Column(name="disp_button_interrupt", type="boolean", nullable=true)
     */
    private $dispButtonInterrupt = false;

    /**
     * @ORM\Column(name="lock_attempt", type="boolean", nullable=true)
     */
    private $lockAttempt = false;

    /**
     * Flag indicating whether the exercise has been published at least
     * one time. An exercise that has never been published has all its
     * existing papers deleted at the first publication.
     *
     * @ORM\Column(name="published", type="boolean")
     */
    private $wasPublishedOnce = false;

    /**
     * @ORM\Column(name="anonymous", type="boolean", nullable=true)
     */
    private $anonymous = false;

    /**
     * @ORM\Column(name="type", type="string", length=255)
     * sommatif, formatif, certificatif
     */
    private $type = '1';

    /**
     * @ORM\OneToMany(
     *     targetEntity="Step",
     *     mappedBy="exercise",
     *     cascade={"remove"}
     * )
     * @ORM\OrderBy({"order" = "ASC"})
     */
    private $steps;

    public function __construct()
    {
        $this->dateCorrection = new \DateTime();
        $this->steps = new ArrayCollection();
    }

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
     * Set title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set shuffle.
     *
     * @param bool $shuffle
     */
    public function setShuffle($shuffle)
    {
        $this->shuffle = $shuffle;
    }

    /**
     * Get shuffle.
     */
    public function getShuffle()
    {
        return $this->shuffle;
    }

    /**
     * Set nbQuestion.
     *
     * @param int $nbQuestion
     */
    public function setNbQuestion($nbQuestion)
    {
        $this->nbQuestion = $nbQuestion;
    }

    /**
     * Get nbQuestion.
     *
     * @return int
     */
    public function getNbQuestion()
    {
        return $this->nbQuestion;
    }

    /**
     * Set keepSameQuestion.
     *
     * @param bool $keepSameQuestion
     */
    public function setKeepSameQuestion($keepSameQuestion)
    {
        $this->keepSameQuestion = $keepSameQuestion;
    }

    /**
     * Get keepSameQuestion.
     */
    public function getKeepSameQuestion()
    {
        return $this->keepSameQuestion;
    }

    /**
     * Set duration.
     *
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Get duration.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set doprint
     *
     * @param bool $doprint
     */
    public function setDoprint($doprint)
    {
        $this->doprint = $doprint;
    }

    /**
     * Get doprint.
     */
    public function getDoprint()
    {
        return $this->doprint;
    }

    /**
     * Set maxAttempts.
     *
     * @param int $maxAttempts
     */
    public function setMaxAttempts($maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Get maxAttempts.
     *
     * @return int
     */
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * Set correctionMode.
     *
     * @param string $correctionMode
     */
    public function setCorrectionMode($correctionMode)
    {
        $this->correctionMode = $correctionMode;
    }

    /**
     * Get correctionMode.
     *
     * @return string
     */
    public function getCorrectionMode()
    {
        return $this->correctionMode;
    }

    /**
     * Set dateCorrection.
     *
     * @param \Datetime $dateCorrection
     */
    public function setDateCorrection(\DateTime $dateCorrection = null)
    {
        $this->dateCorrection = $dateCorrection;
    }

    /**
     * Get dateCorrection.
     *
     * @return \Datetime
     */
    public function getDateCorrection()
    {
        return $this->dateCorrection;
    }

    /**
     * Set markMode.
     *
     * @param string $markMode
     */
    public function setMarkMode($markMode)
    {
        $this->markMode = $markMode;
    }

    /**
     * Get markMode.
     *
     * @return string
     */
    public function getMarkMode()
    {
        return $this->markMode;
    }

    /**
     * Set dispButtonInterrupt.
     *
     * @param bool $dispButtonInterrupt
     */
    public function setDispButtonInterrupt($dispButtonInterrupt)
    {
        $this->dispButtonInterrupt = $dispButtonInterrupt;
    }

    /**
     * Get dispButtonInterrupt.
     */
    public function getDispButtonInterrupt()
    {
        return $this->dispButtonInterrupt;
    }

    /**
     * Set lockAttempt.
     *
     * @param bool $lockAttempt
     */
    public function setLockAttempt($lockAttempt)
    {
        $this->lockAttempt = $lockAttempt;
    }

    /**
     * Get lockAttempt.
     */
    public function getLockAttempt()
    {
        return $this->lockAttempt;
    }

    public function archiveExercise()
    {
        $this->resourceNode = null;
    }

    /**
     * @return bool
     */
    public function wasPublishedOnce()
    {
        return $this->wasPublishedOnce;
    }

    /**
     * @param bool $wasPublishedOnce
     */
    public function setPublishedOnce($wasPublishedOnce)
    {
        $this->wasPublishedOnce = $wasPublishedOnce;
    }

    /**
     * Set anonymous.
     *
     * @param bool $anonymous
     */
    public function setAnonymous($anonymous)
    {
        $this->anonymous = $anonymous;
    }

    /**
     * Get anonymous.
     */
    public function getAnonymous()
    {
        return $this->anonymous;
    }

    /**
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @return ArrayCollection
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Add a step to the Exercise
     * @param Step $step
     * @return $this
     */
    public function addStep(Step $step)
    {
        if (!$this->steps->contains($step)) {
            $this->steps->add($step);

            $step->setExercise($this);
        }

        return $this;
    }

    /**
     * Remove a Step from the Exercise
     * @param Step $step
     * @return $this
     */
    public function removeStep(Step $step)
    {
        if ($this->steps->contains($step)) {
            $this->steps->removeElement($step);
        }

        return $this;
    }
}
