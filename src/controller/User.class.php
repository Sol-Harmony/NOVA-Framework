<?php
class User extends Controller
{
    // columns that are never sent to the view
    protected $hiddenColumns = ['password', 'password_hash', 'pass', 'pw', 'token'];

    public function showAction()
    {
        // /user        → all users
        // /user?id=5   → only the user with id 5
        // Show HTML view: /view/user/show.phtml
        $users = new UserModel();
        $id = $this->myrequest->getParam('id');

        if ($id !== null) {
            $user = $users->find($id);
            $rows = $user ? [$user] : [];
        } else {
            $rows = $users->all();
        }

        $this->data['users'] = array_map(
            fn($row) => array_diff_key($row->toArray(), array_flip($this->hiddenColumns)),
            $rows
        );
    }
}
