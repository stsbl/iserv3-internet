<?php
// src/Stsbl/InternetBundle/Entity/Nac.php
namespace Stsbl\InternetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\User;
use IServ\CrudBundle\Entity\CrudInterface;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @ORM\Entity(repositoryClass="NacRepository")
 * @ORM\Table(name="nacs")
 */
class Nac implements CrudInterface
{
    /**
     * @ORM\Column(type="text", nullable=false)
     * @ORM\Id
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $nac;

    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="owner", referencedColumnName="act")
     *
     * @var User
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="act", referencedColumnName="act")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="inet", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Ip()
     *
     * @var string
     */
    private $ip;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $remain;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $timer;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $expire;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $assigned;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->created = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() 
    {
        return $this->nac;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->nac;
    }


    /**
     * Set nac
     *
     * @param string $nac
     *
     * @return Nac
     */
    public function setNac($nac)
    {
        $this->nac = $nac;

        return $this;
    }

    /**
     * Get nac
     *
     * @return string
     */
    public function getNac()
    {
        return $this->nac;
    }

    /**
     * Set remain
     *
     * @param string $remain
     *
     * @return Nac
     */
    public function setRemain($remain)
    {
        $this->remain = $remain;

        return $this;
    }

    /**
     * Get remain
     *
     * @return string
     */
    public function getRemain()
    {
        return $this->remain;
    }

    /**
     * Set timer
     *
     * @param \DateTime $timer
     *
     * @return Nac
     */
    public function setTimer($timer)
    {
        $this->timer = $timer;

        return $this;
    }

    /**
     * Get timer
     *
     * @return \DateTime
     */
    public function getTimer()
    {
        return $this->timer;
    }

    /**
     * Set expire
     *
     * @param \DateTime $expire
     *
     * @return Nac
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Get expire
     *
     * @return \DateTime
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Nac
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set assigned
     *
     * @param \DateTime $assigned
     *
     * @return Nac
     */
    public function setAssigned($assigned)
    {
        $this->assigned = $assigned;

        return $this;
    }

    /**
     * Get assigned
     *
     * @return \DateTime
     */
    public function getAssigned()
    {
        return $this->assigned;
    }

    /**
     * Set user
     *
     * @param \IServ\CoreBundle\Entity\User $user
     *
     * @return Nac
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \IServ\CoreBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set owner
     *
     * @param User $owner
     *
     * @return Nac
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set ip
     *
     * @param inet $ip
     *
     * @return Nac
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return inet
     */
    public function getIp()
    {
        return $this->ip;
    }
}
