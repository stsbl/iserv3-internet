<?php
// src/Stsbl/InternetBundle/Entity/NacRepository.php
namespace Stsbl\InternetBundle\Entity;

use Doctrine\ORM\NoResultException;
use IServ\CrudBundle\Doctrine\ORM\EntitySpecificationRepository;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
class NacRepository extends EntitySpecificationRepository
{
    /**
     * Checks if user has a NAC in the database.
     * 
     * @param UserInterface $user
     * @return bool
     */
    public function hasNac(UserInterface $user)
    {
        try {
            return $this->findOneBy(['user' => $user]) != null;
        } catch (NoResultException $e) {
            return false;
        }
    }

    /**
     * Get current NAC for user.
     * 
     * @param UserInterface $user
     * @return Nac
     */
    public function findNacByUser(UserInterface $user)
    {
        if (!$this->hasNac($user)) {
            throw new \RuntimeException(sprintf('User %s has no NAC!', (string)$user));
        }

        return $this->findOneBy(['user' => $user]);
    }
}
