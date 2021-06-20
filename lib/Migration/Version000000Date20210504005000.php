<?php

declare(strict_types=1);

namespace OCA\Listman\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000000Date20210504005000 extends SimpleMigrationStep {

    /**
    * @param IOutput $output
    * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
    * @param array $options
    * @return null|ISchemaWrapper
    */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

				//-- list --
				//An email list has an ID, a title, a description, and a randomID
				//plus also a redirect URL and the user_id of the Nextcloud user who 
				//owns the list
        if (!$schema->hasTable('listman_list')) {
            $table = $schema->createTable('listman_list');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('title', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('desc', 'text', [
                'notnull' => true,
                'default' => ''
            ]);
            $table->addColumn('fromname', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('fromemail', 'string', [
                'notnull' => true,
                'length' => 100
            ]);
            $table->addColumn('buttontext', 'string', [
                'notnull' => true,
                'length' => 32
            ]);
            $table->addColumn('buttonlink', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('footer', 'string', [
                'notnull' => true,
                'length' => 500
            ]);
            $table->addColumn('randid', 'string', [
                'length' => 16,
                'notnull' => true,
            ]);
            $table->addColumn('redir', 'string', [
                'length' => 250,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['randid'], 'listman_list_randid_index');
            $table->addIndex(['user_id'], 'listman_list_user_id_index');
        }


				//-- list --
				//A member an ID, an email and name, plus a list ID to say
				//which list the member has joined. Also a state to say if
				//they have confirmed or not, and a confirm-code that's a
				//random string.
        if (!$schema->hasTable('listman_member')) {
            $table = $schema->createTable('listman_member');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('list_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('email', 'string', [
                'notnull' => true,
                'length' => 100
            ]);
            $table->addColumn('state', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('conf', 'string', [
                'length' => 32,
                'notnull' => true,
            ]);
            $table->addColumn('conf_expire', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'listman_member_user_id_index');
            $table->addIndex(['list_id'], 'listman_member_list_id_index');
        }

				// -- message --
				// A message has an ID, created_at, subject, a body, a list_ID.
        if (!$schema->hasTable('listman_message')) {
            $table = $schema->createTable('listman_message');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('randid', 'string', [
                'length' => 16,
                'notnull' => true,
            ]);
            $table->addColumn('list_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('subject', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('body', 'text', [
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('sendrate', 'integer', [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['list_id'], 'listman_message_list_id_index');
            $table->addIndex(['randid'], 'listman_message_randid_index');
            $table->addIndex(['user_id'], 'listman_message_user_id_index');
        }

				// -- sendjob --
				// A sendjob connects a message to a member, and tells us if
        // it's been sent, or bounced, or opened. Maybe not the last one.
				// a bit creepy watching for open-receipts.
				// state: 0-unsent, 1=sent, 2=bounced...
        if (!$schema->hasTable('listman_sendjob')) {
            $table = $schema->createTable('listman_sendjob');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('message_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('member_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('state', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['message_id','member_id']);
            $table->addIndex(['message_id'], 'listman_sjmessid_index');
            $table->addIndex(['member_id'], 'listman_sjmemid_index');
        }

				// -- react --
				// We keep track of counts of react-emojii. They are not
				// limited. If a person clicks a hundred times, we just
				// increment the number a hundred times. No tracking who
			  // clicked what.
        if (!$schema->hasTable('listman_react')) {
            $table = $schema->createTable('listman_react');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('message_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('symbol', 'string', [
                'notnull' => true,
            ]);
            $table->addColumn('count', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['message_id'], 'listman_react_index');
        }

        return $schema;
    }
}
