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
     * @TODO Relation (O2O,M2M)
     * @TODO rename field with alias
     * @TODO Possibility to define elastic index settings
     * @TODO Possibility to define index name using doc field (ex: "users_[role]")
     *
     * @suggestion Computed or scripted field
     * @suggestion Allow to set other output than elasticsearch for each index
     * @suggestion Allow to set other input than "connection" for each index
     * @suggestion Implement via model instead of table with a trait??
     * @suggestion Plugin system to use custom PHP methods to reformat document before indexing??
     */
    'indices' => [
        'users' => [
            'table' => 'users',
            'fields' => ['name'],
        ],
        'clients' => [
            'table' => 'users',
            'fields' => ['email', 'password'],
        ],
        'posts' => [
            'table' => 'posts',
            'fields' => ['*'],
        ],
        /**Relative to suggestions*/
        //        'posts_[user_ref]_[fullname]_[id]' => [
        //            'input'     => 'postgresql2',
        //            'output'    => 'elasticsearch-eu-1',
        //            'table'     => 'posts',
        //            'plugins'   => [CustomPostsProcessing::class],
        //            'fields'    => [
        //                'name',
        //                [
        //                    'db_field' => 'user_id',
        //                    'alias'    => 'user_ref',
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
