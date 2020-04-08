<?php 
/**
 */
return [
    'module_init'=> [
        'wstmart\\home\\behavior\\InitConfig'
    ],
    'action_begin'=> [
        'wstmart\\home\\behavior\\ListenProtectedUrl'
    ]
]
?>