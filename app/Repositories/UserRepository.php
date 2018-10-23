<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 22/10/18
 * Time: 4:59 PM
 */

namespace App\Repositories;


interface UserRepository
{

    public function getPayersByCurrentUser($currentUser);

    public function getPayerByIdAndCurrentUser($id,$currentUser);

    public function getStudentsByCurrentUser($currentUser);

    public function getStudentByIdAndCurrentUser($id,$currentUser);
}