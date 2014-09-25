<?php
/**
 * Authentication manager test class.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
namespace VuFindTest\Auth;
use VuFind\Auth\Manager, VuFind\Auth\PluginManager, VuFind\Db\Table\User as UserTable,
    Zend\Config\Config, Zend\Session\SessionManager;

/**
 * Authentication manager test class.
 *
 * @category VuFind2
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
class ManagerTest extends \VuFindTest\Unit\TestCase
{
    /**
     * Test that database is the default method.
     *
     * @return void
     */
    public function testDefaultConfig()
    {
        $this->assertEquals('Database', $this->getManager()->getAuthMethod());
    }

    /**
     * Test getSessionInitiator
     *
     * @return void
     */
    public function testGetSessionInitiator()
    {
        $pm = $this->getMockPluginManager();
        $db = $pm->get('Database');
        $db->expects($this->once())->method('getSessionInitiator')->with($this->equalTo('foo'))->will($this->returnValue('bar'));
        $manager = $this->getManager(array(), null, null, $pm);
        $this->assertEquals('bar', $manager->getSessionInitiator('foo'));
    }

    /**
     * Test getSelectableAuthOptions
     *
     * @return void
     */
    public function testGetSelectableAuthOptions()
    {
        // Simple case -- default Database helper.
        $this->assertEquals(array('Database'), $this->getManager()->getSelectableAuthOptions());

        // Advanced case -- ChoiceAuth.
        $config = array('Authentication' => array('method' => 'ChoiceAuth'));
        $manager = $this->getManager($config);
        $this->assertEquals(array('Database', 'Shibboleth'), $manager->getSelectableAuthOptions());
    }

    /**
     * Test getLoginTargets
     *
     * @return void
     */
    public function testGetLoginTargets()
    {
        $pm = $this->getMockPluginManager();
        $targets = array('a', 'b', 'c');
        $multi = $pm->get('MultiILS');
        $multi->expects($this->once())->method('getLoginTargets')->will($this->returnValue($targets));
        $config = array('Authentication' => array('method' => 'MultiILS'));
        $this->assertEquals($targets, $this->getManager($config, null, null, $pm)->getLoginTargets());
    }

    /**
     * Test getDefaultLoginTarget
     *
     * @return void
     */
    public function testGetDefaultLoginTarget()
    {
        $pm = $this->getMockPluginManager();
        $target = 'foo';
        $multi = $pm->get('MultiILS');
        $multi->expects($this->once())->method('getDefaultLoginTarget')->will($this->returnValue($target));
        $config = array('Authentication' => array('method' => 'MultiILS'));
        $this->assertEquals($target, $this->getManager($config, null, null, $pm)->getDefaultLoginTarget());
    }

    /**
     * Test logout (with destruction)
     *
     * @return void
     */
    public function testLogoutWithDestruction()
    {
        $pm = $this->getMockPluginManager();
        $db = $pm->get('Database');
        $db->expects($this->once())->method('logout')->with($this->equalTo('http://foo/bar'))->will($this->returnValue('http://baz'));
        $sm = $this->getMockSessionManager();
        $sm->expects($this->once())->method('destroy');
        $manager = $this->getManager(array(), null, $sm, $pm);
        $this->assertEquals('http://baz', $manager->logout('http://foo/bar'));
    }

    /**
     * Test logout (without destruction)
     *
     * @return void
     */
    public function testLogoutWithoutDestruction()
    {
        $pm = $this->getMockPluginManager();
        $db = $pm->get('Database');
        $db->expects($this->once())->method('logout')->with($this->equalTo('http://foo/bar'))->will($this->returnValue('http://baz'));
        $sm = $this->getMockSessionManager();
        $sm->expects($this->exactly(0))->method('destroy');
        $manager = $this->getManager(array(), null, $sm, $pm);
        $this->assertEquals('http://baz', $manager->logout('http://foo/bar', false));
    }

    /**
     * Test that login is enabled by default.
     *
     * @return void
     */
    public function testLoginEnabled()
    {
        $this->assertTrue($this->getManager()->loginEnabled());
    }

    /**
     * Test that login can be disabled by configuration.
     *
     * @return void
     */
    public function testLoginDisabled()
    {
        $config = array('Authentication' => array('hideLogin' => true));
        $this->assertFalse($this->getManager($config)->loginEnabled());
    }

    /**
     * Test security features of switching between auth options (part 1).
     *
     * @return void
     */
    public function testSwitchingSuccess()
    {
        $config = array('Authentication' => array('method' => 'ChoiceAuth'));
        $manager = $this->getManager($config);
        $this->assertEquals('ChoiceAuth', $manager->getAuthMethod());
        // The default mock object in this test is configured to allow a
        // switch from ChoiceAuth --> Database
        $manager->setAuthMethod('Database');
        $this->assertEquals('Database', $manager->getAuthMethod());
    }

    /**
     * Test security features of switching between auth options (part 2).
     *
     * @return void
     * @expectedException \Exception
     * @expectedExceptionMessage Illegal authentication method: MultiILS
     */
    public function testSwitchingFailure()
    {
        $config = array('Authentication' => array('method' => 'ChoiceAuth'));
        $manager = $this->getManager($config);
        $this->assertEquals('ChoiceAuth', $manager->getAuthMethod());
        // The default mock object in this test is NOT configured to allow a
        // switch from ChoiceAuth --> MultiILS
        $manager->setAuthMethod('MultiILS');
    }

    /**
     * Test supportsCreation
     *
     * @return void
     */
    public function testSupportsCreation()
    {
        $config = array('Authentication' => array('method' => 'ChoiceAuth'));
        $pm = $this->getMockPluginManager();
        $db = $pm->get('Database');
        $db->expects($this->once())->method('supportsCreation')->will($this->returnValue(true));
        $shib = $pm->get('Shibboleth');
        $shib->expects($this->once())->method('supportsCreation')->will($this->returnValue(false));
        $manager = $this->getManager($config, null, null, $pm);
        $this->assertTrue($manager->supportsCreation('Database'));
        $this->assertFalse($manager->supportsCreation('Shibboleth'));
    }

    /**
     * Test supportsRecovery
     *
     * @return void
     */
    public function testSupportsRecovery()
    {
        // Most common case -- no:
        $this->assertFalse($this->getManager()->supportsRecovery());

        // Less common case -- yes:
        $pm = $this->getMockPluginManager();
        $db = $pm->get('Database');
        $db->expects($this->once())->method('supportsPasswordChange')->will($this->returnValue(true));
        $config = array('Authentication' => array('recover_password' => true));
        $this->assertTrue($this->getManager($config, null, null, $pm)->supportsRecovery());
    }

    /**
     * Test supportsPasswordChange
     *
     * @return void
     */
    public function testSupportsPasswordChange()
    {
        // Most common case -- no:
        $this->assertFalse($this->getManager()->supportsPasswordChange());

        // Less common case -- yes:
        $pm = $this->getMockPluginManager();
        $db = $pm->get('Database');
        $db->expects($this->once())->method('supportsPasswordChange')->will($this->returnValue(true));
        $config = array('Authentication' => array('change_password' => true));
        $this->assertTrue($this->getManager($config, null, null, $pm)->supportsPasswordChange());
    }

    /**
     * Test getAuthClassForTemplateRendering
     *
     * @return void
     */
    public function testGetAuthClassForTemplateRendering()
    {
        // Simple default case:
        $pm = $this->getMockPluginManager();
        $this->assertEquals(get_class($pm->get('Database')), $this->getManager()->getAuthClassForTemplateRendering());

        // Complex case involving proxied authenticator in ChoiceAuth:
        $config = array('Authentication' => array('method' => 'ChoiceAuth'));
        $choice = $pm->get('ChoiceAuth');
        $choice->expects($this->once())->method('getSelectedAuthOption')->will($this->returnValue('Shibboleth'));
        $manager = $this->getManager($config, null, null, $pm);
        $this->assertEquals(get_class($pm->get('Shibboleth')), $manager->getAuthClassForTemplateRendering());
    }

    /**
     * Get a manager object to test with.
     *
     * @param array          $config         Configuration
     * @param UserTable      $userTable      User table gateway
     * @param SessionManager $sessionManager Session manager
     * @param PluginManager  $pm             Authentication plugin manager
     *
     * @return Manager
     */
    protected function getManager($config = array(), $userTable = null, $sessionManager = null, $pm = null)
    {
        $config = new Config($config);
        if (null === $userTable) {
            $userTable = $this->getMockBuilder('VuFind\Db\Table\User')
                ->disableOriginalConstructor()
                ->getMock();
        }
        if (null === $sessionManager) {
            $sessionManager = $this->getMockSessionManager();
        }
        if (null === $pm) {
            $pm = $this->getMockPluginManager();
        }
        return new Manager($config, $userTable, $sessionManager, $pm);
    }

    /**
     * Get a mock session manager.
     *
     * @return SessionManager
     */
    protected function getMockSessionManager()
    {
        return $this->getMockBuilder('Zend\Session\SessionManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get a mock plugin manager.
     *
     * @return PluginManager
     */
    protected function getMockPluginManager()
    {
        $pm = new PluginManager();
        $mockChoice = $this->getMockBuilder('VuFind\Auth\ChoiceAuth')
            ->disableOriginalConstructor()
            ->getMock();
        $mockChoice->expects($this->any())->method('getSelectableAuthOptions')->will($this->returnValue(array('Database', 'Shibboleth')));
        $mockDb = $this->getMockBuilder('VuFind\Auth\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $mockMulti = $this->getMockBuilder('VuFind\Auth\MultiILS')
            ->disableOriginalConstructor()
            ->getMock();
        $mockShib = $this->getMockBuilder('VuFind\Auth\Shibboleth')
            ->disableOriginalConstructor()
            ->getMock();
        $pm->setService('ChoiceAuth', $mockChoice);
        $pm->setService('Database', $mockDb);
        $pm->setService('MultiILS', $mockMulti);
        $pm->setService('Shibboleth', $mockShib);
        return $pm;
    }
}