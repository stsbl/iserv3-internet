<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use IServ\CoreBundle\Service\User\UserStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

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
 * Validator for NAC
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @Annotation
 */
final class NacValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserStorageInterface
     */
    private $securityHandler;

    public function __construct(EntityManagerInterface $em, UserStorageInterface $userStorage)
    {
        $this->em = $em;
        $this->securityHandler = $userStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Nac) {
            throw new UnexpectedTypeException($constraint, Nac::class);
        }

        if (!preg_match('|^[0-9]{8}$|', $value)) {
            $this->context->buildViolation($constraint->getWrongFormatMessage())->atPath('nac')->addViolation();
            return;
        }

        $nacEntity = $this->em->getRepository(\Stsbl\InternetBundle\Entity\Nac::class)->findOneByNac($value);

        if (null === $nacEntity) {
            $this->context->buildViolation($constraint->getInvalidNacMessage())->atPath('nac')->addViolation();
            return;
        }

        if ($nacEntity->getUser() !== $this->securityHandler->getUser() && $nacEntity->getUser() !== null) {
            $this->context->buildViolation($constraint->getWrongOwnerMessage())->atPath('nac')->addViolation();
        }
    }
}
