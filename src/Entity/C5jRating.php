<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 * @license MIT
 */

namespace C5jRatings\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="C5jRatings")
 */
class C5jRating
{
    use EntityManagerTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    protected $bID;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    protected $cID;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    protected $uID;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected $ratedValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=false)
     */
    protected $ratedAt;

    public function __construct(array $data = [])
    {
        $this->bID = $data['bID'] ?? null;
        $this->cID = $data['cID'] ?? null;
        $this->uID = $data['uID'] ?? null;
        $this->bID = $data['ratedValue'] ?? 0;
        $this->ratedAt = $data['ratedAt'] ?? Carbon::now();
    }

    public static function getByBIDAndUID(int $bID, int $uID): ?object
    {
        $em = \ORM::entityManager();
        return $em->getRepository(__CLASS__)
            ->findOneBy(['bID' => $bID, 'uID' => $uID]);
    }

    public static function getByCIDAndUID(int $cID, int $uID): ?object
    {
        $em = \ORM::entityManager();
        return $em->getRepository(__CLASS__)
            ->findOneBy(['cID' => $cID, 'uID' => $uID]);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getBID()
    {
        return $this->bID;
    }

    /**
     * @param int $bID
     */
    public function setBID($bID): void
    {
        $this->bID = $bID;
    }

    /**
     * @return int
     */
    public function getCID()
    {
        return $this->cID;
    }

    /**
     * @param int $cID
     */
    public function setCID($cID): void
    {
        $this->cID = $cID;
    }

    /**
     * @return int
     */
    public function getUID()
    {
        return $this->uID;
    }

    /**
     * @param int $uID
     */
    public function setUID($uID): void
    {
        $this->uID = $uID;
    }

    /**
     * @return int
     */
    public function getRatedValue(): int
    {
        return $this->ratedValue;
    }

    /**
     * @param int $ratedValue
     */
    public function setRatedValue(int $ratedValue): void
    {
        $this->ratedValue = $ratedValue;
    }

    /**
     * @return \DateTime
     */
    public function getRatedAt(): \DateTime
    {
        return $this->ratedAt;
    }

    /**
     * @param \DateTime $ratedAt
     */
    public function setRatedAt($ratedAt): void
    {
        $this->ratedAt = $ratedAt;
    }
}