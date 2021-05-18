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
            $table->addColumn('randid', 'string', [
                'length' => 8,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'listman_list_user_id_index');
        }


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
                'length' => 200
            ]);
            $table->addColumn('state', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('conf', 'string', [
                'length' => 32,
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
        return $schema;
    }
}
