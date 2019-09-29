<?php
// src/Stsbl/InternetBundle/Form/Data/CreateNacs.php
namespace Stsbl\InternetBundle\Form\Data;

use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use Stsbl\InternetBundle\Form\Type\NacCreateType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/*
 * The MIT License
 *
 * Copyright 2019 Felix Jacobi.
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
class CreateNacs
{
    /**
     * @Assert\NotBlank()
     * @Assert\GreaterThan(value="0")
     *
     * @var int
     */
    private $duration;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice(choices={"free_usage", "user", "group", "all"})
     *
     * @var string
     */
    private $assignment;

    /**
     * @Assert\NotBlank()
     *
     * @var User|null
     */
    private $creator;

    /**
     * @var User|null
     */
    private $user;

    /**
     * @var Group|null
     */
    private $group;

    /**
     * @var int|null
     */
    private $count;

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validateUser(ExecutionContextInterface $context)
    {
        if ('user' === $this->assignment && null === $this->user) {
            $context
                ->buildViolation(_('Please choose a user'))
                ->atPath('user')
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validateGroup(ExecutionContextInterface $context)
    {
        if ('group' === $this->assignment && null === $this->group) {
            $context
                ->buildViolation(_('Please choose a group'))
                ->atPath('group')
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validateCount(ExecutionContextInterface $context)
    {
        if ($this->assignment === 'free_usage') {
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

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return string
     */
    public function getAssignment()
    {
        return $this->assignment;
    }

    /**
     * @param string $assignment
     */
    public function setAssignment($assignment)
    {
        $this->assignment = $assignment;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
    }

    /**
     * @return Group|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group|null $group
     */
    public function setGroup(Group $group = null)
    {
        $this->group = $group;
    }

    /**
     * @return int|null
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int|null $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return User|null
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param User|null $creator
     */
    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;
    }
}