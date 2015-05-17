<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Guard\Tests\Provider;

use Symfony\Component\Security\Guard\Provider\GuardAuthenticationProvider;

/**
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class GuardAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    private $userProvider;
    private $userChecker;
    private $nonAuthedToken;

    public function testAuthenticate()
    {
        $providerKey = 'my_cool_firewall';

        $authenticatorA = $this->getMock('Symfony\Component\Security\Guard\GuardAuthenticatorInterface');
        $authenticatorB = $this->getMock('Symfony\Component\Security\Guard\GuardAuthenticatorInterface');
        $authenticatorC = $this->getMock('Symfony\Component\Security\Guard\GuardAuthenticatorInterface');
        $authenticators = array($authenticatorA, $authenticatorB, $authenticatorC);

        // called 2 times - for authenticator A and B (stops on B because of match)
        $this->nonAuthedToken->expects($this->exactly(2))
            ->method('getGuardProviderKey')
            // it will return the "1" index, which will match authenticatorB
            ->will($this->returnValue('my_cool_firewall_1'));

        $enteredCredentials = array(
            'username' => '_weaverryan_test_user',
            'password' => 'guard_auth_ftw',
        );
        $this->nonAuthedToken->expects($this->once())
            ->method('getCredentials')
            ->will($this->returnValue($enteredCredentials));

        // authenticators A and C are never called
        $authenticatorA->expects($this->never())
            ->method('authenticate');
        $authenticatorC->expects($this->never())
            ->method('authenticate');

        $mockedUser = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $authenticatorB->expects($this->once())
            ->method('authenticate')
            ->with($enteredCredentials, $this->userProvider)
            ->will($this->returnValue($mockedUser));
        $authedToken = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $authenticatorB->expects($this->once())
            ->method('createAuthenticatedToken')
            ->with($mockedUser, $providerKey)
            ->will($this->returnValue($authedToken));

        // user checker should be called
        $this->userChecker->expects($this->once())
            ->method('checkPreAuth')
            ->with($mockedUser);
        $this->userChecker->expects($this->once())
            ->method('checkPostAuth')
            ->with($mockedUser);

        $provider = new GuardAuthenticationProvider($authenticators, $this->userProvider, $providerKey, $this->userChecker);
        $actualAuthedToken = $provider->authenticate($this->nonAuthedToken);
        $this->assertSame($authedToken, $actualAuthedToken);
    }

    protected function setUp()
    {
        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');
        $this->nonAuthedToken = $this->getMockBuilder('Symfony\Component\Security\Guard\Token\NonAuthenticatedGuardToken')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        $this->userProvider = null;
        $this->userChecker = null;
        $this->nonAuthedToken = null;
    }
}
