<?php

return [
    /**
     * You can specify custom connection, by default it use config/database.php default
     */
    'connection' => null,

    //    /**
    //     * You can specify which table you want to index, by default all tables except laravel internals
    //     */
    //    'tables'                => [
    //        'includes' => ['*'],
    //        'excludes' => ['migrations', 'failed_jobs', 'personal_access_tokens', 'password_resets'],
    //    ],

    /**
     * Choose action between:
     * delete - Drop documents from Elasticsearch index when he is soft deleted
     * update - Reindex documents from Elasticsearch index with deleted_as field
     *
     * @TODO add ignore and update only when update
     */
    'action_on_soft_delete' => 'delete',

    /**
     * @suggestion Possibility to define multiple output and choose elasticsearch by default
     */
    'output' => [
        'elasticsearch' => [
            'hosts' => ['http://elasticsearch:9200'],
            'username' => null,
            'password' => null,
        ],
    ],

    /**
     * @TODO Doc
     *
     * @WIP Relation (O2O,M2M) - INSERT[OK] - UPDATE - DELETE
     *
     * @DONE rename field with alias
     *
     * @TODO Possibility to define elastic index settings
     * @TODO Possibility to define index name using doc field (ex: "users_{{role}}")
     *
     * @suggestion Computed or scripted field
     * @suggestion Allow to set other output than elasticsearch for each index
     * @suggestion Allow to set other input than "connection" for each index
     * @suggestion Implement via model instead of table with a trait??
     * @suggestion Plugin system to use custom PHP methods to reformat document before indexing??
     */
    'indices' => [
        /* AUTHORS */
        [
            'table' => 'users',
            'index' => 'authors',
            'fields' => ['id', 'name'],
            'relations' => [
                [
                    'table' => 'posts',
                    'es_name' => 'posts',
                    'type' => 'many_to_many',
                    'foreign_key' => ['local' => 'user_id', 'parent' => 'id'],
                    'fields' => ['id', 'name', 'description', 'created_at'],
                ],
            ],
        ],
        /* POSTS */
        [
            'table' => 'posts',
            'index' => 'posts',
            'fields' => ['id', 'name', 'description', 'created_at'],
            'relations' => [
                [
                    'table' => 'users',
                    'es_name' => 'author',
                    'type' => 'one_to_one',
                    'foreign_key' => ['local' => 'id', 'parent' => 'user_id'],
                    'fields' => ['id', 'name'],
                ],
                [
                    'table' => 'users',
                    'es_name' => 'author2',
                    'type' => 'one_to_one',
                    'foreign_key' => ['local' => 'id', 'parent' => 'user_id'],
                    'fields' => ['id', 'name'],
                ],
                [
                    'table' => 'users',
                    'es_name' => 'author3',
                    'type' => 'one_to_one',
                    'foreign_key' => ['local' => 'id', 'parent' => 'user_id'],
                    'fields' => ['id', 'name'],
                ],
                [
                    'table' => 'users',
                    'es_name' => 'author4',
                    'type' => 'one_to_one',
                    'foreign_key' => ['local' => 'id', 'parent' => 'user_id'],
                    'fields' => ['id', 'name'],
                ],
                [
                    'table' => 'users',
                    'es_name' => 'author4',
                    'type' => 'one_to_one',
                    'foreign_key' => ['local' => 'id', 'parent' => 'user_id'],
                    'fields' => ['id', 'name'],
                ],
            ],
        ],
        /**Relative to suggestions*/
        //        'posts_{{category}}' => [
        //            'input'     => 'postgresql2',
        //            'output'    => 'elasticsearch-eu-1',
        //            'table'     => 'posts',
        //            'plugins'   => [CustomPostsProcessing::class],
        //            'fields'    => [
        //                'name',
        //                [
        //                    'db_field' => 'user_id',
        //                    'es_field'    => 'user_ref',
        //                ],
        //            ],
        //            'relations' => [
        //                'user' => [ //It's necessary update/delete on users table trigger reindex on this index
        //                    'type'        => 'one-to-one', // Or many-to-many
        //                    'table'       => 'users',
        //                    'foreign_key' => 'user_id',
        //                    'local_key'   => 'id',
        //                    'fields'      => [
        //                        '*',
        //                        [
        //                            'db_field' => [
        //                                'raw'    => "CONCAT(firstname,' ',lastname)",
        //                                'raw'    => "CONCAT(res->>'firstname',' ',res->>'lastname')", //Using json approach
        //                                'always' => ['firstname', 'lastname'], //Necessary to track change
        //                            ],
        //                            'alias'    => 'fullname',
        //                        ],
        //                    ]
        //                ],
        //
        //            ]
        //        ],
    ],
];
