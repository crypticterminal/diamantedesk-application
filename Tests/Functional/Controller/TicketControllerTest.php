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
namespace Diamante\DeskBundle\Tests\Functional\Controller;

use Diamante\DeskBundle\Model\Ticket\Priority;
use Diamante\DeskBundle\Model\Ticket\Source;
use Diamante\UserBundle\Model\User;
use Diamante\DeskBundle\Model\Ticket\Status;
use Symfony\Component\DomCrawler\Form;

class TicketControllerTest extends AbstractController
{

    public function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader('admin', '123123q'), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    public function testDelete()
    {
        $ticket        = $this->chooseTicketFromGrid();
        $ticketDeleteUrl = $this->getUrl('diamante_ticket_delete', array('key' => $ticket['key']));
        $this->client->followRedirects(false);

        $crawler       = $this->client->request('GET', $ticketDeleteUrl);
        $response      = $this->client->getResponse();

        $this->client->request(
            'GET',
            $this->getUrl('diamante_ticket_view', array('key' => $ticket['key']))
        );
        $viewResponse = $this->client->getResponse();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(404, $viewResponse->getStatusCode());
    }

    public function testCreateTicketWithTag()
    {
        $branch = $this->chooseBranchFromGrid();
        $crawler = $this->client->request(
            'GET', $this->getUrl('diamante_ticket_create',  array('id' => $branch['id']))
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['diamante_ticket_form[branch]']      = $branch['id'];
        $form['diamante_ticket_form[subject]']     = 'Test Ticket';
        $form['diamante_ticket_form[description]'] = 'Test Description';
        $form['diamante_ticket_form[status]']      = Status::OPEN;
        $form['diamante_ticket_form[priority]']    = Priority::PRIORITY_LOW;
        $form['diamante_ticket_form[source]']      = Source::PHONE;
        $form['diamante_ticket_form[reporter]']    = User::TYPE_ORO . User::DELIMITER .  1;
        $form['diamante_ticket_form[assignee]']    = 1;
        $form['diamante_ticket_form[tags][autocomplete]']    = '';
        $form['diamante_ticket_form[tags][all]']    = '[{"id":"tag_1","name":"test tag","owner":true,"notSaved":true,"moreOwners":false,"url":""}]';
        $form['diamante_ticket_form[tags][owner]']    = '[{"id":"tag_1","name":"test tag","owner":true,"notSaved":true,"moreOwners":false,"url":""}]';
        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully created.", $crawler->html());
    }

    public function testUpdateTag()
    {
        $ticket = $this->chooseTicketFromGrid();
        $ticketUpdateUrl = $this->getUrl('diamante_ticket_update', array('key' => $ticket['key']));
        $crawler = $this->client->request('GET', $ticketUpdateUrl);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['diamante_ticket_update_form[tags][autocomplete]'] = '';
        $form['diamante_ticket_update_form[tags][all]']
            = '[{"name":"test tag 1","id":1,"url":"/tag/search/1","owner":false,"moreOwners":false,"notSaved":false},{"name":"test tag 2","id":2,"url":"/tag/search/2","owner":false,"moreOwners":false,"notSaved":false},{"id":"tag_1","name":"test tag 3","owner":true,"notSaved":true,"moreOwners":false,"url":""}]';
        $form['diamante_ticket_update_form[tags][owner]']
            = '[{"id":"tag_1","name":"test tag 3","owner":true,"notSaved":true,"moreOwners":false,"url":""}]';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully saved.", $crawler->html());
    }

    public function testDeleteTag()
    {
        $ticket = $this->chooseTicketFromGrid();
        $ticketUpdateUrl = $this->getUrl('diamante_ticket_update', array('key' => $ticket['key']));
        $crawler = $this->client->request('GET', $ticketUpdateUrl);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['diamante_ticket_update_form[tags][autocomplete]'] = '';
        $form['diamante_ticket_update_form[tags][all]'] = '[]';
        $form['diamante_ticket_update_form[tags][owner]'] = '[]';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully saved.", $crawler->html());
    }

    public function testCreateWithoutBranchId()
    {
        $crawler = $this->client->request(
            'GET', $this->getUrl('diamante_ticket_create')
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $this->assertEquals($form['diamante_ticket_form[branch]']->getValue(), "");
        $this->assertNotEquals($form['diamante_ticket_form[reporter]']->getValue(), "");
        $this->assertNotEquals($form['diamante_ticket_form[assignee]']->getValue(), $this->equalTo(""));

        $form['diamante_ticket_form[branch]']      = $this->chooseBranchFromGrid()['id'];
        $form['diamante_ticket_form[subject]']     = 'Test Ticket';
        $form['diamante_ticket_form[description]'] = 'Test Description';
        $form['diamante_ticket_form[status]']      = 'open';
        $form['diamante_ticket_form[priority]']    = Priority::PRIORITY_MEDIUM;
        $form['diamante_ticket_form[source]']      = Source::PHONE;
        $form['diamante_ticket_form[reporter]']    = User::TYPE_ORO . User::DELIMITER .  1;
        $form['diamante_ticket_form[assignee]']    = 1;
        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully created.", $crawler->html());
    }

    public function testCreateWithBranchId()
    {
        $branch = $this->chooseBranchFromGrid();
        $crawler = $this->client->request(
            'GET', $this->getUrl('diamante_ticket_create',  array('id' => $branch['id']))
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        //$this->assertEquals($form['diamante_ticket_form[branch]'], $branch['id']);
        //$this->assertNotEquals($form['diamante_ticket_form[reporter]'], "");

        $form['diamante_ticket_form[branch]']      = $branch['id'];
        $form['diamante_ticket_form[subject]']     = 'Test Ticket';
        $form['diamante_ticket_form[description]'] = 'Test Description';
        $form['diamante_ticket_form[status]']      = Status::OPEN;
        $form['diamante_ticket_form[priority]']    = Priority::PRIORITY_LOW;
        $form['diamante_ticket_form[source]']      = Source::PHONE;
        $form['diamante_ticket_form[reporter]']    = User::TYPE_ORO . User::DELIMITER .  1;
        $form['diamante_ticket_form[assignee]']    = 1;
        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully created.", $crawler->html());
    }

    public function testList()
    {
        $crawler  = $this->client->request('GET', $this->getUrl('diamante_ticket_list'));
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/html; charset=UTF-8'));
        $this->assertTrue($crawler->filter('html:contains("Tickets")')->count() >= 1);
    }

    public function testView()
    {
        $ticket        = $this->chooseTicketFromGrid();
        $ticketViewUrl = $this->getUrl('diamante_ticket_view', array('key' => $ticket['key']));
        $crawler     = $this->client->request('GET', $ticketViewUrl);
        $response    = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/html; charset=UTF-8'));
        $this->assertTrue($crawler->filter('html:contains("Ticket Details")')->count() == 1);

        $this->assertTrue($crawler->filter('html:contains("Attachments")')->count() == 1);
        $this->assertTrue($crawler->filter('html:contains("Comments")')->count() == 1);
    }

    public function testChangeStatus()
    {
        $ticket              = $this->chooseTicketFromGrid();
        $updateStatusFormUrl = $this->getUrl('diamante_ticket_status_change', array('id' => $ticket['id']));
        $crawler             = $this->client->request('GET', $updateStatusFormUrl);

        $this->assertEquals("Cancel", $crawler->selectButton('Cancel')->html());
        $this->assertEquals("Change", $crawler->selectButton('Change')->html());
    }


    public function testMove()
    {
        $ticket              = $this->chooseTicketFromGrid();
        $moveFormUrl = $this->getUrl('diamante_ticket_move', array('id' => $ticket['id']));
        $crawler             = $this->client->request('GET', $moveFormUrl);

        $this->assertEquals("Cancel", $crawler->selectButton('Cancel')->html());
        $this->assertEquals("Change", $crawler->selectButton('Change')->html());
    }

    public function testUpdate()
    {
        $ticket        = $this->chooseTicketFromGrid();
        $ticketUpdateUrl = $this->getUrl('diamante_ticket_update', array('key' => $ticket['key']));
        $crawler       = $this->client->request('GET', $ticketUpdateUrl);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['diamante_ticket_update_form[subject]'] = 'Just Changed';
        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully saved.", $crawler->html());
    }

    public function testUpdatePriority()
    {
        $ticket        = $this->chooseTicketFromGrid();
        $ticketUpdateUrl = $this->getUrl('diamante_ticket_update', array('key' => $ticket['key']));
        $crawler       = $this->client->request('GET', $ticketUpdateUrl);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['diamante_ticket_update_form[priority]'] = Priority::PRIORITY_HIGH;
        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully saved.", $crawler->html());
    }

    public function testUpdateSource()
    {
        $ticket        = $this->chooseTicketFromGrid();
        $ticketUpdateUrl = $this->getUrl('diamante_ticket_update', array('key' => $ticket['key']));
        $crawler       = $this->client->request('GET', $ticketUpdateUrl);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['diamante_ticket_update_form[source]'] = Source::EMAIL;
        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully saved.", $crawler->html());
    }

    public function testAssign()
    {
        $ticket        = $this->chooseTicketFromGrid();
        $ticketAssignUrl = $this->getUrl('diamante_ticket_assign', array('id' => $ticket['id']));
        $crawler = $this->client->request('GET', $ticketAssignUrl);

        $this->assertEquals("Cancel", $crawler->selectButton('Cancel')->html());
        $this->assertEquals("Change", $crawler->selectButton('Change')->html());
    }

    public function testCreateWithoutAssigneeId()
    {
        $branch = $this->chooseBranchFromGrid();
        $crawler = $this->client->request(
            'GET', $this->getUrl('diamante_ticket_create',  array('id' => $branch['id']))
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['diamante_ticket_form[branch]']      = $branch['id'];
        $form['diamante_ticket_form[subject]']     = 'Test Ticket Without Assignee';
        $form['diamante_ticket_form[description]'] = 'Test Description';
        $form['diamante_ticket_form[status]']      = Status::OPEN;
        $form['diamante_ticket_form[priority]']    = Priority::PRIORITY_LOW;
        $form['diamante_ticket_form[source]']      = Source::PHONE;
        $form['diamante_ticket_form[reporter]']    = User::TYPE_ORO . User::DELIMITER . 1;
        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Ticket successfully created.", $crawler->html());
    }

    public function testWatch()
    {
        $ticket = $this->chooseTicketFromGrid();
        $ticketWatchUrl = $this->getUrl('diamante_ticket_watch', ['ticket' => $ticket['id']]);

        $this->client->followRedirects(true);
        $crawler = $this->client->request('GET', $ticketWatchUrl);

        $this->assertContains("Now you watching the ticket.", $crawler->html());
    }

    public function testUnWatch()
    {
        $ticket = $this->chooseTicketFromGrid();
        $ticketWatchUrl = $this->getUrl('diamante_ticket_unwatch', ['ticket' => $ticket['id']]);

        $this->client->followRedirects(true);
        $crawler = $this->client->request('GET', $ticketWatchUrl);

        $this->assertContains("You successfully unsubscribe from watching of ticket", $crawler->html());
    }

    private function chooseBranchFromGrid()
    {
        $response = $this->requestGrid(
            'diamante-branch-grid'
        );

        $this->assertEquals(200, $response->getStatusCode());

        $result = $this->jsonToArray($response->getContent());
        return current($result['data']);
    }

    private function chooseTicketFromGrid()
    {
        $response = $this->requestGrid(
            'diamante-ticket-grid'
        );

        $this->assertEquals(200, $response->getStatusCode());

        $result = $this->jsonToArray($response->getContent());

        return current($result['data']);
    }

    /**
     * @group mass
     */
    public function testMassAssign()
    {
        $ticketMassAssignUrl = $this->getUrl('diamante_ticket_mass_assign');
        $crawler = $this->client->request('GET', $ticketMassAssignUrl);

        $this->assertEquals("Cancel", $crawler->selectButton('Cancel')->html());
        $this->assertEquals("Change", $crawler->selectButton('Change')->html());
    }


    /**
     * @group mass
     */
    public function testMassChangeStatus()
    {
        $ticketMassChangeUrl = $this->getUrl('diamante_ticket_mass_status_change');
        $crawler = $this->client->request('GET', $ticketMassChangeUrl);

        $this->assertEquals("Cancel", $crawler->selectButton('Cancel')->html());
        $this->assertEquals("Change", $crawler->selectButton('Change')->html());
    }

    /**
     * @group mass
     */
    public function testMassMove()
    {
        $ticketMassMoveUrl = $this->getUrl('diamante_ticket_mass_move');
        $crawler = $this->client->request('GET', $ticketMassMoveUrl);

        $this->assertEquals("Cancel", $crawler->selectButton('Cancel')->html());
        $this->assertEquals("Change", $crawler->selectButton('Change')->html());
    }

    /**
     * @group mass
     */
    public function testMassWatch()
    {
        $ticketMassWatchUrl = $this->getUrl('diamante_ticket_mass_add_watcher');
        $crawler = $this->client->request('GET', $ticketMassWatchUrl);

        $this->assertEquals("Cancel", $crawler->selectButton('Cancel')->html());
        $this->assertEquals("Add", $crawler->selectButton('Add')->html());
    }


    /**
     * @group mass
     */
    public function testMassAssignSubmit()
    {

        $ticketMassAssignSubmitUrl = $this->getUrl('diamante_ticket_mass_assign',
                                                  ['no_redirect' => 'false', 'ids' =>'1, 2',
                                                   'assignee' => '1']);

        $this->client->request('GET', $ticketMassAssignSubmitUrl);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @group mass
     */
    public function testMassChangeStatusSubmit()
    {
        $ticketMassChangeSubmitUrl = $this->getUrl('diamante_ticket_mass_status_change',
                                                  ['no_redirect' => 'false', 'ids' =>'1, 2', 'status' => 'new']);

        $this->client->request('GET', $ticketMassChangeSubmitUrl);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @group mass
     */
    public function testMassMoveSubmit()
    {
        $ticketMassMoveSubmitUrl = $this->getUrl('diamante_ticket_mass_move', ['no_redirect' => 'false',
                                                 'ids' =>'1, 2', 'branch' => '1']);

        $this->client->request('GET', $ticketMassMoveSubmitUrl);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @group mass
     */
    public function testMassWatchSubmit()
    {
        $ticketMassWatchSubmitUrl = $this->getUrl('diamante_ticket_mass_add_watcher',
                                                  ['no_redirect' => 'false', 'ids' =>'1, 2',
                                                   'watcher' => '1']);

        $this->client->request('GET', $ticketMassWatchSubmitUrl);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }


}
