<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 22/10/18
 * Time: 11:40 AM
 */

namespace App\Services;


interface UserService
{

    public function createPayer($payer,$credentials);

    public function updatePayer($payer,$credentials);

    public function getFailureResponse($message,$code);

    public function successMessage($message,$code);
}