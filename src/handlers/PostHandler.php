<?php

namespace src\handlers;
use \src\models\Post;
use \src\models\PostLike;
use \src\models\PostComment;
use \src\models\User;
use \src\models\UserRelation;

class PostHandler {
    public static function addUser($idUser, $type, $body){
        $body = trim($body);
        if(!empty($idUser) && !empty($body)){
            Post::insert([
                'id_user'=>$idUser,
                'type' => $type,
                'created_at'=> date('Y-m-d H:i:s'),
                'body' => $body
            ])->execute();
        }
    }

    public static function _postListToObject($postList, $loggedUserId){
        $posts = [];
        foreach($postList as $postItem){
            $newPost = new Post();
            $newPost->id = $postItem['id'];
            $newPost->type = $postItem['type'];
            $newPost->created_at = $postItem['created_at'];
            $newPost->body = $postItem['body'];
            $newPost->mine = false;
            if($postItem['id_user'] == $loggedUserId){
                $newPost->mine = true;
            }
            //4. Pegar as informações adicionais do post
            $newUser = User::select()->where('id', $postItem['id_user'])->one();
            $newPost->user = new User();
            $newPost->user->id = $newUser['id'];
            $newPost->user->name = $newUser['name'];
            $newPost->user->avatar = $newUser['avatar'];
            //4.1 Preencher informações de like
            $like = PostLike::select()->where('id_post', $postItem['id'])->get();
            $myLike = PostLike::select()->where('id_post', $postItem['id'])->where('id_user', $loggedUserId)->get();

            $newPost->likeCount = count($like);
            $newPost->liked = self::isLiked($postItem['id'], $loggedUserId);
            $newPost->comments = PostComment::select()->where('id_post', $postItem['id'])->get();
            foreach($newPost->comments as $key => $comment) {
                $newPost->comments[$key]['user'] = User::select()->where('id', $comment['id_user'])->one();
            }
                
            //4.2 Preencher informações de comments

            $posts[] = $newPost;
        }

        return $posts;
    }

    public static function isLiked($id, $loggedUserId){
        $myLike = PostLike::select()->where('id_post', $id)->where('id_user', $loggedUserId)->get();
        if(count($myLike) > 0){
            return true;
        }else{
            return false;
        }

    }

    public static function deleteLike($id, $loggedUserId){
        PostLike::delete()
            ->where('id_post', $id)
            ->where('id_user', $loggedUserId)
        ->execute();

    }

    public static function addLike($id, $loggedUserId){
       PostLike::insert([
           'id_post' =>$id,
           'id_user' => $loggedUserId,
           'created_at' => date('Y-m-d H:i:s')
       ])
       ->execute();


    }

    public static function addComment($id, $txt, $loggedUserId){
        PostComment::insert([
            'id_post' => $id, 
            'id_user' => $loggedUserId,
            'created_at' => date('Y-m-d H:i:s'),
            'body' => $txt
        ])->execute();

    }

    public static function getUserFeed($idUser, $page, $loggedUserId){
        $perPage = 2;
        //1. Pegar a lista de posts que EU sigo
        $userList = UserRelation::select()->where('user_from', $idUser)->get();
        $users = [];
        foreach($userList as $userItem){
            $users[] = $userItem['user_to'];
        }

        $users[] = $idUser;
        //2. Pegar os posts dessa galera ordenando pela data
        $postList = Post::select()->where('id_user', $idUser)->orderBy('created_at', 'desc')->page($page, $perPage)->get();
        $total = Post::select()->where('id_user', 'in', $users)->count();
        $pageCount = ceil($total/$perPage);

        //3. Transformar o resultado em objeto dos models
        $posts = self::_postListToObject($postList, $loggedUserId);
        

        return [
            'posts' => $posts,
            'pageCount' => $pageCount,
            'currentPage' => $page
        ];
    }

    public static function getHomeFeed($idUser, $page){
        $perPage = 2;
        //1. Pegar a lista de posts que EU sigo
        $userList = UserRelation::select()->where('user_from', $idUser)->get();
        $users = [];
        foreach($userList as $userItem){
            $users[] = $userItem['user_to'];
        }

        $users[] = $idUser;
        //2. Pegar os posts dessa galera ordenando pela data
        $postList = Post::select()->where('id_user', 'in', $users)->orderBy('created_at', 'desc')->page($page, $perPage)->get();
        $total = Post::select()->where('id_user', 'in', $users)->count();
        $pageCount = ceil($total/$perPage);

        //3. Transformar o resultado em objeto dos models
        $posts = self::_postListToObject($postList, $idUser);
        

        return [
            'posts' => $posts,
            'pageCount' => $pageCount,
            'currentPage' => $page
        ];

    }

    public static function getPhotosFrom($idUser){
        $photosData = Post::select()
            ->where('id_user', $idUser)
            ->where('type', 'photo')
        ->get();

        $photos = [];

        foreach($photosData as $photo){
            $newPost = new Post();
            $newPost->id = $photo['id'];
            $newPost->type = $photo['type'];
            $newPost->created_at = $photo['created_at'];
            $newPost->body = $photo['body'];

            $photos[] = $newPost;
        }

        return $photos;
    }

    public static function delete($id, $loggedUserId){
    //1 - Verificar se o post existe (e se é seu)
        $post = Post::select()->where('id', $id)->where('id_user', $loggedUserId)->get();

        if(count($post) > 0){
            $post = $post[0];

            //2 - Deletar os likes e comments
            PostLike::delete()->where('id_post', $id)->execute();
            PostComment::delete()->where('id_post', $id)->execute();

            //3 Se a foto for type === photo, deletar o arquivo
            if($post['type'] === 'photo'){
                $img = __DIR__.'/../../public/media/uploads/'.$post['body'];

                if(file_exists($img)){
                    unlink($img);
                }

            }

            //4 Deletar o post
            Post::delete()->where('id', $id)->execute();

        }

    }
}

