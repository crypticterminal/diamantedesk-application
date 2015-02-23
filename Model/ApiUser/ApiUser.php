<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */
namespace Diamante\ApiBundle\Model\ApiUser;

use Diamante\DeskBundle\Model\Shared\Entity;
use Diamante\DeskBundle\Model\User\DiamanteUser;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiUser implements Entity, UserInterface
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var array
     */
    protected $roles = array();

    /**
     * @var string
     */
    protected $salt;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var bool
     */
    protected $isActive;

    /**
     * @var string
     */
    protected $hash;

    public function __construct($email, $password, $salt = null)
    {
        $this->email  = $email;
        $this->password  = $password;
        $this->salt      = $salt;
        $this->isActive  = false;
        $this->hash = md5($this->email . time());
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    public function eraseCredentials()
    {

    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Activate user
     *
     * @param string $hash
     * @return void
     * @throws \RuntimeException if given hash is not equal to generated one for user
     */
    public function activate($hash)
    {
        if ($this->isActive()) {
            return;
        }
        if ($this->hash != $hash) {
            throw new \RuntimeException('Given hash is invalid and user can not be activated.');
        }
        $this->isActive = true;
        $this->hash = '';
    }
}
