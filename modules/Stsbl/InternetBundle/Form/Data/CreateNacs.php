<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Form\Data;

use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use Stsbl\InternetBundle\Form\Type\NacCreateType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
 */
final class CreateNacs
{
    public const ASSIGNMENT_TYPE_USER = 'user';
    public const ASSIGNMENT_TYPE_GROUP = 'group';
    public const ASSIGNMENT_TYPE_FREE_USAGE = 'free_usage';
    public const ASSIGNMENT_TYPE_ALL = 'all';

    /**
     * @Assert\NotBlank()
     * @Assert\GreaterThan(value="0")
     */
    private ?int $duration;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice(choices={"free_usage", "group", "user", "all"})
     */
    private ?string $assignment;

    /**
     * @Assert\NotBlank()
     */
    private User $creator;

    private ?User $user;

    private ?Group $group;

    private ?int $count;

    public function __construct(User $creator)
    {
        $this->creator = $creator;
    }

    /**
     * @Assert\Callback()
     */
    public function validateUser(ExecutionContextInterface $context): void
    {
        if (self::ASSIGNMENT_TYPE_USER === $this->assignment && null === $this->user) {
            $context
                ->buildViolation(_('Please choose a user'))
                ->atPath(self::ASSIGNMENT_TYPE_USER)
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback()
     */
    public function validateGroup(ExecutionContextInterface $context): void
    {
        if (self::ASSIGNMENT_TYPE_GROUP === $this->assignment && null === $this->group) {
            $context
                ->buildViolation(_('Please choose a group'))
                ->atPath(self::ASSIGNMENT_TYPE_GROUP)
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validateCount(ExecutionContextInterface $context): void
    {
        if (self::ASSIGNMENT_TYPE_FREE_USAGE === $this->assignment) {
            if ($this->count === null || !is_numeric($this->count) || $this->count <= 0) {
                $context
                    ->buildViolation(__('This value should be greater than %s.', 0))
                    ->atPath('count')
                    ->addViolation()
                ;
            } elseif ($this->count > NacCreateType::MAX_UNASSIGNED_NAC_COUNT) {
                $context
                    ->buildViolation($context, __('This value should be less than or equal to %s.', NacCreateType::MAX_UNASSIGNED_NAC_COUNT))
                    ->atPath('count')
                    ->addViolation()
                ;
            }
        }
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @return $this
     */
    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getAssignment(): ?string
    {
        return $this->assignment;
    }

    /**
     * @return $this
     */
    public function setAssignment(?string $assignment): self
    {
        $this->assignment = $assignment;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setUser(User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * @return $this
     */
    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }
}
