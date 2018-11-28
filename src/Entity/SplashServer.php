<?php

/**
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * @author Bernard Paquier <contact@splashsync.com>
 */

namespace Splash\Admin\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Description of SplashServer
 *
 * @ORM\Entity
 * @author nanard33
 */
class SplashServer {
    
    //==============================================================================
    //  Definition           
    //==============================================================================
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @abstract    Unik Client Identifier ( 1 to 8 Char)
     * @ORM\Column(name="ServerId", type="string", length=250)
     */
    protected $identifier;
    
    /**
     * @abstract    Node Settings
     * @ORM\Column(name="Settings", type="array")
     * @var Array
     */
    protected $settings = array();      
    
    /**
     * Set setting
     *
     * @param string    $Parameter
     * @param bool      $Value
     *
     * @return User
     */
    public function setSetting($Parameter,$Value)
    {
        $Settings = $this->getSettings();
        $Settings[$Parameter] = $Value;
        $this->setSettings($Settings);
        return $this;
    }
    
    /**
     * Get settings
     *
     * @param string    $Parameter
     * 
     * @return bool
     */
    public function getSetting($Parameter)
    {
        if (array_key_exists($Parameter, $this->settings)) {
            return $this->settings[$Parameter];
        }
        return Null;
    }  
    
    //==============================================================================
    //  Getters & Setters           
    //==============================================================================
    
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return self
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    /**
     * Set all settings
     *
     * @param array      $Value
     *
     * @return User
     */
    public function setSettings($Value)
    {
        $this->settings =  $Value; 
        return $this;
    }
    
    /**
     * Get All settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }  
        
    
    
}
