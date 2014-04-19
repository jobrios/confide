<?php namespace Zizaco\Confide;

use Mockery as m;
use PHPUnit_Framework_TestCase;

class CacheLoginThrottleServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Calls Mockery::close
     *
     * @return void
     */
    public function tearDown()
    {
        m::close();
    }

    public function testShouldThrottleIdentity()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $identity = ['email'=>'someone@somewhere.com','password'=>'123'];

        $throttleService = m::mock('Zizaco\Confide\CacheLoginThrottleService[countThrottle, parseIdentity]',[]);
        $throttleService->shouldAllowMockingProtectedMethods();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $throttleService->shouldReceive('parseIdentity')
            ->once()->with($identity)
            ->andReturn(serialize(['email'=>'someone@somewhere.com']));

        $throttleService->shouldReceive('countThrottle')
            ->once()->with(serialize(['email'=>'someone@somewhere.com']))
            ->andReturn(5);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(5, $throttleService->throttleIdentity($identity));
    }

    public function testShouldCheckIsThrottled()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $identity = ['email'=>'someone@somewhere.com','password'=>'123'];

        $throttleService = m::mock('Zizaco\Confide\CacheLoginThrottleService[countThrottle, parseIdentity]',[]);
        $throttleService->shouldAllowMockingProtectedMethods();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $throttleService->shouldReceive('parseIdentity')
            ->once()->with($identity)
            ->andReturn(serialize(['email'=>'someone@somewhere.com']));

        $throttleService->shouldReceive('countThrottle')
            ->once()->with(serialize(['email'=>'someone@somewhere.com']), 0)
            ->andReturn(5);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(5, $throttleService->isThrottled($identity));
    }

    public function parseIdentity()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $throttleService = m::mock('Zizaco\Confide\CacheLoginThrottleService[parseIdentity]',[]);
        $throttleService->shouldAllowMockingProtectedMethods();
        $identity = ['email'=>'someone@somewhere.com','password'=>'123'];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $throttleService->shouldReceive('parseIdentity')
            ->passthru();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(
            serialize(['email'=>'someone@somewhere.com']),
            $throttleService->parseIdentity($identity)
        );
    }

    public function testShouldCountThrottle()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $idString = serialize(['email'=>'someone@somewhere.com']);
        $cache = m::mock('Cache');
        $config = m::mock('Config');
        $app = ['cache'=>$cache, 'config'=>$config];

        $throttleService = m::mock('Zizaco\Confide\CacheLoginThrottleService[countThrottle]',[$app]);

        $ttl = 3;

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $cache->shouldReceive('get')
            ->once()->with('login_throttling:'.md5($idString), 0)
            ->andReturn(1);

        $config->shouldReceive('get')
            ->once()->with('confide::throttle_time_period')
            ->andReturn($ttl);

        $cache->shouldReceive('put')
            ->once()->with('login_throttling:'.md5($idString), 2, $ttl)
            ->andReturn(1);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertEquals(2, $throttleService->countThrottle($idString));
    }
}
