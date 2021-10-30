<?php
namespace src\controllers;

use \core\Controller;
use \src\handlers\UserHandler;
use \src\handlers\PostHandler;
use \src\models\User;

class ProfileController extends Controller {
    private $loggedUser;

    public function __construct() {
        $this->loggedUser = UserHandler::checkLogin();
        if($this->loggedUser===false){
            $this->redirect('/login');
        }

    }
    public function index($atts = []) {
        $page = intval(filter_input(INPUT_GET, 'page'));
        //Detectando o usuário acessado
        $id = $this->loggedUser->id;

        if(!empty($atts['id'])){
            $id = $atts['id'];
        }
        //Pegando informações de usuário
        $user = UserHandler::getUser($id, true);

        if(!$user){
            $this->redirect('/');
        }
        $dateFrom = new \DateTime($user->birthdate);
        $dateTo = new \DateTime('today');
        $user->ageYears = $dateFrom->diff($dateTo)->y;
        //Pegando o feed do usuário
        $feed = PostHandler::getUserFeed($id, $page, $this->loggedUser->id);

        //Verificar se eu sigo o usuário
        $isFollowing = false;
        if($user->id != $this->loggedUser->id){
            $isFollowing = UserHandler::isFollowing($this->loggedUser->id, $user->id);
        }

        $this->render('profile',[
            'loggedUser' => $this->loggedUser,
            'user' => $user,
            'feed' => $feed,
            'isFollowing' =>$isFollowing
        ]);
    }

   public function follow($atts){
        $to =  intval($atts['id']);

        if(UserHandler::idExists($to)){
            if(UserHandler::isFollowing($this->loggedUser->id, $to)){
                //deixar de seguir
                UserHandler::unfollow($this->loggedUser->id, $to);
            }else{
                //seguir
                UserHandler::follow($this->loggedUser->id, $to);
            }
        }

        $this->redirect('/perfil/'.$to);
   }

   public function friends($atts = []){
    $id = $this->loggedUser->id;

    if(!empty($atts['id'])){
        $id = $atts['id'];
    }
    //Pegando informações de usuário
    $user = UserHandler::getUser($id, true);

    if(!$user){
        $this->redirect('/');
    }
    $dateFrom = new \DateTime($user->birthdate);
    $dateTo = new \DateTime('today');
    $user->ageYears = $dateFrom->diff($dateTo)->y;

    $isFollowing = false;
    if($user->id != $this->loggedUser->id){
        $isFollowing = UserHandler::isFollowing($this->loggedUser->id, $user->id);
    }

    $this->render('profile_friends',[
        'loggedUser' => $this->loggedUser,
        'user' => $user,
        'isFollowing' =>$isFollowing
    ]);
   }

   public function photos($atts = []){
    $id = $this->loggedUser->id;

    if(!empty($atts['id'])){
        $id = $atts['id'];
    }
    //Pegando informações de usuário
    $user = UserHandler::getUser($id, true);

    if(!$user){
        $this->redirect('/');
    }
    $dateFrom = new \DateTime($user->birthdate);
    $dateTo = new \DateTime('today');
    $user->ageYears = $dateFrom->diff($dateTo)->y;

    $isFollowing = false;
    if($user->id != $this->loggedUser->id){
        $isFollowing = UserHandler::isFollowing($this->loggedUser->id, $user->id);
    }

    $this->render('profile_photos',[
        'loggedUser' => $this->loggedUser,
        'user' => $user,
        'isFollowing' =>$isFollowing
    ]);
   }
   
   public function config(){
    $id = $this->loggedUser->id;

    //Pegando informações de usuário
    $user = UserHandler::getUser($id, true);

    $date = strtotime($user->birthdate);
    $dateTime = date('d/m/Y', $date);

    if(!$user){
        $this->redirect('/');
    }
   
    $this->render('config',[
        'loggedUser' => $this->loggedUser,
        'user' => $user,
        'city' => $user->city,
        'nome' => $user->name,
        'email' => $user->email,
        'work' => $user->work,
        'birthdate' =>$dateTime
    ]);

   }

   public function configAction(){
    $id = $this->loggedUser->id;
    $user = UserHandler::getUser($id, true);

    $name = filter_input(INPUT_POST, 'name');
    $birthdate = filter_input(INPUT_POST, 'birthdate');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $city = filter_input(INPUT_POST, 'city');
    $work = filter_input(INPUT_POST, 'work');
    $password = filter_input(INPUT_POST, 'password');
    $passwordVerify = filter_input(INPUT_POST, 'confirm_password');
    $flash = '';

    if($name && $email){

        //E-MAIL
        if($user->email != $email){
            if(UserHandler::emailExists($email)){
                $_SESSION['flash'] = 'Esse email já existe';
            }

        }

        //NASCIMENTO
        $birthdate = explode('/', $birthdate);
            if(count($birthdate) != 3){
                $_SESSION['flash'] = 'Data de nascimento inválida';
            }
            $birthdate = $birthdate[2].'-'.$birthdate[1].'-'.$birthdate[0];
            if(strtotime($birthdate) === false){
                $_SESSION['flash'] = 'Data de nascimento inválida';
            }

        //SENHA
        if($password !== $passwordVerify){
            $_SESSION['flash'] = 'As senhas não são iguais';
        }

        //AVATAR
        if(isset($_FILES['avatar']) && !empty($_FILES['avatar']['tmp_name'])){
            $newAvatar = $_FILES['avatar'];
            if(in_array($newAvatar['type'], ['image/jpeg', 'image/jpg', 'image/png'])){
                $avatarName = $this->cutImage($newAvatar, 200, 200, 'media/avatars'); 
            }
        }

        //COVER
        if(isset($_FILES['cover']) && !empty($_FILES['cover']['tmp_name'])){
            $newCover = $_FILES['cover'];
            if(in_array($newAvatar['type'], ['image/jpeg', 'image/jpg', 'image/png'])){
                $coverName = $this->cutImage($newCover, 850, 310, 'media/covers'); 
                User::update()
                    ->set('cover', $coverName)
                ->execute();
            }

        }
        UserHandler::updateUser(
        $name, 
        $birthdate, 
        $email, 
        $city, 
        $work, 
        $password, 
        $id);
            
            
    }   

    if(!empty($_SESSION['flash'])){
        $flash = $_SESSION['flash'];
        $_SESSION['flash'] = '';
    }
    $this->redirect('/config'); 
   }

   private function cutImage($file, $w, $h, $folder){
    list($widthOrig, $heightOrig) = getimagesize($_FILES['tmp_name']);
    $ratio = $widthOrig / $heightOrig;

    $newWidth = $w;
    $newHeight = $newWidth / $ratio;

    if($newHeight < $h){
        $newHeight = $h;
        $newWidth = $newHeight * $ratio;

    }
    $x = $w - $newWidth;
    $y = $h - $newHeight;
    $x = $x < 0 ? $x/2 : $x;
    $y = $y < 0 ? $y/2 : $y;


    $finalImage = imagecreatetruecollor($w, $h);

    switch($file['type']){
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($file['tmp_name']);
        break;

        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
        break;
    }

    imagecopyresampled(
        $finalImage, $image,
        $x, $y, 0, 0,
        $newWidth, $newHeight, $widthOrig, $heightOrig
    );


    $fileName = md5(time().rand(0,99999)).'jpg';

    imagejpeg($finalImage, $folder.'/'.$fileName);

    return $fileName;

   }

}