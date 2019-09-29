<?php
// src/Stsbl/InternetBundle/Twig/Extension/Time.php
namespace Stsbl\InternetBundle\Twig\Extension;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use IServ\CoreBundle\Util\Format;
use \Twig_Environment;
use \Twig_Extension;
use \Twig_SimpleFilter;

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
 * Time formatting filters
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class Time extends Twig_Extension
{
    private $connectionFactory;

    /**
     * The constructor
     * 
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            'linterval' => new Twig_SimpleFilter('linterval', [$this, 'intervalToString']),
            'smart_date' => new Twig_SimpleFilter('smart_date', [$this, 'smartDate']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'time';
    }

    /**
     * Converts postgresql interval in a human readable string.
     * 
     * @param string $interval
     * @return string
     */
    public function intervalToString($interval)
    {
        // parse interval via database connection
        // FIXME This is very UGLY!
        $db = $this->connectionFactory->createConnection(['pdo' => new \PDO('pgsql:dbname=iserv', 'symfony')]);
        $statement = $db->prepare('SELECT EXTRACT(EPOCH FROM ?::interval)');
        $statement->execute([$interval]);
        $seconds = (float)$statement->fetch()['date_part'];
        $db->close();

        $a['d'] = floor($seconds / 86400);
        $a['h'] = floor($seconds / 3600) % 24;
        $a['m'] = floor($seconds / 60) % 60;
        $a['s'] = floor($seconds) % 60;
        $res = [];
        foreach ($a as $key => $v) {
            if ($v or $res) {
                $res[] = sprintf($res? '%02d': '%d', $v).$key;
            }
        }

        return join(' ', $res);
    }

    /**
     * Example: 8:30 AM; So, 11:15 PM; Dec 24th; Jan 1st, 05
     * 
     * @param \DateTime|string|int $datetime
     * @deprecated
     * @return string
     */
    public function smartDate($datetime)
    {
        @trigger_error(sprintf('The direct usage of %s in %s is deprecated and will removed in future versions. ' .
            'Use the function from %s instead.', __FUNCTION__, self::class, Format::class));

        return Format::smartDate($datetime);
    }
}
