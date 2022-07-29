<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/signup', name: 'signup', methods: ["POST"])]
    public function signUp(Request $request, UserRepository $userRepository): JsonResponse
    {
        try{
            if (isset($_COOKIE['logged_in']) && $_COOKIE['logged_in'] === '1') throw new \Exception('You are logged in!');
            $request = $this->transformJsonBody($request);
            if (!$request->get('login')) throw new \Exception('Login was lost');
            else if (!$request->get('password')) throw new \Exception('Password was lost');
            else if (!$request->get('email')) throw new \Exception('Email was lost');
            else if (!$request->get('phone')) throw new \Exception('Phone was lost');
            else if (!$request->get('firstName')) throw new \Exception('First name was lost');
            else if (!$request->get('lastName')) throw new \Exception('Last name was lost');

            if ($userRepository->findOneBy(['login' => $request->get('login')])) throw new \Exception('User just already exists');

            $user = new User();
            $user->
            setLogin($request->get('login'))->
            setPassword(password_hash($request->get('password'), PASSWORD_DEFAULT))->
            setEmail($request->get('email'))->
            setPhone($request->get('phone'))->
            setFirstName($request->get('firstName'))->
            setLastName($request->get('lastName'))->
            setRole('default');

            $userRepository->add($user, true);

            $data = [
                'status' => 200,
                'success' => "User added successfully. Welcome!",
            ];

            setcookie('logged_in', '1');
            setcookie('login', $request->get('login'));

            return $this->response($data);
        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'error' => $e->getMessage(),
            ];

            return $this->response($data);
        }
    }

    #[Route('/signin', name: 'signin', methods: ["POST"])]
    public function signIn(Request $request, UserRepository $userRepository): JsonResponse
    {
        try{
            if (isset($_COOKIE['logged_in']) && $_COOKIE['logged_in'] === '1') throw new \Exception('You are already logged in!');
            $request = $this->transformJsonBody($request);

            if (!$request->get('login')) throw new \Exception('Login was lost');
            else if (!$request->get('password')) throw new \Exception('Password was lost');

            $user = $userRepository->findOneBy(['login' => $request->get('login')]);

            if (!$user) throw new \Exception('Login wrong');
            else if (!password_verify($request->get('password'), $user->getPassword())) throw new \Exception('Password wrong');

            $data = [
                'status' => 200,
                'success' => "You was logged in successfully. Welcome!",
            ];

            setcookie('logged_in', '1');
            setcookie('login', $request->get('login'));

            return $this->response($data);
        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'error' => $e->getMessage(),
            ];

            return $this->response($data);
        }
    }

    #[Route('/exit', name: 'exit', methods: ["POST"])]
    public function doExit(Request $request): JsonResponse
    {
        try{
            if (!isset($_COOKIE['logged_in'])) throw new \Exception('You are not logged in yet!');

            $data = [
                'status' => 200,
                'success' => "You exited successfully. Bye!",
            ];

            setcookie('logged_in', 1, time()-3600);
            setcookie('login', $request->get('login'), time()-3600);

            return $this->response($data);
        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'error' => $e->getMessage(),
            ];

            return $this->response($data);
        }
    }

    #[Route('/addUser', name: 'add_user', methods: ["POST"])]
    public function addUser(Request $request, UserRepository $userRepository): JsonResponse
    {
        try{
            if (!isset($_COOKIE['logged_in'])) throw new \Exception('You are not logged in for adding a new user!');

            $user = $userRepository->findOneBy(['login' => $_COOKIE['login']]);

            if ($user->getRole() !== "admin") throw new \Exception('Only admin can add a new user!');

            $request = $this->transformJsonBody($request);

            if (!$request->get('login')) throw new \Exception('Login was lost');
            else if (!$request->get('password')) throw new \Exception('Password was lost');
            else if (!$request->get('email')) throw new \Exception('Email was lost');
            else if (!$request->get('phone')) throw new \Exception('Phone was lost');
            else if (!$request->get('firstName')) throw new \Exception('First name was lost');
            else if (!$request->get('lastName')) throw new \Exception('Last name was lost');
            else if (!$request->get('role')) throw new \Exception('Role was lost');

            if ($userRepository->findOneBy(['login' => $request->get('login')])) throw new \Exception('User just already exists');

            $new_user = new User();
            $new_user->
            setLogin($request->get('login'))->
            setPassword(password_hash($request->get('password'), PASSWORD_DEFAULT))->
            setEmail($request->get('email'))->
            setPhone($request->get('phone'))->
            setFirstName($request->get('firstName'))->
            setLastName($request->get('lastName'))->
            setRole($request->get('role'));

            $userRepository->add($new_user, true);

            $data = [
                'status' => 200,
                'success' => "User added successfully!",
            ];

            return $this->response($data);
        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'error' => $e->getMessage(),
            ];

            return $this->response($data);
        }
    }

    #[Route('/deleteUser', name: 'delete_user', methods: ["POST"])]
    public function deleteUser(Request $request, UserRepository $userRepository): JsonResponse
    {
        try{
            if (!isset($_COOKIE['logged_in'])) throw new \Exception('You are not logged in for deleting a user!');

            $user = $userRepository->findOneBy(['login' => $_COOKIE['login']]);

            if ($user->getRole() !== "admin") throw new \Exception('Only admin can delete a user!');

            $request = $this->transformJsonBody($request);

            if (!$request->get('login')) throw new \Exception('Login was lost');

            $user_for_delete = $userRepository->findOneBy(['login' => $request->get('login')]);

            if (!$user_for_delete) throw new \Exception('User don\'t exists');

            $userRepository->remove($user_for_delete, true);

            $data = [
                'status' => 200,
                'success' => "User deleted successfully!",
            ];

            return $this->response($data);
        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'error' => $e->getMessage(),
            ];

            return $this->response($data);
        }
    }

    #[Route('/changeProfile', name: 'change_profile', methods: 'POST')]
    public function changeProfile(Request $request, UserRepository $userRepository): JsonResponse
    {
        try {
            if (!isset($_COOKIE['logged_in'])) throw new \Exception('You are not logged in for changing a user!');
            $request = $this->transformJsonBody($request);

            $user = $userRepository->findOneBy(['login' => $_COOKIE['login']]);

            $login = $request->get('login') ?: $user->getLogin();
            $user_for_change = $userRepository->findOneBy(['login' => $login]);

            if ($user->getRole() === 'admin') {

                if (!$user_for_change) throw new \Exception('User with this login doesn\'t exist');

                $changes = json_decode($request->getContent(),true);
                foreach ($changes as $field => $value) {
                    switch($field){
                        case 'password':
                            $user_for_change->setPassword(password_hash($value, PASSWORD_DEFAULT));
                            break;
                        case 'email':
                            $user_for_change->setEmail($value);
                            break;
                        case 'phone':
                            $user_for_change->setPhone($value);
                            break;
                        case 'firstName':
                            $user_for_change->setFirstName($value);
                            break;
                        case 'lastName':
                            $user_for_change->setLastName($value);
                            break;
                    }
                }
                $userRepository->merge($user_for_change, true);

                $data = [
                    'status' => 200,
                    'success' => "User changed successfully!",
                ];

                return $this->response($data);
            }

            if ($request->get('login') && $request->get('login') !== $user->getLogin()) throw new \Exception('Only admin can change other profiles');

            $changes = json_decode($request->getContent(),true);
            foreach ($changes as $field => $value) {
                switch($field){
                    case 'password':
                        $user_for_change->setPassword(password_hash($value, PASSWORD_DEFAULT));
                        break;
                    case 'email':
                        $user_for_change->setEmail($value);
                        break;
                    case 'phone':
                        $user_for_change->setPhone($value);
                        break;
                    case 'firstName':
                        $user_for_change->setFirstName($value);
                        break;
                    case 'lastName':
                        $user_for_change->setLastName($value);
                        break;
                }
            }
            $userRepository->merge($user_for_change, true);

            $data = [
                'status' => 200,
                'success' => "User changed successfully!",
            ];

            return $this->response($data);

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'error' => $e->getMessage(),
            ];

            return $this->response($data);
        }
    }

    public function response($data, $status = 200, $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function transformJsonBody(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }
}
