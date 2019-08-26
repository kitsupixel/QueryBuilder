# QueryBuilder
A PHP MySQL QueryBuilder

#### Extending the class

    <?php
        class User extends QueryBuilder {
          protected $table = 'users';
        }
        
        $user = new User();
        $user->name = 'Nelson';
        $newId = $user->save();
    ?>
   
