<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2019
 * @license BSD 2.0
 */

namespace Tramasec\Util\EvalMath\Exception;


class InternalErrorException extends AbstractEvalMathException
{
    protected $message = 'Internal error';
}