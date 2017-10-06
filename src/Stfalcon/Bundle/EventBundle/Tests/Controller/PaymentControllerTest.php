<?php

namespace Stfalcon\Bundle\EventBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Stfalcon\Bundle\EventBundle\Entity\Payment;

class PaymentControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;
    /** @var  EntityManager */
    protected $em;

    protected $translator;

    public function setUp()
    {
        $this->loadFixtures(
            [
                'Stfalcon\Bundle\EventBundle\DataFixtures\ORM\LoadEventData',
                'Application\Bundle\UserBundle\DataFixtures\ORM\LoadUserData',
                'Stfalcon\Bundle\EventBundle\DataFixtures\ORM\LoadPromoCodeData',
//                'Stfalcon\Bundle\EventBundle\DataFixtures\ORM\LoadTicketData',
            ]
        );
        $this->client = $this->createClient();
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->translator = $this->getContainer()->get('translator');
    }

    /**
     * destroy
     */
    public function tearDown()
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
    }
    /**
     * Check create payment for ticket (without any discount)
     */
    public function testCreatePayment()
    {
        $user = $this->em->getRepository('ApplicationUserBundle:User')->findOneBy(['email' => 'user@fwdays.com']);

        /** start Login */
        $crawler = $this->client->request('GET', '/login');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('<button class="btn btn--primary btn--lg form-col__btn" type="submit">'.
            $this->translator->trans( 'menu.login').'</button>', $crawler->html());
        $form = $crawler->selectButton($this->translator->trans( 'menu.login'))->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = 'qwerty';
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        /** end Login */

        $crawler = $this->client->request('GET', '/');
        $this->assertGreaterThan(0, $crawler->filter('a:contains("'.$this->translator->trans('ticket.status.pay').'")')->count());

//        $this->client->request('GET','/event/zend-framework-day-2011/take-part');
//        $crawler = $this->client->request('GET','/event/zend-framework-day-2011/pay');
//
//        $this->assertEquals(1, $crawler->filter('h2:contains("Оплата участия в конференции Zend Framework Day")')->count());
//        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'Zend Framework Day']);
//        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);
//        $this->assertNotNull($payment);
//
//        $this->assertEquals($event->getCost(), $payment->getAmount());
    }
    /**
     * User get config discount
     */
    public function testCreatePaymentWithDiscount()
    {
        $user = $this->em->getRepository('ApplicationUserBundle:User')->findOneBy(['email' => 'user@fwdays.com']);

        /** start Login */
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = 'qwerty';
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        $this->assertEquals(1, $crawler->filter('span:contains("user@fwdays.com")')->count());
        /** end Login */

        $this->client->request('GET','/event/zend-framework-day-2011/take-part');
        $crawler = $this->client->request('GET','/event/zend-framework-day-2011/pay');
        $this->assertEquals(1, $crawler->filter('h2:contains("Оплата участия в конференции Zend Framework Day")')->count());
        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'Zend Framework Day']);
        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);
        $this->assertNotNull($payment);
        $payment->setStatus(Payment::STATUS_PAID);
        $this->em->flush();

        $this->client->request('GET','/event/php-day-2017/take-part');
        $crawler = $this->client->request('GET','/event/php-day-2017/pay');

        $this->assertEquals(1, $crawler->filter('h2:contains("Оплата участия в конференции PHP Day")')->count());
        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'PHP Day']);
        $this->em->detach($payment);
        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);
        $this->assertNotNull($payment);

        $paymentsConfig = $this->getContainer()->getParameter('stfalcon_event.config');
        $discount = (float)$paymentsConfig['discount'];

        $discountAmount =  $event->getCost() * $discount;
        $this->assertEquals($payment->getAmount(), $event->getCost() - $discountAmount);
    }

    /**
     * User enter promo code and get discount
     */
    public function testCreatePaymentWithPromoCode()
    {
        $user = $this->em->getRepository('ApplicationUserBundle:User')->findOneBy(['email' => 'user@fwdays.com']);

        /** start Login */
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = 'qwerty';
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        $this->assertEquals(1, $crawler->filter('span:contains("user@fwdays.com")')->count());
        /** end Login */

        $this->client->request('GET','/event/zend-framework-day-2011/take-part');
        $crawler = $this->client->request('GET','/event/zend-framework-day-2011/pay');

        $promoCode = $this->em->getRepository('StfalconEventBundle:PromoCode')->findOneBy(['code' => 'Promo code for ZFDays']);
        $form = $crawler->selectButton('Применить')->form();
        $form['stfalcon_event_promo_code[code]'] = $promoCode->getCode();
        $this->client->submit($form);
        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'Zend Framework Day']);
        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);

        $discountAmount =  $event->getCost() * $promoCode->getDiscountAmount() / 100;
        $this->assertEquals($payment->getAmount(), $event->getCost() - $discountAmount);
    }

    /**
     * User enter promo code but config discount is better
     */
    public function testCreatePaymentWithPromoCodeAndBetterDiscount()
    {
        $user = $this->em->getRepository('ApplicationUserBundle:User')->findOneBy(['email' => 'user@fwdays.com']);

        /** start Login */
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = 'qwerty';
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        $this->assertEquals(1, $crawler->filter('span:contains("user@fwdays.com")')->count());
        /** end Login */

        $this->client->request('GET','/event/php-day-2017/take-part');
        $crawler = $this->client->request('GET','/event/php-day-2017/pay');
        $this->assertEquals(1, $crawler->filter('h2:contains("Оплата участия в конференции PHP Day")')->count());
        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'PHP Day']);

        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);
        $this->assertNotNull($payment);
        $payment->setStatus(Payment::STATUS_PAID);
        $this->em->flush();

        $this->client->request('GET','/event/zend-framework-day-2011/take-part');
        $crawler = $this->client->request('GET','/event/zend-framework-day-2011/pay');
        $this->assertEquals(1, $crawler->filter('h2:contains("Оплата участия в конференции Zend Framework Day")')->count());

        $promoCode = $this->em->getRepository('StfalconEventBundle:PromoCode')->findOneBy(['code' => 'Promo code for ZFDays']);
        $form = $crawler->selectButton('Применить')->form();
        $form['stfalcon_event_promo_code[code]'] = $promoCode->getCode();
        $this->client->submit($form);
        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'Zend Framework Day']);
        $this->em->detach($payment);
        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);

        $paymentsConfig = $this->getContainer()->getParameter('stfalcon_event.config');
        $discount = (float)$paymentsConfig['discount'];

        $discountAmount =  $event->getCost() * $discount;
        $this->assertEquals($payment->getAmount(), $event->getCost() - $discountAmount);
    }

    /**
     * User enter promo code with better from config discount
     */
    public function testCreatePaymentWithBetterPromoCodeDiscount()
    {
        $user = $this->em->getRepository('ApplicationUserBundle:User')->findOneBy(['email' => 'user@fwdays.com']);

        /** start Login */
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = 'qwerty';
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        $this->assertEquals(1, $crawler->filter('span:contains("user@fwdays.com")')->count());
        /** end Login */

        $this->client->request('GET','/event/php-day-2017/take-part');
        $crawler = $this->client->request('GET','/event/php-day-2017/pay');
        $this->assertEquals(1, $crawler->filter('h2:contains("Оплата участия в конференции PHP Day")')->count());
        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'PHP Day']);

        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);
        $this->assertNotNull($payment);
        $payment->setStatus(Payment::STATUS_PAID);
        $this->em->flush();

        $this->client->request('GET','/event/zend-framework-day-2011/take-part');
        $crawler = $this->client->request('GET','/event/zend-framework-day-2011/pay');
        $this->assertEquals(1, $crawler->filter('h2:contains("Оплата участия в конференции Zend Framework Day")')->count());

        $promoCode = $this->em->getRepository('StfalconEventBundle:PromoCode')->findOneBy(['code' => 'Promo code for ZFDays']);
        $promoCode->setDiscountAmount(30);
        $this->em->flush();
        $this->em->refresh($promoCode);
        $form = $crawler->selectButton('Применить')->form();
        $form['stfalcon_event_promo_code[code]'] = $promoCode->getCode();
        $this->client->submit($form);
        $event = $this->em->getRepository('StfalconEventBundle:Event')->findOneBy(['name' => 'Zend Framework Day']);
        $this->em->detach($payment);
        $payment = $this->em->getRepository('StfalconEventBundle:Payment')->findPaymentByUserAndEvent($user, $event);

        $discountAmount =  $event->getCost() * $promoCode->getDiscountAmount() / 100;
        $this->assertEquals($payment->getAmount(), $event->getCost() - $discountAmount);
    }
}