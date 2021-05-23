<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\User;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\Library\Zeit\Zeit;
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
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $remain;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $timer;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $expire;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @var \DateTimeImmutable
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
        $this->created = Zeit::now();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->nac ?? '?';
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?string
    {
        return $this->nac;
    }


    /**
     * @return $this
     */
    public function setNac(?string $nac): self
    {
        $this->nac = $nac;

        return $this;
    }

    public function getNac(): ?string
    {
        return $this->nac;
    }

    public function setRemain(?string $remain): self
    {
        $this->remain = $remain;

        return $this;
    }

    public function getRemain(): ?string
    {
        return $this->remain;
    }

    /**
     * @return $this
     */
    public function setTimer(?string $timer): self
    {
        $this->timer = $timer;

        return $this;
    }

    public function getTimer(): ?string
    {
        return $this->timer;
    }

    /**
     * @return $this
     */
    public function setExpire(\DateTime $expire): self
    {
        $this->expire = $expire;

        return $this;
    }

    public function getExpire(): ?\DateTime
    {
        return $this->expire;
    }

    /**
     * @return $this
     */
    public function setCreated(?\DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @return $this
     */
    public function setAssigned(?\DateTime $assigned): self
    {
        $this->assigned = $assigned;

        return $this;
    }

    public function getAssigned(): ?\DateTime
    {
        return $this->assigned;
    }

    /**
     * @return $this
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @return $this
     */
    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }
}
