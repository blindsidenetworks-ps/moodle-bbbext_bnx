<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace bbbext_bnx\task;

use bbbext_bnx\reminders_utils;
use bbbext_bnx\subscription_utils;
use core_date;
use DateInterval;
use DateTime;
use mod_bigbluebuttonbn\instance;

/**
 * Tests for check_emails_reminder scheduled task.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \bbbext_bnx\task\check_emails_reminder
 */
final class check_email_reminder_test extends \advanced_testcase {
    /** @var instance|null */
    private $bbbinstance = null;

    /** @var array|null */
    private $students = null;

    /** @var \stdClass|null */
    private $teacher = null;

    /**
     * Data provider for test_reminder_enabled_sent.
     *
     * @return array[]
     */
    public static function reminder_enabled_sent_provider(): array {
        return [
            '1 Hour before' => [
                'reminderinterval' => reminders_utils::ONE_HOUR,
                'openingtimespan' => 'PT1H',
                'expectedemails' => [
                    'teacher@example.com',
                    'username2@example.com',
                    'username3@example.com',
                ],
                'nosubscriptions' => ['username1@example.com'],
            ],
            '1 Day before' => [
                'reminderinterval' => reminders_utils::ONE_HOUR,
                'openingtimespan' => 'P1D',
                'expectedemails' => [],
            ],
        ];
    }

    /**
     * Data provider for test_reminder_guest_sent.
     *
     * @return array[]
     */
    public static function reminder_enabled_with_guest_sent_provider(): array {
        return [
            '1 Hour before' => [
                'reminderinterval' => reminders_utils::ONE_HOUR,
                'openingtimespan' => 'PT1H',
                'guests' => ['guest@email.com'],
                'expectedemails' => [
                    'guest@email.com',
                    'username2@example.com',
                    'username3@example.com',
                    'teacher@example.com',
                ],
                'nosubscriptions' => ['username1@example.com'],
            ],
            '1 Day before' => [
                'reminderinterval' => reminders_utils::ONE_HOUR,
                'openingtimespan' => 'P1D',
                'guests' => ['guest@email.com'],
                'expectedemails' => [],
            ],
            '1 Hour before guest unsubscribed' => [
                'reminderinterval' => reminders_utils::ONE_HOUR,
                'openingtimespan' => 'PT1H',
                'guests' => ['guest@email.com'],
                'expectedemails' => [
                    'username1@example.com',
                    'username2@example.com',
                    'username3@example.com',
                    'teacher@example.com',
                ],
                'nosubscriptions' => ['guest@email.com'],
            ],
        ];
    }

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();

        $this->preventResetByRollback();
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bbbgenerator = $generator->get_plugin_generator('mod_bigbluebuttonbn');
        $time = new DateTime('now', core_date::get_user_timezone_object());
        $time->add(new DateInterval('PT1H'));
        $this->bbbinstance = instance::get_from_instanceid($bbbgenerator->create_instance([
            'course' => $course,
        ])->id);

        for ($i = 1; $i < 4; $i++) {
            $this->students[] = $generator->create_and_enrol(
                $course,
                'student',
                ['email' => 'username' . $i . '@example.com', 'username' => 'username' . $i]
            );
        }
        $this->teacher = $generator->create_and_enrol(
            $course,
            'teacher',
            ['email' => 'teacher@example.com', 'username' => 'teacher']
        );
    }

    /**
     * Test that enrolled users receive reminders.
     *
     * @param string $reminderinterval
     * @param string $openingtimespan
     * @param array $expectedemails
     * @param array|null $nosubscriptions
     * @return void
     * @dataProvider reminder_enabled_sent_provider
     * @covers ::execute
     */
    public function test_reminder_enabled_sent(
        string $reminderinterval,
        string $openingtimespan,
        array $expectedemails,
        ?array $nosubscriptions = []
    ): void {
        global $DB;
        $emailsink = $this->redirectEmails();
        \phpunit_util::stop_message_redirection();

        $bnxgenerator = $this->getDataGenerator()->get_plugin_generator('bbbext_bnx');
        $bnxgenerator->enable_reminder($this->bbbinstance->get_instance_id());
        $bnxgenerator->add_reminder([
            'bigbluebuttonbnid' => $this->bbbinstance->get_instance_id(),
            'timespan' => $reminderinterval,
        ]);
        $time = new DateTime('now', core_date::get_user_timezone_object());
        $time->add(new DateInterval($openingtimespan));
        $DB->set_field(
            'bigbluebuttonbn',
            'openingtime',
            $time->getTimestamp(),
            ['id' => $this->bbbinstance->get_instance_id()]
        );

        foreach ($this->students as $student) {
            if (in_array($student->email, $nosubscriptions)) {
                subscription_utils::change_reminder_subcription_user(false, $student->id, $this->bbbinstance);
            }
        }

        $task = new check_emails_reminder();
        $task->execute();
        $task->execute(); // Execute twice to verify no duplicate sends.
        $this->runAdhocTasks();
        $this->assertEquals(count($expectedemails), $emailsink->count());
        $emailsto = array_map(fn($email) => $email->to, $emailsink->get_messages());
        sort($expectedemails);
        sort($emailsto);
        $this->assertEquals($expectedemails, $emailsto);
    }

    /**
     * Test that guests receive reminders when enabled.
     *
     * @param string $reminderinterval
     * @param string $openingtimespan
     * @param array $guests
     * @param array $expectedemails
     * @param array|null $nosubscriptions
     * @return void
     * @dataProvider reminder_enabled_with_guest_sent_provider
     * @covers ::execute
     */
    public function test_reminder_guest_sent(
        string $reminderinterval,
        string $openingtimespan,
        array $guests,
        array $expectedemails,
        ?array $nosubscriptions = []
    ): void {
        global $DB;
        $emailsink = $this->redirectEmails();

        $bnxgenerator = $this->getDataGenerator()->get_plugin_generator('bbbext_bnx');
        $bnxgenerator->enable_reminder($this->bbbinstance->get_instance_id());
        foreach ($guests as $guest) {
            $bnxgenerator->add_guest([
                'bigbluebuttonbnid' => $this->bbbinstance->get_instance_id(),
                'email' => $guest,
            ]);
        }
        $bnxgenerator->enable_reminder_for_guest($this->bbbinstance->get_instance_id());
        $bnxgenerator->add_reminder([
            'bigbluebuttonbnid' => $this->bbbinstance->get_instance_id(),
            'timespan' => $reminderinterval,
        ]);
        $time = new DateTime('now', core_date::get_user_timezone_object());
        $time->add(new DateInterval($openingtimespan));
        $DB->set_field(
            'bigbluebuttonbn',
            'openingtime',
            $time->getTimestamp(),
            ['id' => $this->bbbinstance->get_instance_id()]
        );

        foreach ($this->students as $student) {
            if (in_array($student->email, $nosubscriptions)) {
                subscription_utils::change_reminder_subcription_user(false, $student->id, $this->bbbinstance);
            }
        }
        foreach ($guests as $guestemail) {
            if (in_array($guestemail, $nosubscriptions)) {
                subscription_utils::change_reminder_subcription_email(false, $guestemail, $this->bbbinstance);
            }
        }

        $task = new check_emails_reminder();
        $task->execute();
        $task->execute(); // Execute twice to verify no duplicate sends.
        $this->runAdhocTasks();
        $this->assertEquals(count($expectedemails), $emailsink->count());
        $emailsto = array_map(fn($email) => $email->to, $emailsink->get_messages());
        sort($expectedemails);
        sort($emailsto);
        $this->assertEquals($expectedemails, $emailsto);
    }

    /**
     * Test that no emails are sent when the plugin is disabled.
     *
     * @return void
     * @covers ::execute
     */
    public function test_reminder_disabled_plugin(): void {
        global $DB;
        $emailsink = $this->redirectEmails();

        $bnxgenerator = $this->getDataGenerator()->get_plugin_generator('bbbext_bnx');
        $bnxgenerator->enable_reminder($this->bbbinstance->get_instance_id());
        $bnxgenerator->add_reminder([
            'bigbluebuttonbnid' => $this->bbbinstance->get_instance_id(),
            'timespan' => reminders_utils::ONE_HOUR,
        ]);
        $time = new DateTime('now', core_date::get_user_timezone_object());
        $time->add(new DateInterval('PT1H'));
        $DB->set_field(
            'bigbluebuttonbn',
            'openingtime',
            $time->getTimestamp(),
            ['id' => $this->bbbinstance->get_instance_id()]
        );

        // Disable the plugin.
        set_config('disabled', 1, 'bbbext_bnx');
        \core_plugin_manager::reset_caches();

        $task = new check_emails_reminder();
        $task->execute();
        $this->runAdhocTasks();

        $this->assertEquals(0, $emailsink->count());

        // Re-enable for cleanup.
        unset_config('disabled', 'bbbext_bnx');
        \core_plugin_manager::reset_caches();
    }
}
