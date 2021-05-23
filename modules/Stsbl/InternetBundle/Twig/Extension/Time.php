<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Twig\Extension;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Driver\Exception;
use IServ\CoreBundle\Util\Format;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

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
 * Time formatting filters
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class Time extends AbstractExtension
{
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            'linterval' => new TwigFilter('linterval', [$this, 'intervalToString']),
            'smart_date' => new TwigFilter('smart_date', [$this, 'smartDate']),
        ];
    }

    /**
     * Converts postgresql interval in a human readable string.
     */
    public function intervalToString(string $interval): string
    {
        // parse interval via database connection
        // FIXME This is very UGLY!
        $db = $this->connectionFactory->createConnection(['pdo' => new \PDO('pgsql:dbname=iserv', 'symfony')]);
        try {
            $statement = $db->prepare('SELECT EXTRACT(EPOCH FROM ?::interval)');
            $statement->execute([$interval]);
            $seconds = (float)$statement->fetchAssociative()['date_part'];
        } catch (Exception $e) {
            throw new \RuntimeException('Could not fetch.', 0, $e);
        }

        $db->close();

        $a['d'] = floor($seconds / 86400);
        $a['h'] = floor($seconds / 3600) % 24;
        $a['m'] = floor($seconds / 60) % 60;
        $a['s'] = floor($seconds) % 60;
        $res = [];
        foreach ($a as $key => $v) {
            if ($v or $res) {
                $res[] = sprintf($res ? '%02d' : '%d', $v) . $key;
            }
        }

        return implode(' ', $res);
    }

    /**
     * Example: 8:30 AM; So, 11:15 PM; Dec 24th; Jan 1st, 05
     *
     * @param \DateTime|string|int $datetime
     */
    public function smartDate($datetime): string
    {
        return Format::smartDate($datetime);
    }
}
